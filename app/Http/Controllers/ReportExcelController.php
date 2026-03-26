<?php

namespace App\Http\Controllers;

use App\Exports\Inventory\LowStockExport;
use App\Exports\Inventory\StockCardExport;
use App\Exports\Inventory\ValuationExport;
use App\Exports\Reports\ManualDiscountsExport;
use App\Exports\Reports\MemberPerformanceExport;
use App\Exports\Reports\SalesProfitExport;
use App\Models\Ingredient;
use App\Models\InventoryMovement;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use App\Support\Finance\NetSales;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ReportExcelController extends Controller
{
    private function ensureExcelAvailable(): void
    {
        $excelFacade = \Maatwebsite\Excel\Facades\Excel::class;
        $fromArray = \Maatwebsite\Excel\Concerns\FromArray::class;

        if (! class_exists($excelFacade) || ! interface_exists($fromArray)) {
            abort(503, 'Fitur export Excel belum tersedia di server ini. Jalankan composer install untuk menginstal maatwebsite/excel.');
        }
    }

    private function authorizeAny(Request $request, array $permissions): void
    {
        $user = $request->user();
        if (! $user || ! method_exists($user, 'can')) {
            abort(403);
        }

        foreach ($permissions as $permission) {
            $permission = trim((string) $permission);
            if ($permission !== '' && $user->can($permission)) {
                return;
            }
        }

        abort(403);
    }

    private function canViewMemberPii(Request $request): bool
    {
        $user = $request->user();

        return (bool) (($user && method_exists($user, 'can')) ? ($user->can('members.pii.view') || $user->can('transactions.pii.view')) : false);
    }

    private function parseDate(string $value): ?CarbonImmutable
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function paidStatuses(): array
    {
        return NetSales::postedPaymentStatuses();
    }

    private function meta(string $reportTitle, string $periodLabel, array $badges = []): array
    {
        $setting = Setting::current();

        return [
            'storeName' => (string) ($setting->store_name ?? config('app.name')),
            'reportTitle' => $reportTitle,
            'periodLabel' => $periodLabel,
            'generatedAt' => CarbonImmutable::now()->format('d M Y, H:i'),
            'badges' => $badges,
        ];
    }

    public function salesProfit(Request $request)
    {
        $this->authorizeAny($request, ['reports.sales']);
        $this->ensureExcelAvailable();

        $from = $this->parseDate((string) $request->query('from')) ?? CarbonImmutable::now()->subDays(29);
        $to = $this->parseDate((string) $request->query('to')) ?? CarbonImmutable::now();
        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        $fromAt = $from->startOfDay();
        $toAt = $to->endOfDay();

        $txQuery = DB::table('transactions as t')->whereBetween('t.created_at', [$fromAt, $toAt])->whereIn('t.payment_status', $this->paidStatuses());
        $itemsQuery = DB::table('transaction_items as ti')
            ->join('transactions as t', 't.id', '=', 'ti.transaction_id')
            ->leftJoin('product_variants as pv', 'pv.id', '=', 'ti.product_variant_id')
            ->whereBetween('t.created_at', [$fromAt, $toAt])
            ->whereIn('t.payment_status', $this->paidStatuses());

        $cogsQuery = DB::table('inventory_movements as im')
            ->join('transactions as t', 't.id', '=', 'im.reference_id')
            ->where('im.reference_type', 'transactions')
            ->whereIn('im.type', ['sale_consumption', 'sale_reversal'])
            ->whereBetween('t.created_at', [$fromAt, $toAt])
            ->whereIn('t.payment_status', $this->paidStatuses());

        $tx = (clone $txQuery)
            ->selectRaw('COUNT(*) as tx_count')
            ->selectRaw('COALESCE(SUM(t.total), 0) as net_total')
            ->selectRaw('COALESCE(SUM(COALESCE(t.payment_fee_amount, 0)), 0) as payment_fee_total')
            ->first();

        $items = (clone $itemsQuery)
            ->selectRaw('COUNT(*) as lines_count')
            ->selectRaw('COALESCE(SUM(ti.quantity), 0) as qty_sum')
            ->selectRaw('COALESCE(SUM('.NetSales::itemNetExpr('ti').'), 0) as revenue_gross')
            ->selectRaw('COALESCE(SUM(COALESCE(ti.voucher_discount_amount, 0)), 0) as voucher_discount_total')
            ->selectRaw('COALESCE(SUM(COALESCE(ti.manual_discount_amount, 0)), 0) as manual_discount_total')
            ->selectRaw('COALESCE(SUM(COALESCE(ti.hpp_total, (ti.quantity * COALESCE(pv.hpp, 0)), 0)), 0) as hpp')
            ->first();

        $cogs = (clone $cogsQuery)
            ->selectRaw('COALESCE(SUM((-im.quantity) * COALESCE(im.unit_cost, 0)), 0) as cogs_inventory')
            ->first();

        $other = DB::table('inventory_movements as im')
            ->where(function ($q) use ($fromAt, $toAt): void {
                $q->where(function ($w) use ($fromAt, $toAt): void {
                    $w->whereNotNull('im.happened_at')->whereBetween('im.happened_at', [$fromAt, $toAt]);
                })->orWhere(function ($w) use ($fromAt, $toAt): void {
                    $w->whereNull('im.happened_at')->whereBetween('im.created_at', [$fromAt, $toAt]);
                });
            })
            ->selectRaw("COALESCE(SUM(CASE WHEN im.type = 'waste' THEN (-im.quantity) * COALESCE(im.unit_cost, 0) ELSE 0 END), 0) as waste_cost")
            ->selectRaw("COALESCE(SUM(CASE WHEN im.type = 'usage' THEN (-im.quantity) * COALESCE(im.unit_cost, 0) ELSE 0 END), 0) as usage_cost")
            ->selectRaw("COALESCE(SUM(CASE WHEN im.type = 'adjustment' THEN (-im.quantity) * COALESCE(im.unit_cost, 0) ELSE 0 END), 0) as adjustment_net_cost")
            ->selectRaw("COALESCE(SUM(CASE WHEN im.type = 'opname_adjustment' THEN (-im.quantity) * COALESCE(im.unit_cost, 0) ELSE 0 END), 0) as opname_net_cost")
            ->first();

        $operatingExpenses = (float) DB::table('operating_expenses')
            ->whereDate('expense_date', '>=', $from->format('Y-m-d'))
            ->whereDate('expense_date', '<=', $to->format('Y-m-d'))
            ->sum('amount');

        $revenue = (float) NetSales::netSalesBetween($fromAt, $toAt);
        $cogsSales = (float) ($cogs->cogs_inventory ?? 0);
        $stockLossNet = (float) ($other->waste_cost ?? 0) + (float) ($other->usage_cost ?? 0) + (float) ($other->opname_net_cost ?? 0) + (float) ($other->adjustment_net_cost ?? 0);
        $cogsTotal = $cogsSales + $stockLossNet;

        $grossProfit = $revenue - $cogsTotal;
        $grossMargin = $revenue > 0 ? ($grossProfit / $revenue) * 100 : 0.0;
        $netProfit = $grossProfit - $operatingExpenses;
        $netMargin = $revenue > 0 ? ($netProfit / $revenue) * 100 : 0.0;
        $txCount = (int) ($tx->tx_count ?? 0);
        $avgOrder = $txCount > 0 ? $revenue / $txCount : 0.0;

        $days = [];
        $cursor = CarbonImmutable::parse($from->format('Y-m-d'));
        $end = CarbonImmutable::parse($to->format('Y-m-d'));
        while ($cursor->lessThanOrEqualTo($end)) {
            $days[] = $cursor->format('Y-m-d');
            $cursor = $cursor->addDay();
        }

        $dailyRevenueRows = collect(NetSales::netSalesByDay($fromAt, $toAt))->all();

        $dailyCogsRows = (clone $cogsQuery)
            ->selectRaw('DATE(t.created_at) as day')
            ->selectRaw('COALESCE(SUM((-im.quantity) * COALESCE(im.unit_cost, 0)), 0) as cogs_inventory')
            ->groupBy('day')
            ->get()
            ->mapWithKeys(fn ($r) => [(string) $r->day => (float) $r->cogs_inventory])
            ->all();

        $dailyOtherRows = DB::table('inventory_movements as im')
            ->where(function ($q) use ($fromAt, $toAt): void {
                $q->where(function ($w) use ($fromAt, $toAt): void {
                    $w->whereNotNull('im.happened_at')->whereBetween('im.happened_at', [$fromAt, $toAt]);
                })->orWhere(function ($w) use ($fromAt, $toAt): void {
                    $w->whereNull('im.happened_at')->whereBetween('im.created_at', [$fromAt, $toAt]);
                });
            })
            ->whereIn('im.type', ['waste', 'usage', 'opname_adjustment', 'adjustment'])
            ->selectRaw('DATE(COALESCE(im.happened_at, im.created_at)) as day')
            ->selectRaw('COALESCE(SUM((-im.quantity) * COALESCE(im.unit_cost, 0)), 0) as other_cost')
            ->groupBy('day')
            ->get()
            ->mapWithKeys(fn ($r) => [(string) $r->day => (float) $r->other_cost])
            ->all();

        $daily = [];
        foreach ($days as $d) {
            $rev = (float) ($dailyRevenueRows[$d] ?? 0);
            $cogsSalesDay = (float) ($dailyCogsRows[$d] ?? 0);
            $loss = (float) ($dailyOtherRows[$d] ?? 0);
            $cogsDay = $cogsSalesDay + $loss;
            $daily[] = [
                'day' => $d,
                'revenue' => $rev,
                'cogsSales' => $cogsSalesDay,
                'stockLossNet' => $loss,
                'cogsTotal' => $cogsDay,
                'grossProfit' => $rev - $cogsDay,
            ];
        }

        $periodLabel = CarbonImmutable::parse($from->format('Y-m-d'))->format('d M Y').' – '.CarbonImmutable::parse($to->format('Y-m-d'))->format('d M Y');
        $meta = $this->meta('Laporan Penjualan & Laba', $periodLabel, ['Hanya Paid']);
        $filename = 'laporan-penjualan-laba_'.$from->format('Ymd').'-'.$to->format('Ymd').'.xlsx';

        $export = new SalesProfitExport($meta, [
            'txCount' => $txCount,
            'revenue' => $revenue,
            'cogsSales' => $cogsSales,
            'stockLossNet' => $stockLossNet,
            'cogsTotal' => $cogsTotal,
            'grossProfit' => $grossProfit,
            'grossMarginPercent' => $grossMargin,
            'operatingExpenseTotal' => $operatingExpenses,
            'netProfit' => $netProfit,
            'netMarginPercent' => $netMargin,
            'avgOrder' => $avgOrder,
            'voucherDiscount' => (float) ($items->voucher_discount_total ?? 0),
            'manualDiscount' => (float) ($items->manual_discount_total ?? 0),
        ], $daily);

        return Excel::download($export, $filename);
    }

    public function manualDiscounts(Request $request)
    {
        $this->authorizeAny($request, ['reports.sales']);
        $this->ensureExcelAvailable();

        $from = $this->parseDate((string) $request->query('from'));
        $to = $this->parseDate((string) $request->query('to'));
        $cashierId = $request->query('cashierId');
        $cashierId = is_numeric($cashierId) ? (int) $cashierId : null;

        $q = Transaction::query()
            ->with(['manualDiscountByUser'])
            ->where('manual_discount_amount', '>', 0)
            ->when($from, fn (Builder $b) => $b->whereDate('created_at', '>=', $from->format('Y-m-d')))
            ->when($to, fn (Builder $b) => $b->whereDate('created_at', '<=', $to->format('Y-m-d')))
            ->when($cashierId, fn (Builder $b) => $b->where('manual_discount_by_user_id', $cashierId))
            ->orderByDesc('created_at');

        $totalRows = (int) (clone $q)->count();
        $rows = (clone $q)->limit(5000)->get();

        $totalDiscount = (float) (clone $q)->sum('manual_discount_amount');
        $txCount = $totalRows;

        $periodLabel = ($from ? $from->format('d M Y') : '-').' – '.($to ? $to->format('d M Y') : '-');
        $badges = [];
        if ($cashierId) {
            $cashierName = User::query()->whereKey($cashierId)->value('name');
            if (is_string($cashierName) && trim($cashierName) !== '') {
                $badges[] = 'Kasir: '.trim($cashierName);
            }
        }

        $meta = $this->meta('Laporan Diskon Manual', $periodLabel, $badges);
        $filename = 'laporan-diskon-manual_'.now()->format('Ymd_His').'.xlsx';

        $exportRows = $rows->map(fn (Transaction $t) => [
            'created_at' => (string) ($t->created_at?->format('Y-m-d H:i:s') ?? ''),
            'code' => (string) ($t->code ?? ''),
            'channel' => (string) ($t->channel ?? ''),
            'total' => (float) ($t->total ?? 0),
            'manual_discount_amount' => (float) ($t->manual_discount_amount ?? 0),
            'cashier_name' => (string) ($t->manualDiscountByUser?->name ?? ''),
        ])->all();

        $export = new ManualDiscountsExport($meta, [
            'totalDiscount' => $totalDiscount,
            'txCount' => $txCount,
            'shownCount' => count($exportRows),
            'totalCount' => $totalRows,
        ], $exportRows);

        return Excel::download($export, $filename);
    }

    public function memberPerformance(Request $request)
    {
        $this->authorizeAny($request, ['reports.performance']);
        $this->ensureExcelAvailable();
        $canViewPii = $this->canViewMemberPii($request);

        $from = $this->parseDate((string) $request->query('from')) ?? CarbonImmutable::now()->subDays(29);
        $to = $this->parseDate((string) $request->query('to')) ?? CarbonImmutable::now();
        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        $scope = (string) ($request->query('paymentScope') ?? 'paid');
        $onlyPaid = $scope === 'paid';
        $fromAt = $from->startOfDay();
        $toAt = $to->endOfDay();

        $itemsBase = DB::table('transaction_items as ti')
            ->join('transactions as t', 't.id', '=', 'ti.transaction_id')
            ->leftJoin('product_variants as pv', 'pv.id', '=', 'ti.product_variant_id')
            ->leftJoin('members as m', 'm.id', '=', 't.member_id')
            ->whereBetween('t.created_at', [$fromAt, $toAt]);

        if ($onlyPaid) {
            $itemsBase->whereIn('t.payment_status', $this->paidStatuses());
        }

        $membersAgg = (clone $itemsBase)
            ->selectRaw('t.member_id')
            ->selectRaw('COUNT(DISTINCT t.id) as tx_count')
            ->selectRaw('COALESCE(SUM(ti.quantity), 0) as qty')
            ->selectRaw('COALESCE(SUM(ti.subtotal), 0) as revenue')
            ->selectRaw('COALESCE(SUM(COALESCE(ti.hpp_total, (ti.quantity * COALESCE(pv.hpp, 0)), 0)), 0) as hpp')
            ->groupBy('t.member_id')
            ->get();

        $membersOnly = $membersAgg->whereNotNull('member_id');
        $nonMembersOnly = $membersAgg->whereNull('member_id');

        $memberRevenue = (float) $membersOnly->sum('revenue');
        $memberHpp = (float) $membersOnly->sum('hpp');
        $memberProfit = $memberRevenue - $memberHpp;
        $memberTxCount = (int) $membersOnly->sum('tx_count');
        $activeMembers = (int) $membersOnly->filter(fn ($r) => (int) ($r->member_id ?? 0) > 0)->count();
        $repeatMembers = (int) $membersOnly->filter(fn ($r) => (int) ($r->tx_count ?? 0) >= 2)->count();
        $repeatRate = $activeMembers > 0 ? ($repeatMembers / $activeMembers) * 100 : 0.0;
        $avgOrder = $memberTxCount > 0 ? $memberRevenue / $memberTxCount : 0.0;
        $marginPercent = $memberRevenue > 0 ? ($memberProfit / $memberRevenue) * 100 : 0.0;

        $nonMemberRevenue = (float) $nonMembersOnly->sum('revenue');
        $totalRevenue = $memberRevenue + $nonMemberRevenue;
        $memberShare = $totalRevenue > 0 ? ($memberRevenue / $totalRevenue) * 100 : 0.0;

        $topMembers = (clone $itemsBase)
            ->whereNotNull('t.member_id')
            ->selectRaw('t.member_id')
            ->selectRaw('m.name as member_name')
            ->selectRaw($canViewPii ? 'm.phone as member_phone' : "'' as member_phone")
            ->selectRaw('COUNT(DISTINCT t.id) as tx_count')
            ->selectRaw('COALESCE(SUM(ti.quantity), 0) as qty')
            ->selectRaw('COALESCE(SUM(ti.subtotal), 0) as revenue')
            ->selectRaw('COALESCE(SUM(COALESCE(ti.hpp_total, (ti.quantity * COALESCE(pv.hpp, 0)), 0)), 0) as hpp')
            ->selectRaw('MAX(t.created_at) as last_purchase_at')
            ->groupBy('t.member_id', 'member_name', 'member_phone')
            ->orderByDesc('revenue')
            ->limit(200)
            ->get()
            ->map(function ($r) {
                $revenue = (float) ($r->revenue ?? 0);
                $hpp = (float) ($r->hpp ?? 0);
                $profit = $revenue - $hpp;

                return [
                    'member_id' => (int) ($r->member_id ?? 0),
                    'member_name' => (string) ($r->member_name ?? ''),
                    'member_phone' => (string) ($r->member_phone ?? ''),
                    'tx_count' => (int) ($r->tx_count ?? 0),
                    'qty' => (int) ($r->qty ?? 0),
                    'revenue' => $revenue,
                    'hpp' => $hpp,
                    'profit' => $profit,
                    'margin_percent' => $revenue > 0 ? ($profit / $revenue) * 100 : 0.0,
                    'last_purchase_at' => (string) ($r->last_purchase_at ?? ''),
                ];
            })
            ->all();

        $periodLabel = $from->format('d M Y').' – '.$to->format('d M Y');
        $badges = [$onlyPaid ? 'Hanya Paid' : 'Semua Status'];
        $meta = $this->meta('Laporan Performa Member', $periodLabel, $badges);
        $filename = 'laporan-performa-member_'.$from->format('Ymd').'-'.$to->format('Ymd').'.xlsx';

        $export = new MemberPerformanceExport($meta, [
            'memberRevenue' => $memberRevenue,
            'memberProfit' => $memberProfit,
            'memberMarginPercent' => $marginPercent,
            'memberTxCount' => $memberTxCount,
            'activeMembers' => $activeMembers,
            'repeatRatePercent' => $repeatRate,
            'avgOrder' => $avgOrder,
            'memberSharePercent' => $memberShare,
        ], $topMembers, $canViewPii);

        return Excel::download($export, $filename);
    }

    public function inventoryLowStock(Request $request)
    {
        $user = $request->user();
        if (! $user || ! ($user->can('inventory.reports.view') || $user->can('inventory.view') || $user->can('inventory.manage'))) {
            abort(403);
        }

        $this->ensureExcelAvailable();

        $search = trim((string) ($request->query('search') ?? ''));

        $stockSubquery = DB::table('inventory_movements')
            ->select('ingredient_id')
            ->selectRaw('COALESCE(SUM(quantity), 0) as stock_on_hand')
            ->groupBy('ingredient_id');

        $q = Ingredient::query()
            ->leftJoinSub($stockSubquery, 'inventory_stock', function ($join): void {
                $join->on('inventory_stock.ingredient_id', '=', 'ingredients.id');
            })
            ->select([
                'ingredients.id',
                'ingredients.name',
                'ingredients.sku',
                'ingredients.unit',
                'ingredients.reorder_level',
            ])
            ->selectRaw('COALESCE(inventory_stock.stock_on_hand, 0) as stock_on_hand')
            ->where('ingredients.is_active', true)
            ->where('ingredients.reorder_level', '>', 0)
            ->whereRaw('COALESCE(inventory_stock.stock_on_hand, 0) <= ingredients.reorder_level')
            ->when($search !== '', function (Builder $b) use ($search): void {
                $term = '%'.$search.'%';
                $b->where(function (Builder $w) use ($term): void {
                    $w->where('ingredients.name', 'like', $term)
                        ->orWhere('ingredients.sku', 'like', $term);
                });
            })
            ->orderBy('stock_on_hand')
            ->orderBy('ingredients.name');

        $rows = (clone $q)->limit(2000)->get();
        $totalRows = (int) (clone $q)->count();

        $periodLabel = CarbonImmutable::now()->format('d M Y');
        $badges = [];
        if ($search !== '') {
            $badges[] = 'Filter: '.$search;
        }

        $meta = $this->meta('Laporan Inventory: Low Stock', $periodLabel, $badges);
        $filename = 'laporan-low-stock_'.now()->format('Ymd_His').'.xlsx';

        $export = new LowStockExport($meta, [
            'shownCount' => $rows->count(),
            'totalCount' => $totalRows,
        ], $rows->map(fn ($r) => [
            'sku' => (string) ($r->sku ?? ''),
            'name' => (string) ($r->name ?? ''),
            'unit' => (string) ($r->unit ?? ''),
            'reorder_level' => (float) ($r->reorder_level ?? 0),
            'stock_on_hand' => (float) ($r->stock_on_hand ?? 0),
        ])->all());

        return Excel::download($export, $filename);
    }

    public function inventoryStockCard(Request $request)
    {
        $user = $request->user();
        if (! $user || ! ($user->can('inventory.reports.view') || $user->can('inventory.view') || $user->can('inventory.manage'))) {
            abort(403);
        }

        $this->ensureExcelAvailable();

        $ingredientId = $request->query('ingredientId');
        $ingredientId = is_numeric($ingredientId) ? (int) $ingredientId : 0;
        if ($ingredientId <= 0) {
            abort(400, 'ingredientId is required');
        }

        $from = $this->parseDate((string) $request->query('from')) ?? CarbonImmutable::now();
        $to = $this->parseDate((string) $request->query('to')) ?? CarbonImmutable::now();
        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }
        $fromDate = $from->format('Y-m-d');
        $toDate = $to->format('Y-m-d');

        $ingredient = Ingredient::query()->whereKey($ingredientId)->firstOrFail(['id', 'name', 'unit']);

        $movementsQuery = InventoryMovement::query()
            ->where('ingredient_id', $ingredientId)
            ->where(function (Builder $q) use ($fromDate): void {
                $q->where(function (Builder $x) use ($fromDate): void {
                    $x->whereNotNull('happened_at')->whereDate('happened_at', '>=', $fromDate);
                })->orWhere(function (Builder $x) use ($fromDate): void {
                    $x->whereNull('happened_at')->whereDate('created_at', '>=', $fromDate);
                });
            })
            ->where(function (Builder $q) use ($toDate): void {
                $q->where(function (Builder $x) use ($toDate): void {
                    $x->whereNotNull('happened_at')->whereDate('happened_at', '<=', $toDate);
                })->orWhere(function (Builder $x) use ($toDate): void {
                    $x->whereNull('happened_at')->whereDate('created_at', '<=', $toDate);
                });
            })
            ->orderByRaw('COALESCE(happened_at, created_at) asc')
            ->orderBy('id');

        $movements = (clone $movementsQuery)->limit(5000)->get();
        $totalRows = (int) (clone $movementsQuery)->count();

        $startingBalance = (float) InventoryMovement::query()
            ->where('ingredient_id', $ingredientId)
            ->where(function (Builder $q) use ($fromDate): void {
                $q->where(function (Builder $x) use ($fromDate): void {
                    $x->whereNotNull('happened_at')->whereDate('happened_at', '<', $fromDate);
                })->orWhere(function (Builder $x) use ($fromDate): void {
                    $x->whereNull('happened_at')->whereDate('created_at', '<', $fromDate);
                });
            })
            ->sum('quantity');

        $startingValue = (float) InventoryMovement::query()
            ->where('ingredient_id', $ingredientId)
            ->where(function (Builder $q) use ($fromDate): void {
                $q->where(function (Builder $x) use ($fromDate): void {
                    $x->whereNotNull('happened_at')->whereDate('happened_at', '<', $fromDate);
                })->orWhere(function (Builder $x) use ($fromDate): void {
                    $x->whereNull('happened_at')->whereDate('created_at', '<', $fromDate);
                });
            })
            ->sum(DB::raw('quantity * COALESCE(unit_cost, 0)'));

        $running = $startingBalance;
        $runningValue = $startingValue;
        $rows = [];
        foreach ($movements as $m) {
            $qty = (float) ($m->quantity ?? 0);
            $unitCost = (float) ($m->unit_cost ?? 0);
            $deltaValue = $qty * $unitCost;
            $running += $qty;
            $runningValue += $deltaValue;

            $rows[] = [
                'when' => (string) (($m->happened_at ?? $m->created_at)?->format('Y-m-d H:i:s') ?? ''),
                'type' => (string) ($m->type ?? ''),
                'qty' => $qty,
                'unit_cost' => $unitCost,
                'delta_value' => $deltaValue,
                'balance' => $running,
                'running_value' => $runningValue,
                'note' => (string) ($m->note ?? ''),
            ];
        }

        $periodLabel = $from->format('d M Y').' – '.$to->format('d M Y');
        $meta = $this->meta('Kartu Stok', $periodLabel, ['Bahan: '.(string) $ingredient->name]);
        $filename = 'kartu-stok_'.$ingredientId.'_'.$from->format('Ymd').'-'.$to->format('Ymd').'.xlsx';

        $export = new StockCardExport($meta, [
            'shownCount' => count($rows),
            'totalCount' => $totalRows,
        ], $rows, [
            'name' => (string) $ingredient->name,
            'unit' => (string) $ingredient->unit,
        ], $startingBalance, $startingValue);

        return Excel::download($export, $filename);
    }

    public function inventoryValuation(Request $request)
    {
        $user = $request->user();
        if (! $user || ! ($user->can('inventory.reports.view') || $user->can('inventory.view') || $user->can('inventory.manage'))) {
            abort(403);
        }

        $this->ensureExcelAvailable();

        $includeZero = filter_var($request->query('includeZero', true), FILTER_VALIDATE_BOOL);
        $search = trim((string) ($request->query('search') ?? ''));

        $agg = DB::table('ingredients as i')
            ->leftJoin('inventory_movements as im', function ($join): void {
                $join->on('im.ingredient_id', '=', 'i.id');
            })
            ->where('i.is_active', true)
            ->when($search !== '', function ($q) use ($search): void {
                $term = '%'.$search.'%';
                $q->where(function ($w) use ($term): void {
                    $w->where('i.name', 'like', $term)
                        ->orWhere('i.sku', 'like', $term);
                });
            })
            ->selectRaw('i.id, i.name, i.sku, i.unit, i.cost_price')
            ->selectRaw('COALESCE(SUM(im.quantity), 0) as stock_on_hand')
            ->selectRaw('COALESCE(SUM(im.quantity * COALESCE(im.unit_cost, i.cost_price)), 0) as stock_value')
            ->selectRaw('COALESCE(COUNT(im.id), 0) as movement_lines')
            ->selectRaw('COALESCE(SUM(CASE WHEN im.unit_cost IS NOT NULL THEN 1 ELSE 0 END), 0) as movement_cost_lines')
            ->groupBy('i.id', 'i.name', 'i.sku', 'i.unit', 'i.cost_price');

        $rowsQuery = DB::query()->fromSub($agg, 'x');
        if (! $includeZero) {
            $rowsQuery->whereRaw('ABS(stock_on_hand) >= 0.0005');
        }
        $rowsQuery->orderBy('name');

        $rows = (clone $rowsQuery)->limit(5000)->get();
        $totalRows = (int) DB::query()->fromSub($agg, 'x')->when(! $includeZero, fn ($q) => $q->whereRaw('ABS(stock_on_hand) >= 0.0005'))->count();

        $summary = DB::query()
            ->fromSub($agg, 'x')
            ->when(! $includeZero, fn ($q) => $q->whereRaw('ABS(stock_on_hand) >= 0.0005'))
            ->selectRaw('COALESCE(SUM(stock_on_hand), 0) as qty_total')
            ->selectRaw('COALESCE(SUM(stock_value), 0) as value_total')
            ->selectRaw('COALESCE(SUM(movement_lines), 0) as movement_lines_total')
            ->selectRaw('COALESCE(SUM(movement_cost_lines), 0) as movement_cost_lines_total')
            ->first();

        $movementLines = (int) ($summary->movement_lines_total ?? 0);
        $movementCostLines = (int) ($summary->movement_cost_lines_total ?? 0);
        $coverage = $movementLines > 0 ? ($movementCostLines / $movementLines) * 100 : 0.0;

        $badges = [$includeZero ? 'Include Zero' : 'Exclude Zero'];
        if ($search !== '') {
            $badges[] = 'Filter: '.$search;
        }

        $meta = $this->meta('Laporan Persediaan', 'Semua tanggal', $badges);
        $filename = 'laporan-persediaan_semua.xlsx';

        $export = new ValuationExport($meta, [
            'qtyTotal' => (float) ($summary->qty_total ?? 0),
            'valueTotal' => (float) ($summary->value_total ?? 0),
            'coveragePercent' => $coverage,
            'shownCount' => $rows->count(),
            'totalCount' => $totalRows,
        ], $rows->all());

        return Excel::download($export, $filename);
    }
}
