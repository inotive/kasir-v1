<?php

namespace App\Livewire\Reports;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class MemberPerformanceReportPage extends Component
{
    public string $title = 'Laporan Performa Member';

    public ?string $fromDate = null;

    public ?string $toDate = null;

    public string $rangePreset = '30d';

    public string $paymentScope = 'paid';

    public array $chartSeries = [];

    public array $chartCategories = [];

    public array $regionMarkers = [];

    public function mount(): void
    {
        $this->authorizePermission('reports.performance');

        $this->setRange('30d');
    }

    public function updatedPaymentScope(): void
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

    private function refreshChart(): void
    {
        [$from, $to] = $this->dateRange();
        [$series, $categories] = $this->buildDailySeries();

        $this->chartSeries = $series;
        $this->chartCategories = $categories;

        $this->dispatch('statistics-updated', series: $series, categories: $categories);

        $markers = $this->buildRegionMarkers($from, $to)->values()->all();
        $this->regionMarkers = $markers;
        $this->dispatch('member-map-updated', markers: $markers);
    }

    private function paidStatuses(): array
    {
        return ['paid', 'settlement', 'capture', 'success'];
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

    private function itemsBaseQuery(CarbonImmutable $from, CarbonImmutable $to)
    {
        $fromAt = $from->startOfDay();
        $toAt = $to->endOfDay();

        $query = DB::table('transaction_items as ti')
            ->join('transactions as t', 't.id', '=', 'ti.transaction_id')
            ->leftJoin('product_variants as pv', 'pv.id', '=', 'ti.product_variant_id')
            ->leftJoin('members as m', 'm.id', '=', 't.member_id')
            ->leftJoin('member_regions as mr', 'mr.id', '=', 'm.member_region_id')
            ->whereBetween('t.created_at', [$fromAt, $toAt]);

        if ($this->paymentScope === 'paid') {
            $query->whereIn('t.payment_status', $this->paidStatuses());
        }

        return $query;
    }

    private function statusForMemberCount(int $memberCount): array
    {
        if ($memberCount <= 0) {
            return ['label' => 'Tidak ada', 'color' => '#94a3b8'];
        }

        if ($memberCount <= 5) {
            return ['label' => 'Rendah', 'color' => '#ef4444'];
        }

        if ($memberCount <= 20) {
            return ['label' => 'Sedang', 'color' => '#f59e0b'];
        }

        return ['label' => 'Tinggi', 'color' => '#22c55e'];
    }

    private function buildRegionMarkers(CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        $rows = $this->itemsBaseQuery($from, $to)
            ->whereNotNull('t.member_id')
            ->whereNotNull('m.member_region_id')
            ->selectRaw('m.member_region_id as region_id')
            ->selectRaw('mr.province as province')
            ->selectRaw('mr.regency as regency')
            ->selectRaw('mr.district as district')
            ->selectRaw('mr.geojson as geojson')
            ->selectRaw('COUNT(DISTINCT t.member_id) as member_count')
            ->selectRaw('COUNT(DISTINCT t.id) as tx_count')
            ->selectRaw('COALESCE(SUM(ti.subtotal), 0) as revenue')
            ->selectRaw('COALESCE(SUM(COALESCE(ti.hpp_total, (ti.quantity * COALESCE(pv.hpp, 0)), 0)), 0) as hpp')
            ->groupBy('region_id', 'province', 'regency', 'district', 'geojson')
            ->orderByDesc('revenue')
            ->get();

        return $rows->map(function ($row) {
            $memberCount = (int) $row->member_count;
            $revenue = (float) $row->revenue;
            $hpp = (float) $row->hpp;
            $profit = $revenue - $hpp;

            $status = $this->statusForMemberCount($memberCount);
            $geojson = $row->geojson ? json_decode((string) $row->geojson, true) : null;

            return [
                'region_id' => (int) $row->region_id,
                'province' => (string) $row->province,
                'regency' => (string) $row->regency,
                'district' => $row->district === null ? null : (string) $row->district,
                'geojson' => $geojson,
                'member_count' => $memberCount,
                'tx_count' => (int) $row->tx_count,
                'revenue' => round($revenue, 2),
                'hpp' => round($hpp, 2),
                'profit' => round($profit, 2),
                'margin_percent' => $revenue > 0 ? ($profit / $revenue) * 100 : 0,
                'status_label' => (string) $status['label'],
                'status_color' => (string) $status['color'],
            ];
        });
    }

    private function buildOverviewMetrics(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $rows = $this->itemsBaseQuery($from, $to)
            ->selectRaw('t.member_id')
            ->selectRaw('COALESCE(SUM(ti.subtotal), 0) as revenue')
            ->selectRaw('COALESCE(SUM(COALESCE(ti.hpp_total, (ti.quantity * COALESCE(pv.hpp, 0)), 0)), 0) as hpp')
            ->selectRaw('COALESCE(SUM(ti.quantity), 0) as qty')
            ->selectRaw('COUNT(DISTINCT t.id) as tx_count')
            ->selectRaw('COUNT(*) as lines_count')
            ->selectRaw('COALESCE(SUM(CASE WHEN ti.hpp_unit IS NOT NULL OR (ti.product_variant_id IS NOT NULL AND COALESCE(pv.hpp, 0) > 0) THEN 1 ELSE 0 END), 0) as hpp_estimated_lines')
            ->groupBy('t.member_id')
            ->get();

        $members = $rows->whereNotNull('member_id');
        $nonMembers = $rows->whereNull('member_id');

        $memberRevenue = (float) $members->sum('revenue');
        $memberHpp = (float) $members->sum('hpp');
        $memberProfit = $memberRevenue - $memberHpp;
        $memberTxCount = (int) $members->sum('tx_count');
        $memberQty = (int) $members->sum('qty');
        $memberLines = (int) $members->sum('lines_count');
        $memberHppCoverage = $memberLines > 0
            ? ((int) $members->sum('hpp_estimated_lines') / $memberLines) * 100
            : 0;

        $nonMemberRevenue = (float) $nonMembers->sum('revenue');
        $totalRevenue = $memberRevenue + $nonMemberRevenue;
        $memberShare = $totalRevenue > 0 ? ($memberRevenue / $totalRevenue) * 100 : 0;

        $activeMembers = $members->filter(fn ($r) => (int) $r->member_id > 0)->count();
        $repeatMembers = $members->filter(fn ($r) => (int) $r->tx_count >= 2)->count();
        $repeatRate = $activeMembers > 0 ? ($repeatMembers / $activeMembers) * 100 : 0;

        $avgOrder = $memberTxCount > 0 ? $memberRevenue / $memberTxCount : 0;
        $marginPercent = $memberRevenue > 0 ? ($memberProfit / $memberRevenue) * 100 : 0;

        return [
            'memberRevenue' => $memberRevenue,
            'memberHpp' => $memberHpp,
            'memberProfit' => $memberProfit,
            'memberMarginPercent' => $marginPercent,
            'memberTxCount' => $memberTxCount,
            'memberQty' => $memberQty,
            'activeMembers' => $activeMembers,
            'repeatRatePercent' => $repeatRate,
            'avgOrder' => $avgOrder,
            'memberSharePercent' => $memberShare,
            'hppCoveragePercent' => $memberHppCoverage,
        ];
    }

    private function buildTopMembers(CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        $canViewPii = $this->canViewMemberPii();

        $query = $this->itemsBaseQuery($from, $to)
            ->whereNotNull('t.member_id')
            ->selectRaw('t.member_id')
            ->selectRaw('m.name as member_name')
            ->selectRaw($canViewPii ? 'm.phone as member_phone' : "'' as member_phone")
            ->selectRaw($canViewPii ? 'm.email as member_email' : "'' as member_email")
            ->selectRaw('COUNT(DISTINCT t.id) as tx_count')
            ->selectRaw('COALESCE(SUM(ti.quantity), 0) as qty')
            ->selectRaw('COALESCE(SUM(ti.subtotal), 0) as revenue')
            ->selectRaw('COALESCE(SUM(COALESCE(ti.hpp_total, (ti.quantity * COALESCE(pv.hpp, 0)), 0)), 0) as hpp')
            ->selectRaw('MAX(t.created_at) as last_purchase_at')
            ->groupBy('t.member_id', 'member_name', 'member_phone', 'member_email')
            ->orderByDesc('revenue')
            ->limit(50)
            ->get();

        return $query->map(function ($row) {
            $revenue = (float) $row->revenue;
            $hpp = (float) $row->hpp;
            $profit = $revenue - $hpp;
            $txCount = (int) $row->tx_count;

            return [
                'member_id' => (int) $row->member_id,
                'name' => (string) $row->member_name,
                'phone' => $row->member_phone,
                'email' => $row->member_email,
                'tx_count' => $txCount,
                'qty' => (int) $row->qty,
                'revenue' => $revenue,
                'hpp' => $hpp,
                'profit' => $profit,
                'margin_percent' => $revenue > 0 ? ($profit / $revenue) * 100 : 0,
                'avg_order' => $txCount > 0 ? $revenue / $txCount : 0,
                'last_purchase_at' => $row->last_purchase_at,
            ];
        });
    }

    private function buildDailySeries(): array
    {
        [$from, $to] = $this->dateRange();

        $rows = $this->itemsBaseQuery($from, $to)
            ->selectRaw('DATE(t.created_at) as day')
            ->selectRaw('CASE WHEN t.member_id IS NULL THEN 0 ELSE 1 END as is_member')
            ->selectRaw('COALESCE(SUM(ti.subtotal), 0) as revenue')
            ->groupByRaw('DATE(t.created_at), is_member')
            ->orderByRaw('DATE(t.created_at) asc')
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $day = (string) $row->day;
            $isMember = (int) $row->is_member;
            $map[$day][$isMember] = (float) $row->revenue;
        }

        $categories = [];
        $membersData = [];
        $nonMembersData = [];

        $cursor = CarbonImmutable::parse($from->format('Y-m-d'));
        $end = CarbonImmutable::parse($to->format('Y-m-d'));

        while ($cursor->lessThanOrEqualTo($end)) {
            $dayKey = $cursor->format('Y-m-d');
            $categories[] = $dayKey;

            $membersRevenue = (float) ($map[$dayKey][1] ?? 0);
            $nonMembersRevenue = (float) ($map[$dayKey][0] ?? 0);

            $membersData[] = round($membersRevenue, 2);
            $nonMembersData[] = round($nonMembersRevenue, 2);

            $cursor = $cursor->addDay();
        }

        return [
            [
                [
                    'name' => 'Revenue Member',
                    'data' => $membersData,
                ],
                [
                    'name' => 'Revenue Non-Member',
                    'data' => $nonMembersData,
                ],
            ],
            $categories,
        ];
    }

    public function render(): View
    {
        $this->authorizePermission('reports.performance');

        [$from, $to] = $this->dateRange();

        $overview = $this->buildOverviewMetrics($from, $to);
        $topMembers = $this->buildTopMembers($from, $to);

        return view('livewire.reports.member-performance-report-page', [
            'overview' => $overview,
            'topMembers' => $topMembers,
            'canViewPii' => $this->canViewMemberPii(),
            'regionMarkers' => $this->regionMarkers,
            'chartSeries' => $this->chartSeries,
            'chartCategories' => $this->chartCategories,
        ])->layout('layouts.app', ['title' => $this->title]);
    }

    private function canViewMemberPii(): bool
    {
        $user = auth()->user();

        return (bool) (($user && method_exists($user, 'can')) ? ($user->can('members.pii.view') || $user->can('transactions.pii.view')) : false);
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
