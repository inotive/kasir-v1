<?php

namespace App\Livewire\Inventory;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class InventoryValuationPage extends Component
{
    use WithPagination;

    public string $title = 'Laporan Persediaan';

    public string $search = '';

    public bool $includeZero = true;

    public function mount(): void
    {
        $user = auth()->user();
        if (
            ! $user
            || ! method_exists($user, 'can')
            || ! (
                $user->can('inventory.reports.view')
                || $user->can('inventory.view')
                || $user->can('inventory.manage')
            )
        ) {
            abort(403);
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedIncludeZero(): void
    {
        $this->resetPage();
    }

    private function aggregatedQuery()
    {
        return DB::table('ingredients as i')
            ->leftJoin('inventory_movements as im', function ($join): void {
                $join->on('im.ingredient_id', '=', 'i.id');
            })
            ->where('i.is_active', true)
            ->when($this->search !== '', function ($q): void {
                $term = '%'.$this->search.'%';
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
    }

    public function render(): View
    {
        $agg = $this->aggregatedQuery();

        $rowsBaseQuery = DB::query()
            ->fromSub($agg, 'x')
            ->when(! $this->includeZero, fn ($q) => $q->whereRaw('ABS(stock_on_hand) >= 0.0005'))
            ->orderBy('name');

        $ingredients = (clone $rowsBaseQuery)->paginate(30);

        $lowestStocks = (clone $rowsBaseQuery)
            ->reorder()
            ->orderBy('stock_on_hand')
            ->limit(5)
            ->get();

        $highestStocks = (clone $rowsBaseQuery)
            ->reorder()
            ->orderByDesc('stock_on_hand')
            ->limit(5)
            ->get();

        $summary = DB::query()
            ->fromSub($agg, 'x')
            ->when(! $this->includeZero, fn ($q) => $q->whereRaw('ABS(stock_on_hand) >= 0.0005'))
            ->selectRaw('COALESCE(SUM(stock_on_hand), 0) as qty_total')
            ->selectRaw('COALESCE(SUM(stock_value), 0) as value_total')
            ->selectRaw('COALESCE(SUM(movement_lines), 0) as movement_lines_total')
            ->selectRaw('COALESCE(SUM(movement_cost_lines), 0) as movement_cost_lines_total')
            ->first();

        $movementLines = (int) ($summary->movement_lines_total ?? 0);
        $movementCostLines = (int) ($summary->movement_cost_lines_total ?? 0);
        $coverage = $movementLines > 0 ? ($movementCostLines / $movementLines) * 100 : 0.0;

        return view('livewire.inventory.inventory-valuation-page', [
            'ingredients' => $ingredients,
            'summary' => [
                'qtyTotal' => (float) ($summary->qty_total ?? 0),
                'valueTotal' => (float) ($summary->value_total ?? 0),
                'movementLines' => $movementLines,
                'movementCostLines' => $movementCostLines,
                'coveragePercent' => $coverage,
            ],
            'lowestStocks' => $lowestStocks,
            'highestStocks' => $highestStocks,
        ])->layout('layouts.app', ['title' => $this->title]);
    }
}
