<?php

namespace App\Livewire\Inventory;

use App\Models\Ingredient;
use App\Models\InventoryMovement;
use App\Models\Purchase;
use App\Models\StockOpname;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Support\Number\QuantityParser;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class InventoryMovementsPage extends Component
{
    use WithPagination;

    public string $title = 'Pergerakan Stok';

    public string $search = '';

    public ?string $fromDate = null;

    public ?string $toDate = null;

    public string $rangePreset = '7d';

    public bool $createMovementModalOpen = false;

    public ?int $ingredientId = null;

    public string $type = 'adjustment';

    public string $direction = 'in';

    public ?string $quantity = null;

    public ?string $unitCost = null;

    public ?string $happenedAt = null;

    public ?string $note = null;

    public ?int $filterIngredientId = null;

    public ?int $filterSupplierId = null;

    public string $filterType = '';

    public bool $deleteConfirmOpen = false;

    public ?int $deletingMovementId = null;

    public function mount(): void
    {
        $this->authorizeAny(['inventory.movements.view', 'inventory.movements.manage', 'inventory.manage', 'inventory.view']);

        $this->happenedAt = CarbonImmutable::now()->format('Y-m-d');
        $this->setRange('7d');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterIngredientId(): void
    {
        $this->resetPage();
    }

    public function updatedFilterSupplierId(): void
    {
        $this->resetPage();
    }

    public function updatedFilterType(): void
    {
        $this->resetPage();
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
        $this->resetPage();
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
        $this->resetPage();
    }

    public function openCreateMovementModal(): void
    {
        $this->authorizeAny(['inventory.movements.create', 'inventory.movements.manage', 'inventory.manage']);

        $this->reset(['ingredientId', 'quantity', 'unitCost', 'note']);
        $this->type = 'adjustment';
        $this->direction = 'in';
        if (! $this->happenedAt) {
            $this->happenedAt = CarbonImmutable::now()->format('Y-m-d');
        }
        $this->resetValidation();
        $this->createMovementModalOpen = true;
    }

    public function updatedType(): void
    {
        if ($this->type !== 'adjustment') {
            $this->direction = 'out';
        }
    }

    public function closeCreateMovementModal(): void
    {
        $this->createMovementModalOpen = false;
        $this->resetValidation();
    }

    public function createMovement(): void
    {
        $this->authorizeAny(['inventory.movements.create', 'inventory.movements.manage', 'inventory.manage']);

        $validated = $this->validate([
            'ingredientId' => ['required', 'integer', 'exists:ingredients,id'],
            'type' => ['required', 'in:adjustment,usage,waste'],
            'direction' => ['required', 'in:in,out'],
            'quantity' => ['required', function (string $attribute, $value, $fail): void {
                $parsed = QuantityParser::parse($value);
                if ($parsed === null || $parsed < 0.001) {
                    $fail('Qty tidak valid.');
                }
            }],
            'unitCost' => ['nullable', 'numeric', 'min:0'],
            'happenedAt' => ['nullable', 'date'],
            'note' => ['required', 'string', 'max:255'],
        ]);

        $qty = (float) (QuantityParser::parse($validated['quantity'] ?? null) ?? 0);

        $direction = (string) $validated['direction'];
        $type = (string) $validated['type'];
        $signedQty = $direction === 'out' ? -abs($qty) : abs($qty);

        if (in_array($type, ['usage', 'waste'], true)) {
            $signedQty = -abs($qty);
        }

        $unitCost = $validated['unitCost'] === null || $validated['unitCost'] === ''
            ? (float) (Ingredient::query()->whereKey((int) $validated['ingredientId'])->value('cost_price') ?? 0)
            : (float) $validated['unitCost'];

        InventoryMovement::query()->create([
            'ingredient_id' => (int) $validated['ingredientId'],
            'supplier_id' => null,
            'type' => $type,
            'quantity' => $signedQty,
            'unit_cost' => $unitCost,
            'note' => $validated['note'],
            'happened_at' => $validated['happenedAt'] ? $validated['happenedAt'].' 00:00:00' : null,
        ]);

        $this->reset(['ingredientId', 'quantity', 'unitCost', 'note']);
        $this->type = 'adjustment';
        $this->direction = 'in';
        $this->createMovementModalOpen = false;
        $this->dispatch('toast', type: 'success', message: 'Pergerakan stok berhasil disimpan.');
    }

    public function openDeleteConfirm(int $movementId): void
    {
        $this->authorizeAny(['inventory.movements.delete', 'inventory.movements.manage', 'inventory.manage']);

        $this->deletingMovementId = $movementId;
        $this->deleteConfirmOpen = true;
    }

    public function closeDeleteConfirm(): void
    {
        $this->deleteConfirmOpen = false;
        $this->deletingMovementId = null;
    }

    public function deleteMovement(): void
    {
        $this->authorizeAny(['inventory.movements.delete', 'inventory.movements.manage', 'inventory.manage']);

        $movementId = (int) ($this->deletingMovementId ?? 0);
        if ($movementId <= 0) {
            return;
        }

        $movement = InventoryMovement::query()->findOrFail($movementId);
        if ($movement->reference_type !== null || $movement->reference_id !== null) {
            $this->dispatch('toast', type: 'error', message: 'Pergerakan stok ini berasal dari dokumen (transaksi/pembelian/opname) dan tidak bisa dihapus dari sini.');
            $this->closeDeleteConfirm();

            return;
        }

        $movement->delete();

        $this->dispatch('toast', type: 'success', message: 'Pergerakan stok dihapus.');
        $this->closeDeleteConfirm();
    }

    protected function movementsQuery(): Builder
    {
        return InventoryMovement::query()
            ->with(['ingredient', 'supplier'])
            ->when(! empty($this->filterIngredientId), fn (Builder $q) => $q->where('ingredient_id', $this->filterIngredientId))
            ->when(! empty($this->filterSupplierId), fn (Builder $q) => $q->where('supplier_id', $this->filterSupplierId))
            ->when($this->filterType !== '', fn (Builder $q) => $q->where('type', $this->filterType))
            ->when($this->search !== '', function (Builder $q): void {
                $term = '%'.$this->search.'%';
                $q->where(function (Builder $w) use ($term): void {
                    $w->where('note', 'like', $term)
                        ->orWhere('reference_type', 'like', $term);
                });
            })
            ->when($this->fromDate, function (Builder $q): void {
                $from = $this->fromDate;
                $q->where(function (Builder $w) use ($from): void {
                    $w->where(function (Builder $x) use ($from): void {
                        $x->whereNotNull('happened_at')->whereDate('happened_at', '>=', $from);
                    })->orWhere(function (Builder $x) use ($from): void {
                        $x->whereNull('happened_at')->whereDate('created_at', '>=', $from);
                    });
                });
            })
            ->when($this->toDate, function (Builder $q): void {
                $to = $this->toDate;
                $q->where(function (Builder $w) use ($to): void {
                    $w->where(function (Builder $x) use ($to): void {
                        $x->whereNotNull('happened_at')->whereDate('happened_at', '<=', $to);
                    })->orWhere(function (Builder $x) use ($to): void {
                        $x->whereNull('happened_at')->whereDate('created_at', '<=', $to);
                    });
                });
            })
            ->orderByRaw('COALESCE(happened_at, created_at) desc')
            ->orderByDesc('id');
    }

    public function render(): View
    {
        $ingredients = Ingredient::query()
            ->orderBy('name')
            ->get(['id', 'name', 'unit']);

        $suppliers = Supplier::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $typeOptions = [
            'purchase' => 'Pembelian',
            'sale_consumption' => 'Penjualan (Konsumsi)',
            'sale_reversal' => 'Pembatalan Penjualan',
            'usage' => 'Pemakaian (Non-Penjualan)',
            'waste' => 'Waste/Rusak/Expired',
            'adjustment' => 'Penyesuaian (Koreksi Input)',
            'opname_adjustment' => 'Stock Opname',
        ];

        $createTypeOptions = [
            'adjustment' => 'Penyesuaian (Koreksi Input)',
            'usage' => 'Pemakaian (Non-Penjualan)',
            'waste' => 'Waste/Rusak/Expired',
        ];

        $movements = $this->movementsQuery()->paginate(20);

        $refGroups = [];
        foreach ($movements as $movement) {
            $type = (string) ($movement->reference_type ?? '');
            $id = (int) ($movement->reference_id ?? 0);
            if ($type === '' || $id <= 0) {
                continue;
            }
            $refGroups[$type][] = $id;
        }

        $refCodes = [
            'purchases' => [],
            'stock_opnames' => [],
            'transactions' => [],
        ];

        if (! empty($refGroups['purchases'])) {
            $ids = array_values(array_unique(array_map('intval', $refGroups['purchases'])));
            $refCodes['purchases'] = Purchase::query()
                ->whereIn('id', $ids)
                ->pluck('code', 'id')
                ->map(fn ($value) => (string) $value)
                ->all();
        }

        if (! empty($refGroups['stock_opnames'])) {
            $ids = array_values(array_unique(array_map('intval', $refGroups['stock_opnames'])));
            $refCodes['stock_opnames'] = StockOpname::query()
                ->whereIn('id', $ids)
                ->pluck('code', 'id')
                ->map(fn ($value) => (string) $value)
                ->all();
        }

        if (! empty($refGroups['transactions'])) {
            $ids = array_values(array_unique(array_map('intval', $refGroups['transactions'])));
            $refCodes['transactions'] = Transaction::query()
                ->whereIn('id', $ids)
                ->pluck('code', 'id')
                ->map(fn ($value) => (string) $value)
                ->all();
        }

        $user = auth()->user();
        $canCreate = (bool) (($user && method_exists($user, 'can')) ? ($user->can('inventory.movements.create') || $user->can('inventory.movements.manage') || $user->can('inventory.manage')) : false);
        $canDelete = (bool) (($user && method_exists($user, 'can')) ? ($user->can('inventory.movements.delete') || $user->can('inventory.movements.manage') || $user->can('inventory.manage')) : false);

        return view('livewire.inventory.inventory-movements-page', [
            'ingredients' => $ingredients,
            'suppliers' => $suppliers,
            'typeOptions' => $typeOptions,
            'createTypeOptions' => $createTypeOptions,
            'movements' => $movements,
            'refCodes' => $refCodes,
            'canCreate' => $canCreate,
            'canDelete' => $canDelete,
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
