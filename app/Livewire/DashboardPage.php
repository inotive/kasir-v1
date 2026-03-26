<?php

namespace App\Livewire;

use App\Models\MonthlyRevenueTarget;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Support\Finance\NetSales;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Livewire\Component;

class DashboardPage extends Component
{
    public string $title = 'E-commerce Dashboard';

    public int $transactionsCount = 0;

    public float $transactionsDeltaPercent = 0.0;

    public bool $transactionsDeltaUp = true;

    public float $monthlyTargetProgressPercent = 0.0;

    public float $monthlyTargetDeltaPercent = 0.0;

    public bool $monthlyTargetDeltaUp = true;

    public int $monthlyTargetAmount = 0;

    public int $monthlyRevenueAmount = 0;

    public int $todayRevenueAmount = 0;

    public array $statisticsSeries = [];

    public array $statisticsCategories = [];

    public ?string $statisticsFrom = null;

    public ?string $statisticsTo = null;

    public array $bestSellingProducts = [];

    public array $latestTransactions = [];

    public function mount(): void
    {
        $this->authorize('dashboard.access');

        $now = now();
        $this->transactionsCount = $this->getTransactionsCount($now);

        [$this->transactionsDeltaPercent, $this->transactionsDeltaUp] = $this->getTransactionsDelta($now);

        $this->todayRevenueAmount = $this->getRevenueBetween($now->copy()->startOfDay(), $now->copy()->endOfDay());

        $this->loadMonthlyTargetWidget();

        $this->statisticsFrom = $now->copy()->subDays(6)->toDateString();
        $this->statisticsTo = $now->toDateString();
        $this->loadStatisticsForRange();

        $this->bestSellingProducts = $this->getBestSellingProducts($now);
        $this->latestTransactions = $this->getLatestTransactions();
    }

    public function render(): View
    {
        return view('livewire.dashboard-page')
            ->layout('layouts.app', ['title' => $this->title]);
    }

    public function setStatisticsRange(string $from, string $to): void
    {
        $this->statisticsFrom = $from;
        $this->statisticsTo = $to;
        $this->loadStatisticsForRange();
    }

    protected function getTransactionsCount(Carbon $now): int
    {
        $todayStart = $now->copy()->startOfDay();
        $todayEnd = $now->copy()->endOfDay();

        return Transaction::query()
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->count();
    }

    protected function getTransactionsDelta(Carbon $now): array
    {
        $todayStart = $now->copy()->startOfDay();
        $todayEnd = $now->copy()->endOfDay();

        $yesterdayStart = $todayStart->copy()->subDay();
        $yesterdayEnd = $todayEnd->copy()->subDay();

        $todayCount = Transaction::query()
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->count();

        $yesterdayCount = Transaction::query()
            ->whereBetween('created_at', [$yesterdayStart, $yesterdayEnd])
            ->count();

        $delta = $todayCount - $yesterdayCount;
        $percent = $this->calculateDeltaPercent($todayCount, $yesterdayCount);

        return [$percent, $delta >= 0];
    }

    protected function getRevenueBetween(Carbon $from, Carbon $to): int
    {
        return (int) round(NetSales::netSalesBetween($from, $to));
    }

    protected function calculatePercent(int $value, int $target): float
    {
        if ($target <= 0) {
            return 0.0;
        }

        return min(100.0, round(($value / $target) * 100, 2));
    }

