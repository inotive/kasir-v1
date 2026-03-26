<?php

namespace App\Services\Printing;

use App\Models\PrinterSource;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Support\Products\ItemNameFormatter;

class PosPrintPayloadService
{
    private function mergeRows(array $rows): array
    {
        $merged = [];
        foreach ($rows as $row) {
            $productId = (int) ($row['product_id'] ?? 0);
            $variantId = (int) ($row['product_variant_id'] ?? 0);
            $note = trim((string) ($row['note'] ?? ''));

            $key = $productId.'|'.$variantId.'|'.$note;

            if (! isset($merged[$key])) {
                $merged[$key] = $row;
                $merged[$key]['quantity'] = (int) ($row['quantity'] ?? 0);

                continue;
            }

            $merged[$key]['quantity'] += (int) ($row['quantity'] ?? 0);
        }

        return array_values($merged);
    }

    public function build(int $transactionId, ?string $cashierName = null): ?array
    {
        $trx = Transaction::query()
            ->with(['transactionItems.product.printerSource', 'transactionItems.variant', 'diningTable'])
            ->whereKey($transactionId)
            ->first();

        if (! $trx) {
            return null;
        }

        $setting = Setting::current();

        $sources = PrinterSource::query()
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        $kasirSourceId = $sources->firstWhere('type', 'kasir')?->id;

        $kasirItems = $trx->transactionItems
            ->whereNull('parent_transaction_item_id')
            ->values();

        $stationItems = $trx->transactionItems
            ->filter(function (TransactionItem $item): bool {
                if ($item->parent_transaction_item_id !== null) {
                    return true;
                }

                if (! $item->product) {
                    return true;
                }

                return ! (bool) $item->product->is_package;
            })
            ->values();

        $items = $kasirItems->map(function (TransactionItem $item) {
            $productName = $item->product ? (string) $item->product->name : 'Produk';
            $variantName = ItemNameFormatter::displayVariantName((int) $item->product_id, $item->variant?->name);

            return [
                'product_id' => (int) $item->product_id,
                'product_variant_id' => (int) ($item->product_variant_id ?? 0),
                'quantity' => (int) $item->quantity,
                'price' => (int) round((float) $item->price),
                'note' => $item->note,
                'name' => $productName,
                'variant_name' => $variantName,
                'product' => [
                    'name' => $productName,
                    'printer_source_id' => $item->product?->printer_source_id,
                ],
                'product_variant' => [
                    'name' => $variantName,
                ],
            ];
        })->all();

        $itemsBySourceId = [];
        $unassignedItems = [];
        $checkerItems = [];
        foreach ($stationItems as $item) {
            $sourceId = $item->product?->printer_source_id;
            $productName = $item->product ? (string) $item->product->name : 'Produk';
            $variantName = ItemNameFormatter::displayVariantName((int) $item->product_id, $item->variant?->name);

            $row = [
                'product_id' => (int) $item->product_id,
                'product_variant_id' => (int) ($item->product_variant_id ?? 0),
                'quantity' => (int) $item->quantity,
                'price' => (int) round((float) $item->price),
                'note' => $item->note,
                'name' => $productName,
                'variant_name' => $variantName,
                'product' => [
                    'name' => $productName,
                    'printer_source_id' => $sourceId,
                ],
                'product_variant' => [
                    'name' => $variantName,
                ],
            ];

            $checkerItems[] = $row;

            if ($sourceId) {
                $itemsBySourceId[(int) $sourceId] ??= [];
                $itemsBySourceId[(int) $sourceId][] = $row;
            } else {
                $unassignedItems[] = $row;
            }
        }

        $checkerItems = $this->mergeRows($checkerItems);
        foreach ($itemsBySourceId as $sourceId => $rows) {
            $itemsBySourceId[$sourceId] = $this->mergeRows($rows);
        }
        $unassignedItems = $this->mergeRows($unassignedItems);

        $printerSources = $sources
            ->map(fn (PrinterSource $s) => [
                'id' => (int) $s->id,
                'name' => (string) $s->name,
                'type' => (string) $s->type,
            ])
            ->values()
            ->all();

        $suggestedSourceIds = array_values(array_unique(array_map('intval', array_keys($itemsBySourceId))));
        if ($kasirSourceId) {
            $suggestedSourceIds[] = (int) $kasirSourceId;
            $suggestedSourceIds = array_values(array_unique($suggestedSourceIds));
        }

        $resolvedCashierName = $cashierName !== null && trim($cashierName) !== '' ? trim($cashierName) : null;
        $logoPath = trim((string) ($setting->store_logo ?? ''));
        $logoUrl = $logoPath !== '' ? '/storage/'.ltrim($logoPath, '/') : null;

        $user = auth()->user();
        $canViewPii = (bool) (($user && method_exists($user, 'can')) ? $user->can('transactions.pii.view') : false);
        $customerName = $canViewPii ? (string) $trx->name : '-';

        $createdAt = $trx->created_at ? $trx->created_at->copy() : now();
        $queueDate = $createdAt->toDateString();
        $startOfDay = $createdAt->copy()->startOfDay();
        $endOfDay = $createdAt->copy()->endOfDay();

        $queueNumber = (int) Transaction::query()
            ->whereNull('voided_at')
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->where(function ($q) use ($createdAt, $trx) {
                $q->where('created_at', '<', $createdAt)
                    ->orWhere(function ($q2) use ($createdAt, $trx) {
                        $q2->where('created_at', '=', $createdAt)
                            ->where('id', '<=', (int) $trx->id);
                    });
            })
            ->count();

        if ($queueNumber <= 0) {
            $queueNumber = 1;
        }

        return [
            'store' => [
                'name' => (string) ($setting->store_name ?? 'TOKO'),
                'address' => (string) ($setting->address ?? '-'),
                'phone' => (string) ($setting->phone ?? '-'),
                'logo_url' => $logoUrl,
                'cashier_receipt_print_logo' => (bool) ($setting->cashier_receipt_print_logo ?? true),
            ],
            'order' => [
                'code' => (string) $trx->code,
                'order_type' => (string) $trx->order_type,
                'queue_number' => $queueNumber,
                'queue_date' => $queueDate,
                'voucher_code' => (string) ($trx->voucher_code ?? ''),
                'subtotal' => (int) $trx->subtotal,
                'tax_percentage' => $trx->tax_percentage === null ? null : (float) $trx->tax_percentage,
                'tax_amount' => (int) ($trx->tax_amount ?? 0),
                'rounding_amount' => (int) ($trx->rounding_amount ?? 0),
                'total' => (int) $trx->total,
                'voucher_discount_amount' => (int) ($trx->voucher_discount_amount ?? 0),
                'manual_discount_amount' => (int) ($trx->manual_discount_amount ?? 0),
                'point_discount_amount' => (int) ($trx->point_discount_amount ?? 0),
                'points_redeemed' => (int) ($trx->points_redeemed ?? 0),
                'points_earned' => (int) ($trx->points_earned ?? 0),
                'cash_received' => $trx->cash_received === null ? null : (int) $trx->cash_received,
                'change' => $trx->cash_change === null ? null : (int) $trx->cash_change,
                'dining_table' => $trx->diningTable ? ['table_number' => (string) $trx->diningTable->table_number] : null,
            ],
            'customer_name' => $customerName,
            'name_kasir' => (string) ($resolvedCashierName ?? (auth()->user()->name ?? 'Kasir')),
            'table_number' => $trx->diningTable ? (string) $trx->diningTable->table_number : null,
            'items' => $items,
            'checker_items' => $checkerItems,
            'printer_sources' => $printerSources,
            'default_kasir_source_id' => $kasirSourceId ? (int) $kasirSourceId : null,
            'items_by_source' => $itemsBySourceId,
            'unassigned_items' => $unassignedItems,
            'suggested_source_ids' => $suggestedSourceIds,
        ];
    }
}
