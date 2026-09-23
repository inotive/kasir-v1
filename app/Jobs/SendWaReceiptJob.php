<?php

namespace App\Jobs;

use App\Helpers\DataLabelHelper;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\TransactionItemAddon;
use App\Models\WhatsappSetting;
use App\Services\Transactions\TransactionEventService;
use App\Services\Whatsapp\OpenwaService;
use App\Support\Phone\PhoneNumber;
use App\Support\Products\ItemNameFormatter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWaReceiptJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $transactionId,
        public ?int $tenantId,
        public bool $isResend = false,
    ) {}

    public function handle(OpenwaService $openwa): void
    {
        // Queue workers have no HTTP request/subdomain to resolve the tenant from, unlike every
        // other caller of BelongsToTenant-scoped models in this app. A null tenantId means the
        // job was dispatched outside any tenant context (e.g. a single-tenant deployment); in
        // that case there is nothing to switch to and the global scope is left as-is.
        $tenant = null;
        if ($this->tenantId !== null) {
            $tenant = Tenant::find($this->tenantId);
            if (! $tenant) {
                return;
            }

            $tenant->makeCurrent();
        }

        $transaction = Transaction::query()
            ->with([
                'member',
                'diningTable',
                'transactionItems.product',
                'transactionItems.variant',
                'transactionItems.itemAddons.addon',
                'transactionItems.childTransactionItems.product',
                'transactionItems.childTransactionItems.variant',
                'transactionItems.childTransactionItems.itemAddons.addon',
            ])
            ->find($this->transactionId);
        if (! $transaction) {
            return;
        }

        $waSetting = WhatsappSetting::current();
        if (! $waSetting->is_enabled || ! $waSetting->openwa_session_id) {
            $this->markFailed($transaction, 'WhatsApp belum diaktifkan/di-setup untuk tenant ini.');

            return;
        }

        $chatId = PhoneNumber::toWhatsAppChatId($transaction->member?->phone ?? $transaction->phone);
        if (! $chatId) {
            $this->markFailed($transaction, 'Nomor WhatsApp pelanggan tidak tersedia.');

            return;
        }

        $text = $this->buildMessage($transaction, $tenant);

        try {
            $openwa->sendText($waSetting->openwa_session_id, $chatId, $text);

            $transaction->forceFill([
                'wa_receipt_sent_at' => now(),
                'wa_receipt_status' => 'sent',
                'wa_receipt_error' => null,
            ])->save();

            app(TransactionEventService::class)->record(
                $transaction,
                $this->isResend ? 'wa_receipt_resent' : 'wa_receipt_sent',
            );
        } catch (\Throwable $e) {
            $this->markFailed($transaction, $e->getMessage());

            Log::error('WA receipt send failed', [
                'transaction_id' => $transaction->id,
                'exception' => $e,
            ]);
        }
    }

    private function markFailed(Transaction $transaction, string $reason): void
    {
        $transaction->forceFill([
            'wa_receipt_status' => 'failed',
            'wa_receipt_error' => $reason,
        ])->save();

        app(TransactionEventService::class)->record($transaction, 'wa_receipt_failed', ['reason' => $reason]);
    }

    private function buildMessage(Transaction $transaction, ?Tenant $tenant): string
    {
        $storeName = (string) (Setting::current()->store_name ?? config('app.name'));
        $customerName = (string) ($transaction->member?->name ?? $transaction->name ?? 'Pelanggan');
        $date = $transaction->updated_at?->format('d/m/Y H:i') ?? '-';
        $tableNumber = $transaction->diningTable?->table_number;

        $lines = [];
        $lines[] = "*Struk Pembayaran* — {$storeName}";
        $lines[] = 'Terima kasih atas pesanan Anda!';
        $lines[] = '';
        $lines[] = "Kode Transaksi: {$transaction->code}";
        $lines[] = "Tanggal: {$date}";
        $lines[] = "Pelanggan: {$customerName}";
        $lines[] = 'Nomor Meja: '.($tableNumber ?: '-');
        $lines[] = 'Metode Pembayaran: '.DataLabelHelper::enum($transaction->payment_method, 'payment_method');
        $lines[] = '';
        $lines[] = '*Detail Pesanan*';

        foreach ($transaction->transactionItems->whereNull('parent_transaction_item_id') as $item) {
            $lines = array_merge($lines, $this->formatItemLines($item));
        }

        $lines[] = '';
        $lines[] = $this->money('Subtotal', (int) $transaction->subtotal);

        if ((int) $transaction->voucher_discount_amount > 0) {
            $voucherLabel = 'Diskon Voucher'.($transaction->voucher_code ? " ({$transaction->voucher_code})" : '');
            $lines[] = $this->money($voucherLabel, -(int) $transaction->voucher_discount_amount);
        }

        if ((int) $transaction->manual_discount_amount > 0) {
            $lines[] = $this->money('Diskon Manual', -(int) $transaction->manual_discount_amount);
        }

        if ((int) $transaction->point_discount_amount > 0) {
            $points = number_format((int) $transaction->points_redeemed, 0, ',', '.');
            $lines[] = $this->money("Diskon Poin ({$points} Poin)", -(int) $transaction->point_discount_amount);
        }

        if ((int) $transaction->tax_amount > 0) {
            $lines[] = $this->money("Pajak PB1 ({$transaction->tax_percentage}%)", (int) $transaction->tax_amount);
        }

        if ((int) ($transaction->payment_fee_amount ?? 0) > 0) {
            $lines[] = $this->money('Biaya Admin', (int) $transaction->payment_fee_amount);
        }

        if ((int) ($transaction->rounding_amount ?? 0) !== 0) {
            $lines[] = $this->money('Pembulatan', (int) $transaction->rounding_amount);
        }

        $lines[] = '*'.$this->money('Total', (int) $transaction->total).'*';

        if ($transaction->payment_method === 'cash' && $transaction->cash_received !== null) {
            $lines[] = $this->money('Tunai', (int) $transaction->cash_received);
            $lines[] = $this->money('Kembalian', (int) ($transaction->cash_change ?? 0));
        }

        $path = route('self-order.payment.receipt', [
            'code' => $transaction->code,
            'wa_token' => $transaction->wa_receipt_token,
        ], absolute: false);

        $lines[] = '';
        $lines[] = 'Lihat struk digital di sini:';
        $lines[] = $this->tenantUrl($tenant, $path);

        return implode("\n", $lines);
    }

    /**
     * @return list<string>
     */
    private function formatItemLines(TransactionItem $item): array
    {
        $productName = $item->product ? (string) $item->product->name : 'Produk';
        $variantName = ItemNameFormatter::displayVariantName((int) $item->product_id, $item->variant?->name);
        $subtotal = number_format((int) $item->subtotal, 0, ',', '.');

        $label = "{$item->quantity}x {$productName}";
        if ($variantName !== '') {
            $label .= " ({$variantName})";
        }

        $lines = ["{$label} — Rp{$subtotal}"];

        foreach ($item->itemAddons as $addon) {
            $lines[] = $this->formatAddonLine($addon);
        }

        foreach ($item->childTransactionItems as $child) {
            $childProductName = $child->product ? (string) $child->product->name : 'Produk';
            $childVariantName = ItemNameFormatter::displayVariantName((int) $child->product_id, $child->variant?->name);
            $childLabel = "  • {$childProductName}";
            if ($childVariantName !== '') {
                $childLabel .= " ({$childVariantName})";
            }
            $lines[] = "{$childLabel} x{$child->quantity}";

            foreach ($child->itemAddons as $addon) {
                $lines[] = '  '.$this->formatAddonLine($addon);
            }
        }

        return $lines;
    }

    private function formatAddonLine(TransactionItemAddon $itemAddon): string
    {
        $name = $itemAddon->addon?->name ?? 'Add-on';
        $amount = number_format((int) $itemAddon->price * (int) $itemAddon->quantity, 0, ',', '.');

        return "  + {$name} {$itemAddon->quantity}x (Rp{$amount})";
    }

    private function money(string $label, int $amount): string
    {
        $sign = $amount < 0 ? '-' : '';
        $formatted = number_format(abs($amount), 0, ',', '.');

        return "{$label}: {$sign}Rp{$formatted}";
    }

    /**
     * Queue workers have no request/Host header to build an absolute URL from, so route()'s
     * default (config('app.url')) is a single global host with no subdomain — wrong for every
     * tenant here, since SubdomainTenantFinder resolves the tenant from the URL's subdomain and
     * TenantScope then filters every query to that tenant's rows. Opening a link built from
     * config('app.url') alone resolves no tenant, so the transaction lookup 404s. Fix: rebuild
     * the URL using this tenant's own `domain` column (e.g. "tenant01.localhost"), keeping only
     * the scheme/port from config('app.url').
     */
    private function tenantUrl(?Tenant $tenant, string $path): string
    {
        $appUrl = (string) config('app.url');
        $parsed = parse_url($appUrl) ?: [];
        $scheme = (string) ($parsed['scheme'] ?? 'http');
        $port = isset($parsed['port']) ? ':'.$parsed['port'] : '';
        $host = $tenant?->domain ?: (string) ($parsed['host'] ?? 'localhost');

        return $scheme.'://'.$host.$port.$path;
    }
}