    protected function calculateDeltaPercent(int $current, int $previous): float
    {
        if ($previous <= 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }

    protected function formatCurrency(int|float|string $amount): string
    {
        if (is_string($amount) && is_numeric($amount)) {
            $amount = str_contains($amount, '.') ? (float) $amount : (int) $amount;
        }

        $decimals = is_float($amount) ? 2 : 0;

        return 'Rp'.number_format((float) $amount, $decimals, ',', '.');
    }

    protected function getBestSellingProducts(Carbon $now): array
    {
        $from = $now->copy()->startOfMonth();
        $to = $now->copy()->endOfMonth();

        $rows = TransactionItem::query()
            ->selectRaw('product_id, SUM(quantity) as sold')
            ->whereHas('transaction', function ($query) use ($from, $to) {
                $query->whereIn('payment_status', ['paid', 'settlement', 'capture', 'success', 'partial_refund'])
                    ->whereBetween('created_at', [$from, $to]);
            })
            ->groupBy('product_id')
            ->orderByDesc('sold')
            ->with(['product'])
            ->limit(5)
            ->get();

        return $rows->map(function (TransactionItem $item): array {
            $product = $item->product;

            return [
                'name' => $product?->name ?? '-',
                'image' => $product?->image ?? '/images/product/product-01.jpg',
                'sold' => (int) ($item->getAttribute('sold') ?? 0),
            ];
        })->all();
    }

    protected function getLatestTransactions(): array
    {
        $items = Transaction::query()
            ->with(['member'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return $items->map(function (Transaction $transaction): array {
            $customerName = $transaction->member?->name ?: ($transaction->name ?: '-');
            $customerPhone = $transaction->phone ?: '-';

            return [
                'code' => (string) $transaction->code,
                'customer' => $customerName,
                'phone' => $customerPhone,
                'order_type' => (string) $transaction->order_type,
                'total' => $this->formatCurrency($transaction->total),
                'payment_status' => (string) $transaction->payment_status,
                'created_at' => optional($transaction->created_at)->format('d M Y H:i') ?? '-',
            ];
        })->all();
    }

    protected function loadMonthlyTargetWidget(): void
    {
        $now = now();

        $monthStart = $now->copy()->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        $this->monthlyRevenueAmount = $this->getRevenueBetween($monthStart, $monthEnd);

        $lastMonthStart = $monthStart->copy()->subMonthNoOverflow()->startOfMonth();
        $lastMonthEnd = $lastMonthStart->copy()->endOfMonth();
        $lastMonthRevenue = $this->getRevenueBetween($lastMonthStart, $lastMonthEnd);

        $target = MonthlyRevenueTarget::query()
            ->where('year', $now->year)
            ->where('month', $now->month)
            ->value('amount');

        $this->monthlyTargetAmount = (int) ($target ?? 0);

        $this->monthlyTargetProgressPercent = $this->calculatePercent($this->monthlyRevenueAmount, $this->monthlyTargetAmount);
        $this->monthlyTargetDeltaPercent = $this->calculateDeltaPercent($this->monthlyRevenueAmount, $lastMonthRevenue);
        $this->monthlyTargetDeltaUp = $this->monthlyRevenueAmount >= $lastMonthRevenue;
        $this->dispatch('monthly-target-updated', progressPercent: $this->monthlyTargetProgressPercent);
    }

    protected function loadStatisticsForRange(): void
    {
        if (empty($this->statisticsFrom) || empty($this->statisticsTo)) {
            return;
        }

        $from = Carbon::parse($this->statisticsFrom)->startOfDay();
        $to = Carbon::parse($this->statisticsTo)->endOfDay();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        $days = $from->diffInDays($to) + 1;

        if ($days <= 90) {
            $rows = NetSales::netSalesByDay($from, $to);

            $categories = [];
            $revenue = [];

            $cursor = $from->copy()->startOfDay();
            while ($cursor->lte($to)) {
                $key = $cursor->toDateString();
                $categories[] = $cursor->format('d M');
                $revenue[] = (int) round((float) ($rows[$key] ?? 0));
                $cursor->addDay();
            }

            $this->statisticsCategories = $categories;
            $this->statisticsSeries = [
                ['name' => 'Revenue', 'data' => $revenue],
            ];
            $this->dispatch('statistics-updated', series: $this->statisticsSeries, categories: $this->statisticsCategories);

            return;
        }

        $driver = Transaction::query()->getConnection()->getDriverName();
        $bucketExpr = $driver === 'sqlite'
            ? "strftime('%Y-%m-01', t.created_at)"
            : 'DATE_FORMAT(t.created_at, "%Y-%m-01")';

        $rows = NetSales::netSalesByMonth($from, $to);

        $categories = [];
        $revenue = [];

        $cursor = $from->copy()->startOfMonth();
        $end = $to->copy()->startOfMonth();
        while ($cursor->lte($end)) {
            $key = $cursor->format('Y-m-01');
            $categories[] = $cursor->format('M Y');
            $revenue[] = (int) round((float) ($rows[$key] ?? 0));
            $cursor->addMonthNoOverflow();
        }

        $this->statisticsCategories = $categories;
        $this->statisticsSeries = [
            ['name' => 'Revenue', 'data' => $revenue],
        ];
        $this->dispatch('statistics-updated', series: $this->statisticsSeries, categories: $this->statisticsCategories);
    }
}
