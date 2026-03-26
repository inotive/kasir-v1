<?php

namespace App\Livewire\Vouchers;

use App\Models\VoucherCampaign;
use App\Support\Finance\NetSales;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class VoucherPerformancePage extends Component
{
    public string $title = 'Performa Voucher';

    public ?string $from = null;

    public ?string $to = null;

    public string $rangePreset = '30d';

    public function mount(): void
    {
        $this->setRange('30d');
    }

    public function setTransactionsRange(?string $from, ?string $to): void
    {
        if (! $from || ! $to) {
            return;
        }

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $this->from = $from;
        $this->to = $to;
        $this->rangePreset = 'custom';
    }

    public function setRange(string $preset): void
    {
        $today = CarbonImmutable::now();

        if ($preset === 'today') {
            $from = $today;
            $to = $today;
        } elseif ($preset === '7d') {
            $from = $today->subDays(6);
            $to = $today;
        } elseif ($preset === '30d') {
            $from = $today->subDays(29);
            $to = $today;
        } elseif ($preset === 'custom') {
            return;
        } else {
            return;
        }

        $this->from = $from->format('Y-m-d');
        $this->to = $to->format('Y-m-d');
        $this->rangePreset = $preset;
    }

    public function render(): View
    {
        $from = $this->from ? (string) $this->from : now()->subDays(30)->format('Y-m-d');
        $to = $this->to ? (string) $this->to : now()->format('Y-m-d');

        $fromAt = $from.' 00:00:00';
        $toAt = $to.' 23:59:59';

        $sub = DB::table('transactions as t')
            ->join('transaction_items as ti', 't.id', '=', 'ti.transaction_id')
            ->whereBetween('t.created_at', [$fromAt, $toAt])
            ->whereIn('t.payment_status', NetSales::postedPaymentStatuses())
            ->selectRaw('t.id as tx_id')
            ->selectRaw('t.voucher_campaign_id as voucher_campaign_id')
            ->selectRaw('COALESCE(t.voucher_discount_amount, 0) as voucher_discount_amount')
            ->selectRaw('COALESCE(t.refunded_amount, 0) as refunded_amount')
            ->selectRaw('COALESCE(SUM('.NetSales::itemNetExpr('ti').'), 0) as item_net')
            ->groupBy('tx_id', 'voucher_campaign_id', 'voucher_discount_amount', 'refunded_amount');

        $totalTx = (int) DB::query()->fromSub($sub, 'x')->count();

        $avgWithoutVoucher = (float) DB::query()
            ->fromSub($sub, 'x')
            ->whereNull('x.voucher_campaign_id')
            ->selectRaw('COALESCE(AVG('.NetSales::netPerTransactionExpr('x.item_net', 'x.refunded_amount').'), 0) as avg_net')
            ->value('avg_net');

        $rows = DB::query()
            ->fromSub($sub, 'x')
            ->whereNotNull('x.voucher_campaign_id')
            ->selectRaw('x.voucher_campaign_id')
            ->selectRaw('COUNT(*) as tx_count')
            ->selectRaw('COALESCE(SUM(x.voucher_discount_amount), 0) as voucher_discount_sum')
            ->selectRaw('COALESCE(AVG('.NetSales::netPerTransactionExpr('x.item_net', 'x.refunded_amount').'), 0) as avg_total')
            ->groupBy('x.voucher_campaign_id')
            ->orderByDesc('tx_count')
            ->get();

        $campaignNames = VoucherCampaign::query()
            ->whereIn('id', $rows->pluck('voucher_campaign_id')->map(fn ($v) => (int) $v)->all())
            ->pluck('name', 'id');

        $rows = $rows->map(function ($row) use ($avgWithoutVoucher, $totalTx, $campaignNames) {
            $campaignId = (int) $row->voucher_campaign_id;
            $txCount = (int) $row->tx_count;
            $conversion = $totalTx > 0 ? ($txCount / $totalTx) * 100 : 0;

            return [
                'campaign_id' => $campaignId,
                'campaign_name' => (string) ($campaignNames[$campaignId] ?? '-'),
                'tx_count' => $txCount,
                'discount_sum' => (int) $row->voucher_discount_sum,
                'avg_total' => (float) $row->avg_total,
                'avg_without_voucher' => (float) $avgWithoutVoucher,
                'aov_impact' => (float) $row->avg_total - (float) $avgWithoutVoucher,
                'conversion_rate' => $conversion,
            ];
        });

        $txWithVoucher = (int) $rows->sum('tx_count');
        $conversionAll = $totalTx > 0 ? ($txWithVoucher / $totalTx) * 100 : 0;
        $discountTotal = (int) $rows->sum('discount_sum');
        $avgWithVoucher = (float) ($txWithVoucher > 0 ? ($rows->sum(fn ($r) => (float) $r['avg_total'] * (int) $r['tx_count']) / $txWithVoucher) : 0);

        return view('livewire.vouchers.voucher-performance-page', [
            'rows' => $rows,
            'totalTx' => $totalTx,
            'txWithVoucher' => $txWithVoucher,
            'conversionAll' => $conversionAll,
            'avgWithoutVoucher' => $avgWithoutVoucher,
            'avgWithVoucher' => $avgWithVoucher,
            'discountTotal' => $discountTotal,
        ])->layout('layouts.app', ['title' => $this->title]);
    }
}
