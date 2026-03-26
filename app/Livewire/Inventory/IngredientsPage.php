<?php

namespace App\Livewire\Inventory;

use App\Models\Ingredient;
use App\Services\Product\ProductHppSyncService;
use App\Support\Number\QuantityFormatter;
use App\Support\Number\QuantityParser;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class IngredientsPage extends Component
{
    use WithPagination;

    public string $title = 'Bahan Baku';

    public string $search = '';

    public bool $createIngredientModalOpen = false;

    public string $name = '';

    public ?string $sku = null;

    public string $unit = 'pcs';

    public ?string $costPrice = null;

    public ?string $reorderLevel = null;

    public ?int $editingIngredientId = null;

    public string $editingName = '';

    public ?string $editingSku = null;

    public string $editingUnit = 'pcs';

    public ?string $editingCostPrice = null;

    public ?string $editingReorderLevel = null;

    public function mount(): void
    {
        $this->authorizeAny(['inventory.ingredients.view', 'inventory.ingredients.manage', 'inventory.manage', 'inventory.view']);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openCreateIngredientModal(): void
    {
        $this->authorizeAny(['inventory.ingredients.manage', 'inventory.manage']);

        $this->reset(['name', 'sku', 'unit', 'costPrice', 'reorderLevel']);
        $this->unit = 'pcs';
        $this->resetValidation();
        $this->createIngredientModalOpen = true;
    }

    public function closeCreateIngredientModal(): void
    {
        $this->createIngredientModalOpen = false;
        $this->resetValidation();
    }

    public function createIngredient(): void
    {
        $this->authorizeAny(['inventory.ingredients.manage', 'inventory.manage']);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:50'],
            'costPrice' => ['nullable', 'numeric', 'min:0'],
            'reorderLevel' => ['nullable', function (string $attribute, $value, $fail): void {
                if ($value === null || trim((string) $value) === '') {
                    return;
                }
                $parsed = QuantityParser::parse($value);
                if ($parsed === null || $parsed < 0) {
                    $fail('Reorder level tidak valid.');
                }
            }],
        ]);

        Ingredient::query()->create([
            'name' => $validated['name'],
            'sku' => $validated['sku'],
            'unit' => $validated['unit'],
            'cost_price' => $validated['costPrice'] ?? 0,
            'reorder_level' => QuantityParser::parse($validated['reorderLevel'] ?? null) ?? 0,
        ]);

        $this->reset(['name', 'sku', 'unit', 'costPrice', 'reorderLevel']);
        $this->unit = 'pcs';
        $this->createIngredientModalOpen = false;
        $this->dispatch('toast', type: 'success', message: 'Bahan baku berhasil ditambahkan.');
    }

    public function startEditIngredient(int $ingredientId): void
    {
        $this->authorizeAny(['inventory.ingredients.manage', 'inventory.manage']);

        $ingredient = Ingredient::query()->findOrFail($ingredientId);

        $this->editingIngredientId = (int) $ingredient->id;
        $this->editingName = (string) $ingredient->name;
        $this->editingSku = $ingredient->sku;
        $this->editingUnit = (string) $ingredient->unit;
        $this->editingCostPrice = (string) $ingredient->cost_price;
        $this->editingReorderLevel = QuantityFormatter::format((float) $ingredient->reorder_level);
        $this->resetValidation();
    }

    public function cancelEditIngredient(): void
    {
        $this->editingIngredientId = null;
        $this->reset(['editingName', 'editingSku', 'editingUnit', 'editingCostPrice', 'editingReorderLevel']);
        $this->editingUnit = 'pcs';
        $this->resetValidation();
    }

    public function updateIngredient(ProductHppSyncService $hppSync): void
    {
        $this->authorizeAny(['inventory.ingredients.manage', 'inventory.manage']);

        if (! $this->editingIngredientId) {
            return;
        }

        $ingredient = Ingredient::query()->findOrFail($this->editingIngredientId);
        $oldCost = (float) $ingredient->cost_price;

        $validated = $this->validate([
            'editingName' => ['required', 'string', 'max:255'],
            'editingSku' => ['nullable', 'string', 'max:255'],
            'editingUnit' => ['required', 'string', 'max:50'],
            'editingCostPrice' => ['nullable', 'numeric', 'min:0'],
            'editingReorderLevel' => ['nullable', function (string $attribute, $value, $fail): void {
                if ($value === null || trim((string) $value) === '') {
                    return;
                }
                $parsed = QuantityParser::parse($value);
                if ($parsed === null || $parsed < 0) {
                    $fail('Reorder level tidak valid.');
                }
            }],
        ]);

        $newCost = (float) ($validated['editingCostPrice'] ?? 0);

        $ingredient->update([
            'name' => $validated['editingName'],
            'sku' => $validated['editingSku'],
            'unit' => $validated['editingUnit'],
            'cost_price' => $newCost,
            'reorder_level' => QuantityParser::parse($validated['editingReorderLevel'] ?? null) ?? 0,
        ]);

        if (abs($newCost - $oldCost) >= 0.0005) {
            $hppSync->syncForIngredient((int) $ingredient->id);
            $this->dispatch('toast', type: 'success', message: 'Harga bahan tersimpan. HPP produk ikut diperbarui.');
        }

        $this->cancelEditIngredient();
    }

    public function toggleActive(int $ingredientId): void
    {
        $this->authorizeAny(['inventory.ingredients.manage', 'inventory.manage']);

        $ingredient = Ingredient::query()->findOrFail($ingredientId);
        $ingredient->update(['is_active' => ! (bool) $ingredient->is_active]);
    }

    public function deleteIngredient(int $ingredientId): void
    {
        $this->authorizeAny(['inventory.ingredients.manage', 'inventory.manage']);

        $ingredient = Ingredient::query()->findOrFail($ingredientId);
        $ingredient->delete();

        if ($this->editingIngredientId === $ingredientId) {
            $this->cancelEditIngredient();
        }
    }

    protected function ingredientsQuery(): Builder
    {
        return Ingredient::query()
            ->select([
                'ingredients.id',
                'ingredients.name',
                'ingredients.sku',
                'ingredients.unit',
                'ingredients.cost_price',
                'ingredients.reorder_level',
                'ingredients.is_active',
                'ingredients.created_at',
            ])
            ->selectSub(function ($query) {
                $query->from('inventory_movements')
                    ->selectRaw('COALESCE(SUM(quantity), 0)')
                    ->whereColumn('inventory_movements.ingredient_id', 'ingredients.id');
            }, 'stock_on_hand')
            ->when($this->search !== '', function (Builder $query): void {
                $term = '%'.$this->search.'%';
                $query->where(function (Builder $q) use ($term): void {
                    $q->where('ingredients.name', 'like', $term)
                        ->orWhere('ingredients.sku', 'like', $term);
                });
            })
            ->orderBy('ingredients.name');
    }

    public function render(): View
    {
        $ingredients = $this->ingredientsQuery()->paginate(15);

        $user = auth()->user();
        $canManage = (bool) (($user && method_exists($user, 'can')) ? ($user->can('inventory.ingredients.manage') || $user->can('inventory.manage')) : false);

        return view('livewire.inventory.ingredients-page', [
            'ingredients' => $ingredients,
            'canManage' => $canManage,
        ])->layout('layouts.app', ['title' => $this->title]);
    }

    private function authorizeAny(array $permissions): void
    {
        $user = auth()->user();
        if (! $user || ! method_exists($user, 'can')) {
            abort(403);
        }

        foreach ($permissions as $permission) {
            $permission = (string) $permission;
            if ($permission !== '' && $user->can($permission)) {
                return;
            }
        }

        abort(403);
    }
}
