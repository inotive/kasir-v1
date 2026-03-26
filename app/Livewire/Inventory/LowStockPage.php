<?php

namespace App\Livewire\Inventory;

use App\Models\Ingredient;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class LowStockPage extends Component
{
    use WithPagination;

    public string $title = 'Low Stock';

    public string $search = '';

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

    protected function query(): Builder
    {
        $stockSubquery = DB::table('inventory_movements')
            ->select('ingredient_id')
            ->selectRaw('COALESCE(SUM(quantity), 0) as stock_on_hand')
            ->groupBy('ingredient_id');

        return Ingredient::query()
            ->leftJoinSub($stockSubquery, 'inventory_stock', function ($join): void {
                $join->on('inventory_stock.ingredient_id', '=', 'ingredients.id');
            })
            ->select([
                'ingredients.id',
                'ingredients.name',
                'ingredients.sku',
                'ingredients.unit',
                'ingredients.reorder_level',
                'ingredients.is_active',
            ])
            ->selectRaw('COALESCE(inventory_stock.stock_on_hand, 0) as stock_on_hand')
            ->where('ingredients.is_active', true)
            ->where('ingredients.reorder_level', '>', 0)
            ->whereRaw('COALESCE(inventory_stock.stock_on_hand, 0) <= ingredients.reorder_level')
            ->when($this->search !== '', function (Builder $query): void {
                $term = '%'.$this->search.'%';
                $query->where(function (Builder $q) use ($term): void {
                    $q->where('ingredients.name', 'like', $term)
                        ->orWhere('ingredients.sku', 'like', $term);
                });
            })
            ->orderBy('stock_on_hand')
            ->orderBy('ingredients.name');
    }

    public function render(): View
    {
        $ingredients = $this->query()->paginate(20);

        return view('livewire.inventory.low-stock-page', [
            'ingredients' => $ingredients,
        ])->layout('layouts.app', ['title' => $this->title]);
    }
}
