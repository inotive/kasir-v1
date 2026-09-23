<?php

namespace App\Livewire\Reports;

use App\Models\Tenant;
use App\Support\Finance\NetSales;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ItemSalesReportPage extends Component
{
    private const PIE_LIMIT = 8;

    public string $title = 'Penjualan per Item';

    public ?string $fromDate = null;

    public ?string $toDate = null;

    public string $rangePreset = '30d';

    public array $chartLabels = [];

    public array $chartSeries = [];

    public function mount(): void
    {
        $this->authorizePermission('reports.sales');

        $this->setRange('30d');
        $this->refreshChart();
    }

    public function updatedFromDate(): void
    {
        $this->refreshChart();
    }

    public function updatedToDate(): void
    {
        $this->refreshChart();
    }

    public function setTransactionsRange(?string $from, ?string $to): void
    {
        if (! $from || ! $to) {
            return;
        }

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $this->fromDate = $from;
        $this->toDate = $to;
        $this->rangePreset = 'custom';
        $this->refreshChart();
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

        $this->fromDate = $from->format('Y-m-d');
        $this->toDate = $to->format('Y-m-d');
        $this->rangePreset = $preset;
        $this->refreshChart();
    }

    private function dateRange(): array
    {
        try {
            $from = $this->fromDate ? CarbonImmutable::parse($this->fromDate) : CarbonImmutable::now();
        } catch (\Throwable) {
            $from = CarbonImmutable::now();
        }

        try {
            $to = $this->toDate ? CarbonImmutable::parse($this->toDate) : CarbonImmutable::now();
        } catch (\Throwable) {
            $to = CarbonImmutable::now();
        }

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }

    private function itemsBaseQueryForRange(CarbonImmutable $from, CarbonImmutable $to)
    {
        $query = DB::table('transaction_items as ti')
            ->join('transactions as t', 't.id', '=', 'ti.transaction_id')
            ->join('products as p', 'p.id', '=', 'ti.product_id')
            ->whereBetween('t.created_at', [$from->startOfDay(), $to->endOfDay()])
            ->whereIn('t.payment_status', NetSales::postedPaymentStatuses());

        Tenant::scopeQuery($query, 't.tenant_id');

        return $query;
    }

    private function buildItemRows(CarbonImmutable $from, CarbonImmutable $to)
    {
        return $this->itemsBaseQueryForRange($from, $to)
            ->selectRaw('ti.product_id')
            ->selectRaw('p.name as product_name')
            ->selectRaw('COALESCE(SUM(ti.quantity), 0) as qty')
            ->selectRaw('COALESCE(SUM('.NetSales::itemNetExpr('ti').'), 0) as revenue')
            ->groupBy('ti.product_id', 'product_name')
            ->orderByDesc('revenue')
            ->get();
    }

    private function refreshChart(): void
    {
        [$from, $to] = $this->dateRange();
        $rows = $this->buildItemRows($from, $to);

        $top = $rows->take(self::PIE_LIMIT);
        $rest = $rows->slice(self::PIE_LIMIT);

        $labels = $top->pluck('product_name')->map(fn ($v) => (string) $v)->values()->all();
        $series = $top->pluck('revenue')->map(fn ($v) => (float) $v)->values()->all();

        if ($rest->isNotEmpty()) {
            $labels[] = 'Lainnya';
            $series[] = (float) $rest->sum('revenue');
        }

        $this->chartLabels = $labels;
        $this->chartSeries = $series;

        $this->dispatch('item-sales-updated', series: $series, labels: $labels);
    }

    public function render(): View
    {
        $this->authorizePermission('reports.sales');

        [$from, $to] = $this->dateRange();
        $rows = $this->buildItemRows($from, $to);

        $totalRevenue = (float) $rows->sum('revenue');
        $totalQty = (int) $rows->sum('qty');

        $detailRows = $rows->map(function ($row) use ($totalRevenue) {
            $revenue = (float) $row->revenue;

            return [
                'product_name' => (string) $row->product_name,
                'qty' => (int) $row->qty,
                'revenue' => $revenue,
                'percent' => $totalRevenue > 0 ? ($revenue / $totalRevenue) * 100 : 0,
            ];
        });

        return view('livewire.reports.item-sales-report-page', [
            'detailRows' => $detailRows,
            'totalRevenue' => $totalRevenue,
            'totalQty' => $totalQty,
            'topProductName' => (string) ($rows->first()->product_name ?? '-'),
        ])->layout('layouts.app', ['title' => $this->title]);
    }

    private function authorizePermission(string $permission): void
    {
        $permission = trim($permission);
        if ($permission === '') {
            abort(403);
        }

        $user = auth()->user();
        if (! $user || ! method_exists($user, 'can') || ! $user->can($permission)) {
            abort(403);
        }
    }
}
