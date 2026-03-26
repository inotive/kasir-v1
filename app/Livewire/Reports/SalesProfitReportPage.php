<?php

namespace App\Livewire\Reports;

use App\Models\MonthlyRevenueTarget;
use App\Support\Finance\NetSales;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class SalesProfitReportPage extends Component
{
    public string $title = 'Laporan Penjualan & Laba';

    public ?string $fromDate = null;

    public ?string $toDate = null;

    public string $rangePreset = '30d';

    public array $chartSeries = [];

    public array $chartCategories = [];

    public int $targetYear = 0;

    public int $targetMonth = 0;

    public function mount(): void
    {
        $this->authorizePermission('reports.sales');

        $now = CarbonImmutable::now();
        $this->targetYear = (int) $now->year;
        $this->targetMonth = (int) $now->month;

        $this->setRange('30d');
        $this->refreshChart();
    }

    public function updatedTargetYear(): void
    {
        $this->targetYear = max(2000, min(2100, (int) $this->targetYear));
    }

    public function updatedTargetMonth(): void
    {
        $this->targetMonth = max(1, min(12, (int) $this->targetMonth));
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

    private function refreshChart(): void
    {
        [$series, $categories] = $this->buildDailySeries();

        $this->chartSeries = $series;
        $this->chartCategories = $categories;

        $this->dispatch('statistics-updated', series: $series, categories: $categories);
    }

    private function paidStatuses(): array
    {
        return NetSales::postedPaymentStatuses();
    }

    private function dateRange(): array
    {
        $from = $this->fromDate ? CarbonImmutable::parse($this->fromDate) : CarbonImmutable::now();
        $to = $this->toDate ? CarbonImmutable::parse($this->toDate) : CarbonImmutable::now();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        $days = (int) $from->diffInDays($to) + 1;

        return [$from, $to, $days];
    }

    private function transactionsBaseQuery(CarbonImmutable $from, CarbonImmutable $to)
    {
        $fromAt = $from->startOfDay();
        $toAt = $to->endOfDay();

        $query = DB::table('transactions as t')
            ->whereBetween('t.created_at', [$fromAt, $toAt]);

        $query->whereIn('t.payment_status', $this->paidStatuses());

        return $query;
    }

    private function itemsBaseQuery(CarbonImmutable $from, CarbonImmutable $to)
    {
        $fromAt = $from->startOfDay();
        $toAt = $to->endOfDay();

        $query = DB::table('transaction_items as ti')
            ->join('transactions as t', 't.id', '=', 'ti.transaction_id')
            ->leftJoin('product_variants as pv', 'pv.id', '=', 'ti.product_variant_id')
            ->whereBetween('t.created_at', [$fromAt, $toAt]);

        $query->whereIn('t.payment_status', $this->paidStatuses());

        return $query;
    }

    private function cogsInventoryBaseQuery(CarbonImmutable $from, CarbonImmutable $to)
    {
        $fromAt = $from->startOfDay();
        $toAt = $to->endOfDay();

        $query = DB::table('inventory_movements as im')
            ->join('transactions as t', 't.id', '=', 'im.reference_id')
            ->where('im.reference_type', 'transactions')
            ->whereIn('im.type', ['sale_consumption', 'sale_reversal'])
            ->whereBetween('t.created_at', [$fromAt, $toAt]);

        $query->whereIn('t.payment_status', $this->paidStatuses());

        return $query;
    }

    private function inventoryMovementsPeriodBaseQuery(CarbonImmutable $from, CarbonImmutable $to)
    {
        $fromAt = $from->startOfDay();
        $toAt = $to->endOfDay();

        return DB::table('inventory_movements as im')
            ->where(function ($q) use ($fromAt, $toAt): void {
                $q->where(function ($w) use ($fromAt, $toAt): void {
                    $w->whereNotNull('im.happened_at')->whereBetween('im.happened_at', [$fromAt, $toAt]);
                })->orWhere(function ($w) use ($fromAt, $toAt): void {
                    $w->whereNull('im.happened_at')->whereBetween('im.created_at', [$fromAt, $toAt]);
                });
            });
    }

    private function operatingExpensesTotal(CarbonImmutable $from, CarbonImmutable $to): float
    {
        return (float) DB::table('operating_expenses')
            ->whereDate('expense_date', '>=', $from->format('Y-m-d'))
            ->whereDate('expense_date', '<=', $to->format('Y-m-d'))
            ->sum('amount');
    }

    private function computePeriodMetrics(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $tx = $this->transactionsBaseQuery($from, $to)
            ->selectRaw('COUNT(*) as tx_count')
            ->selectRaw('COALESCE(SUM(t.total), 0) as net_total')
            ->selectRaw('COALESCE(SUM(t.subtotal), 0) as tx_subtotal')
            ->selectRaw('COALESCE(SUM(t.tax_amount), 0) as tax_total')
            ->selectRaw('COALESCE(SUM(COALESCE(t.payment_fee_amount, 0)), 0) as payment_fee_total')
            ->selectRaw('COALESCE(SUM(t.rounding_amount), 0) as rounding_total')
            ->first();

        $items = $this->itemsBaseQuery($from, $to)
            ->selectRaw('COUNT(*) as lines_count')
            ->selectRaw('COALESCE(SUM(ti.quantity), 0) as qty_sum')
            ->selectRaw('COALESCE(SUM('.NetSales::itemNetExpr('ti').'), 0) as revenue_gross')
            ->selectRaw('COALESCE(SUM(COALESCE(ti.voucher_discount_amount, 0)), 0) as voucher_discount_total')
            ->selectRaw('COALESCE(SUM(COALESCE(ti.manual_discount_amount, 0)), 0) as manual_discount_total')
            ->selectRaw('COALESCE(SUM(COALESCE(ti.hpp_total, (ti.quantity * COALESCE(pv.hpp, 0)), 0)), 0) as hpp')
            ->selectRaw('COALESCE(SUM(CASE WHEN ti.hpp_unit IS NOT NULL THEN 1 ELSE 0 END), 0) as hpp_snap_lines')
            ->selectRaw('COALESCE(SUM(CASE WHEN ti.hpp_unit IS NOT NULL OR (ti.product_variant_id IS NOT NULL AND COALESCE(pv.hpp, 0) > 0) THEN 1 ELSE 0 END), 0) as hpp_estimated_lines')
            ->first();

        $cogs = $this->cogsInventoryBaseQuery($from, $to)
            ->selectRaw('COALESCE(SUM((-im.quantity) * COALESCE(im.unit_cost, 0)), 0) as cogs_inventory')
            ->selectRaw('COUNT(*) as movement_lines')
            ->selectRaw('COALESCE(SUM(CASE WHEN im.unit_cost IS NOT NULL THEN 1 ELSE 0 END), 0) as movement_cost_lines')
            ->first();

        $other = $this->inventoryMovementsPeriodBaseQuery($from, $to)
            ->selectRaw("COALESCE(SUM(CASE WHEN im.type = 'waste' THEN (-im.quantity) * COALESCE(im.unit_cost, 0) ELSE 0 END), 0) as waste_cost")
            ->selectRaw("COALESCE(SUM(CASE WHEN im.type = 'usage' THEN (-im.quantity) * COALESCE(im.unit_cost, 0) ELSE 0 END), 0) as usage_cost")
            ->selectRaw("COALESCE(SUM(CASE WHEN im.type = 'adjustment' THEN (-im.quantity) * COALESCE(im.unit_cost, 0) ELSE 0 END), 0) as adjustment_net_cost")
            ->selectRaw("COALESCE(SUM(CASE WHEN im.type = 'adjustment' AND im.quantity < 0 THEN (-im.quantity) * COALESCE(im.unit_cost, 0) ELSE 0 END), 0) as adjustment_out_cost")
            ->selectRaw("COALESCE(SUM(CASE WHEN im.type = 'adjustment' AND im.quantity > 0 THEN (im.quantity) * COALESCE(im.unit_cost, 0) ELSE 0 END), 0) as adjustment_in_value")
            ->selectRaw("COALESCE(SUM(CASE WHEN im.type = 'opname_adjustment' THEN (-im.quantity) * COALESCE(im.unit_cost, 0) ELSE 0 END), 0) as opname_net_cost")
            ->selectRaw("COALESCE(SUM(CASE WHEN im.type = 'opname_adjustment' AND im.quantity < 0 THEN (-im.quantity) * COALESCE(im.unit_cost, 0) ELSE 0 END), 0) as opname_loss_cost")
            ->selectRaw("COALESCE(SUM(CASE WHEN im.type = 'opname_adjustment' AND im.quantity > 0 THEN (im.quantity) * COALESCE(im.unit_cost, 0) ELSE 0 END), 0) as opname_gain_value")
            ->first();

        $revenue = (float) NetSales::netSalesBetween(Carbon::parse($from->format('Y-m-d'))->startOfDay(), Carbon::parse($to->format('Y-m-d'))->endOfDay());
        $hpp = (float) ($items->hpp ?? 0);
        $profit = $revenue - $hpp;
        $cogsSales = (float) ($cogs->cogs_inventory ?? 0);

        $wasteCost = (float) ($other->waste_cost ?? 0);
        $usageCost = (float) ($other->usage_cost ?? 0);
        $opnameNetCost = (float) ($other->opname_net_cost ?? 0);
        $opnameLossCost = (float) ($other->opname_loss_cost ?? 0);
        $opnameGainValue = (float) ($other->opname_gain_value ?? 0);
        $adjustmentNetCost = (float) ($other->adjustment_net_cost ?? 0);
        $adjustmentOutCost = (float) ($other->adjustment_out_cost ?? 0);
        $adjustmentInValue = (float) ($other->adjustment_in_value ?? 0);

        $stockLossNet = $wasteCost + $usageCost + $opnameNetCost + $adjustmentNetCost;
        $cogsTotal = $cogsSales + $stockLossNet;

        $grossProfit = $revenue - $cogsTotal;
        $paymentFees = (float) ($tx->payment_fee_total ?? 0);
        $operatingExpenses = $this->operatingExpensesTotal($from, $to);
        $netProfit = $grossProfit - $operatingExpenses;

        $txCount = (int) ($tx->tx_count ?? 0);
        $avgOrder = $txCount > 0 ? $revenue / $txCount : 0;

        $linesCount = (int) ($items->lines_count ?? 0);
        $hppCoverage = $linesCount > 0
            ? ((int) ($items->hpp_estimated_lines ?? 0) / $linesCount) * 100
            : 0;

        $movementLines = (int) ($cogs->movement_lines ?? 0);
        $movementCoverage = $movementLines > 0
            ? ((int) ($cogs->movement_cost_lines ?? 0) / $movementLines) * 100
            : 0;

        return [
            'txCount' => $txCount,
            'netTotal' => (float) ($tx->net_total ?? 0),
            'txSubtotal' => (float) ($tx->tx_subtotal ?? 0),
            'taxTotal' => (float) ($tx->tax_total ?? 0),
            'paymentFeeTotal' => $paymentFees,
            'roundingTotal' => (float) ($tx->rounding_total ?? 0),
            'linesCount' => $linesCount,
            'itemsQty' => (int) ($items->qty_sum ?? 0),
            'revenue' => $revenue,
            'revenueGrossBeforeRefund' => (float) ($items->revenue_gross ?? 0),
            'voucherDiscount' => (float) ($items->voucher_discount_total ?? 0),
            'manualDiscount' => (float) ($items->manual_discount_total ?? 0),
            'hpp' => $hpp,
            'profit' => $profit,
            'marginPercent' => $revenue > 0 ? ($profit / $revenue) * 100 : 0,
            'cogsInventory' => $cogsSales,
            'cogsTotal' => $cogsTotal,
            'stockLossNet' => $stockLossNet,
            'wasteCost' => $wasteCost,
            'usageCost' => $usageCost,
            'opnameNetCost' => $opnameNetCost,
            'opnameLossCost' => $opnameLossCost,
            'opnameGainValue' => $opnameGainValue,
            'adjustmentNetCost' => $adjustmentNetCost,
            'adjustmentOutCost' => $adjustmentOutCost,
            'adjustmentInValue' => $adjustmentInValue,
            'grossProfit' => $grossProfit,
            'grossMarginPercent' => $revenue > 0 ? ($grossProfit / $revenue) * 100 : 0,
            'operatingExpenseTotal' => $operatingExpenses,
            'netProfit' => $netProfit,
            'netMarginPercent' => $revenue > 0 ? ($netProfit / $revenue) * 100 : 0,
            'avgOrder' => $avgOrder,
            'hppCoveragePercent' => $hppCoverage,
            'cogsInventoryCoveragePercent' => $movementCoverage,
            'hppSnapshotLines' => (int) ($items->hpp_snap_lines ?? 0),
            'hppEstimatedLines' => (int) ($items->hpp_estimated_lines ?? 0),
        ];
    }

    private function metricWithDelta(float $current, float $previous): array
    {
        $delta = $current - $previous;
        $deltaPercent = $previous != 0.0 ? ($delta / $previous) * 100 : null;

        return [
            'value' => $current,
            'previous' => $previous,
            'delta' => $delta,
            'deltaPercent' => $deltaPercent,
            'deltaUp' => $delta >= 0,
        ];
    }

    private function buildMetrics(): array
    {
        [$from, $to, $days] = $this->dateRange();

        $current = $this->computePeriodMetrics($from, $to);

        $prevTo = $from->subDay();
        $prevFrom = $prevTo->subDays($days - 1);

        $previous = $this->computePeriodMetrics($prevFrom, $prevTo);

        $target = $this->buildMonthlyTargetAnalysis($this->targetYear, $this->targetMonth);

        return [
            'range' => [
                'from' => $from,
                'to' => $to,
                'days' => $days,
                'prevFrom' => $prevFrom,
                'prevTo' => $prevTo,
            ],
            'current' => $current,
            'previous' => $previous,
            'target' => $target,
            'kpi' => [
                'revenue' => $this->metricWithDelta((float) $current['revenue'], (float) $previous['revenue']),
                'cogsInventory' => $this->metricWithDelta((float) $current['cogsInventory'], (float) $previous['cogsInventory']),
                'stockLossNet' => $this->metricWithDelta((float) $current['stockLossNet'], (float) $previous['stockLossNet']),
                'cogsTotal' => $this->metricWithDelta((float) $current['cogsTotal'], (float) $previous['cogsTotal']),
                'grossProfit' => $this->metricWithDelta((float) $current['grossProfit'], (float) $previous['grossProfit']),
                'paymentFeeTotal' => $this->metricWithDelta((float) $current['paymentFeeTotal'], (float) $previous['paymentFeeTotal']),
                'operatingExpenseTotal' => $this->metricWithDelta((float) $current['operatingExpenseTotal'], (float) $previous['operatingExpenseTotal']),
                'netProfit' => $this->metricWithDelta((float) $current['netProfit'], (float) $previous['netProfit']),
                'txCount' => $this->metricWithDelta((float) $current['txCount'], (float) $previous['txCount']),
                'avgOrder' => $this->metricWithDelta((float) $current['avgOrder'], (float) $previous['avgOrder']),
                'voucherDiscount' => $this->metricWithDelta((float) $current['voucherDiscount'], (float) $previous['voucherDiscount']),
                'manualDiscount' => $this->metricWithDelta((float) $current['manualDiscount'], (float) $previous['manualDiscount']),
                'grossMarginPercent' => [
                    'value' => (float) $current['grossMarginPercent'],
                    'previous' => (float) $previous['grossMarginPercent'],
                    'delta' => (float) $current['grossMarginPercent'] - (float) $previous['grossMarginPercent'],
                    'deltaPercent' => null,
                    'deltaUp' => (float) $current['grossMarginPercent'] >= (float) $previous['grossMarginPercent'],
                ],
                'netMarginPercent' => [
                    'value' => (float) $current['netMarginPercent'],
                    'previous' => (float) $previous['netMarginPercent'],
                    'delta' => (float) $current['netMarginPercent'] - (float) $previous['netMarginPercent'],
                    'deltaPercent' => null,
                    'deltaUp' => (float) $current['netMarginPercent'] >= (float) $previous['netMarginPercent'],
                ],
            ],
        ];
    }

    private function buildMonthlyTargetAnalysis(int $year, int $month): array
    {
        $year = max(2000, min(2100, $year));
        $month = max(1, min(12, $month));

        $from = CarbonImmutable::create($year, $month, 1)->startOfMonth();
        $to = CarbonImmutable::create($year, $month, 1)->endOfMonth();

        $targetAmount = (int) (MonthlyRevenueTarget::query()
            ->where('year', $year)
            ->where('month', $month)
            ->value('amount') ?? 0);

        $monthMetrics = $this->computePeriodMetrics($from, $to);
        $revenue = (float) ($monthMetrics['revenue'] ?? 0);

        $achievementPercent = $targetAmount > 0 ? ($revenue / $targetAmount) * 100 : null;
        $gap = $targetAmount > 0 ? ($revenue - $targetAmount) : null;

        return [
            'label' => $from->translatedFormat('F Y'),
            'year' => $year,
            'month' => $month,
            'targetAmount' => $targetAmount,
            'revenueAmount' => $revenue,
            'hasAnyTarget' => $targetAmount > 0,
            'achievementPercent' => $achievementPercent,
            'gapAmount' => $gap,
        ];
    }

    private function buildDailySeries(): array
    {
        [$from, $to] = $this->dateRange();

        $byDay = collect(NetSales::netSalesByDay(Carbon::parse($from->format('Y-m-d'))->startOfDay(), Carbon::parse($to->format('Y-m-d'))->endOfDay()));

        $cogsRows = $this->cogsInventoryBaseQuery($from, $to)
            ->selectRaw('DATE(t.created_at) as day')
            ->selectRaw('COALESCE(SUM((-im.quantity) * COALESCE(im.unit_cost, 0)), 0) as cogs_inventory')
            ->groupByRaw('DATE(t.created_at)')
            ->orderByRaw('DATE(t.created_at) asc')
            ->get();

        $cogsByDay = $cogsRows->keyBy('day');

        $otherRows = $this->inventoryMovementsPeriodBaseQuery($from, $to)
            ->whereIn('im.type', ['waste', 'usage', 'opname_adjustment', 'adjustment'])
            ->selectRaw('DATE(COALESCE(im.happened_at, im.created_at)) as day')
            ->selectRaw('COALESCE(SUM((-im.quantity) * COALESCE(im.unit_cost, 0)), 0) as other_cost')
            ->groupByRaw('DATE(COALESCE(im.happened_at, im.created_at))')
            ->orderByRaw('DATE(COALESCE(im.happened_at, im.created_at)) asc')
            ->get()
            ->keyBy('day');

        $categories = [];
        $revenueData = [];
        $cogsTotalData = [];
        $grossProfitData = [];

        $cursor = CarbonImmutable::parse($from->format('Y-m-d'));
        $end = CarbonImmutable::parse($to->format('Y-m-d'));

        while ($cursor->lessThanOrEqualTo($end)) {
            $dayKey = $cursor->format('Y-m-d');
            $categories[] = $dayKey;

            $revenue = (float) ($byDay[$dayKey] ?? 0);
            $cogsSales = (float) (($cogsByDay[$dayKey]->cogs_inventory ?? 0));
            $otherCost = (float) (($otherRows[$dayKey]->other_cost ?? 0));
            $cogsTotal = $cogsSales + $otherCost;

            $revenueData[] = round($revenue, 2);
            $cogsTotalData[] = round($cogsTotal, 2);
            $grossProfitData[] = round($revenue - $cogsTotal, 2);

            $cursor = $cursor->addDay();
        }

        return [
            [
                [
                    'name' => 'Omzet (Net Sales)',
                    'data' => $revenueData,
                ],
                [
                    'name' => 'COGS + Loss Stok',
                    'data' => $cogsTotalData,
                ],
                [
                    'name' => 'Laba Kotor (Setelah Loss)',
                    'data' => $grossProfitData,
                ],
            ],
            $categories,
        ];
    }

    private function buildDailyTable(CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        $netByDay = NetSales::netSalesByDay(Carbon::parse($from->format('Y-m-d'))->startOfDay(), Carbon::parse($to->format('Y-m-d'))->endOfDay());

        $rows = $this->itemsBaseQuery($from, $to)
            ->selectRaw('DATE(t.created_at) as day')
            ->selectRaw('COUNT(DISTINCT t.id) as tx_count')
            ->selectRaw('COALESCE(SUM(ti.quantity), 0) as items_qty')
            ->groupByRaw('DATE(t.created_at)')
            ->orderByRaw('DATE(t.created_at) desc')
            ->get();

        $cogsRows = $this->cogsInventoryBaseQuery($from, $to)
            ->selectRaw('DATE(t.created_at) as day')
            ->selectRaw('COALESCE(SUM((-im.quantity) * COALESCE(im.unit_cost, 0)), 0) as cogs_inventory')
            ->groupByRaw('DATE(t.created_at)')
            ->get()
            ->keyBy('day');

        $otherRows = $this->inventoryMovementsPeriodBaseQuery($from, $to)
            ->whereIn('im.type', ['waste', 'usage', 'opname_adjustment', 'adjustment'])
            ->selectRaw('DATE(COALESCE(im.happened_at, im.created_at)) as day')
            ->selectRaw("COALESCE(SUM(CASE WHEN im.type = 'waste' THEN (-im.quantity) * COALESCE(im.unit_cost, 0) ELSE 0 END), 0) as waste_cost")
            ->selectRaw("COALESCE(SUM(CASE WHEN im.type = 'usage' THEN (-im.quantity) * COALESCE(im.unit_cost, 0) ELSE 0 END), 0) as usage_cost")
            ->selectRaw("COALESCE(SUM(CASE WHEN im.type = 'opname_adjustment' THEN (-im.quantity) * COALESCE(im.unit_cost, 0) ELSE 0 END), 0) as opname_net_cost")
            ->selectRaw("COALESCE(SUM(CASE WHEN im.type = 'adjustment' THEN (-im.quantity) * COALESCE(im.unit_cost, 0) ELSE 0 END), 0) as adjustment_net_cost")
            ->groupByRaw('DATE(COALESCE(im.happened_at, im.created_at))')
            ->get()
            ->keyBy('day');

        $feeRows = $this->transactionsBaseQuery($from, $to)
            ->selectRaw('DATE(t.created_at) as day')
            ->selectRaw('COALESCE(SUM(COALESCE(t.payment_fee_amount, 0)), 0) as payment_fee')
            ->groupByRaw('DATE(t.created_at)')
            ->get()
            ->keyBy('day');

        $expenseRows = DB::table('operating_expenses')
            ->whereDate('expense_date', '>=', $from->format('Y-m-d'))
            ->whereDate('expense_date', '<=', $to->format('Y-m-d'))
            ->selectRaw('DATE(expense_date) as day')
            ->selectRaw('COALESCE(SUM(amount), 0) as operating_expenses')
            ->groupByRaw('DATE(expense_date)')
            ->get()
            ->keyBy('day');

        return $rows->map(function ($row) use ($cogsRows, $otherRows, $feeRows, $expenseRows, $netByDay) {
            $revenue = (float) ($netByDay[(string) $row->day] ?? 0);
            $cogsSales = (float) (($cogsRows[(string) $row->day]->cogs_inventory ?? 0));
            $other = $otherRows[(string) $row->day] ?? null;
            $wasteCost = (float) (($other->waste_cost ?? 0));
            $usageCost = (float) (($other->usage_cost ?? 0));
            $opnameNetCost = (float) (($other->opname_net_cost ?? 0));
            $adjustmentNetCost = (float) (($other->adjustment_net_cost ?? 0));
            $stockLossNet = $wasteCost + $usageCost + $opnameNetCost + $adjustmentNetCost;
            $cogsTotal = $cogsSales + $stockLossNet;
            $grossProfit = $revenue - $cogsTotal;
            $paymentFees = (float) (($feeRows[(string) $row->day]->payment_fee ?? 0));
            $operatingExpenses = (float) (($expenseRows[(string) $row->day]->operating_expenses ?? 0));
            $netProfit = $grossProfit - $operatingExpenses;

            return [
                'day' => (string) $row->day,
                'tx_count' => (int) $row->tx_count,
                'items_qty' => (int) $row->items_qty,
                'revenue' => $revenue,
                'cogs_sales' => $cogsSales,
                'waste_cost' => $wasteCost,
                'usage_cost' => $usageCost,
                'opname_net_cost' => $opnameNetCost,
                'adjustment_net_cost' => $adjustmentNetCost,
                'stock_loss_net' => $stockLossNet,
                'cogs_total' => $cogsTotal,
                'gross_profit' => $grossProfit,
                'gross_margin_percent' => $revenue > 0 ? ($grossProfit / $revenue) * 100 : 0,
                'payment_fee_total' => $paymentFees,
                'operating_expense_total' => $operatingExpenses,
                'net_profit' => $netProfit,
                'net_margin_percent' => $revenue > 0 ? ($netProfit / $revenue) * 100 : 0,
            ];
        });
    }

    private function buildTopItems(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $base = $this->itemsBaseQuery($from, $to)
            ->join('products as p', 'p.id', '=', 'ti.product_id')
            ->leftJoin('product_variants as v', 'v.id', '=', 'ti.product_variant_id')
            ->selectRaw('ti.product_id, ti.product_variant_id')
            ->selectRaw("COALESCE(v.name, '-') as variant_name")
            ->selectRaw('p.name as product_name')
            ->selectRaw('COALESCE(SUM(ti.quantity), 0) as qty')
            ->selectRaw('COALESCE(SUM(ti.subtotal - COALESCE(ti.voucher_discount_amount, 0) - COALESCE(ti.manual_discount_amount, 0)), 0) as revenue')
            ->selectRaw('COALESCE(SUM(COALESCE(ti.hpp_total, (ti.quantity * COALESCE(v.hpp, 0)), 0)), 0) as hpp')
            ->groupBy('ti.product_id', 'ti.product_variant_id', 'product_name', 'variant_name');

        $topByProfit = (clone $base)
            ->selectRaw('(COALESCE(SUM(ti.subtotal - COALESCE(ti.voucher_discount_amount, 0) - COALESCE(ti.manual_discount_amount, 0)), 0) - COALESCE(SUM(COALESCE(ti.hpp_total, (ti.quantity * COALESCE(v.hpp, 0)), 0)), 0)) as profit')
            ->orderByDesc('profit')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                $revenue = (float) $row->revenue;
                $hpp = (float) $row->hpp;
                $profit = (float) ($row->profit ?? ($revenue - $hpp));

                return [
                    'product_name' => (string) $row->product_name,
                    'variant_name' => (string) $row->variant_name,
                    'qty' => (int) $row->qty,
                    'revenue' => $revenue,
                    'hpp' => $hpp,
                    'profit' => $profit,
                    'margin_percent' => $revenue > 0 ? ($profit / $revenue) * 100 : 0,
                ];
            });

        $topByRevenue = (clone $base)
            ->orderByDesc('revenue')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                $revenue = (float) $row->revenue;
                $hpp = (float) $row->hpp;
                $profit = $revenue - $hpp;

                return [
                    'product_name' => (string) $row->product_name,
                    'variant_name' => (string) $row->variant_name,
                    'qty' => (int) $row->qty,
                    'revenue' => $revenue,
                    'hpp' => $hpp,
                    'profit' => $profit,
                    'margin_percent' => $revenue > 0 ? ($profit / $revenue) * 100 : 0,
                ];
            });

        return [
            'byProfit' => $topByProfit,
            'byRevenue' => $topByRevenue,
        ];
    }

    private function buildPaymentMethods(CarbonImmutable $from, CarbonImmutable $to, float $totalRevenue): Collection
    {
        $rows = $this->itemsBaseQuery($from, $to)
            ->selectRaw("COALESCE(NULLIF(t.payment_method, ''), 'unknown') as payment_method")
            ->selectRaw('COALESCE(SUM(ti.subtotal - COALESCE(ti.voucher_discount_amount, 0) - COALESCE(ti.manual_discount_amount, 0)), 0) as revenue')
            ->groupBy('payment_method')
            ->orderByDesc('revenue')
            ->get();

        return $rows->map(function ($row) use ($totalRevenue) {
            $revenue = (float) $row->revenue;

            return [
                'payment_method' => (string) $row->payment_method,
                'revenue' => $revenue,
                'percent' => $totalRevenue > 0 ? ($revenue / $totalRevenue) * 100 : 0,
            ];
        });
    }

    public function render(): View
    {
        $this->authorizePermission('reports.sales');

        [$from, $to] = $this->dateRange();

        $metrics = $this->buildMetrics();
        $daily = $this->buildDailyTable($from, $to);
        $top = $this->buildTopItems($from, $to);
        $paymentMethods = $this->buildPaymentMethods($from, $to, (float) ($metrics['current']['revenue'] ?? 0));

        return view('livewire.reports.sales-profit-report-page', [
            'metrics' => $metrics,
            'dailyRows' => $daily,
            'topByProfit' => $top['byProfit'],
            'topByRevenue' => $top['byRevenue'],
            'paymentMethods' => $paymentMethods,
            'chartSeries' => $this->chartSeries,
            'chartCategories' => $this->chartCategories,
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
