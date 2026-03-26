<?php

namespace App\Livewire\Inventory;

use App\Models\Ingredient;
use App\Models\InventoryMovement;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Support\Number\QuantityFormatter;
use App\Support\Number\QuantityParser;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class StockOpnameFormPage extends Component
{
    public string $title = 'Stock Opname';

    public ?int $stockOpnameId = null;

    public string $code = '';

    public string $status = 'draft';

    public ?string $countedAt = null;

    public ?string $note = null;

    public array $items = [];

    public array $ingredients = [];

    public array $systemStocks = [];

    public bool $postConfirmOpen = false;

    public bool $cancelConfirmOpen = false;

    public function mount(?StockOpname $stockOpname = null): void
    {
        $this->authorizeAny(['inventory.opnames.view', 'inventory.opnames.manage', 'inventory.manage', 'inventory.view']);

        $this->ingredients = Ingredient::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'unit'])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'unit' => (string) $row->unit,
            ])
            ->all();

        $this->systemStocks = InventoryMovement::query()
            ->selectRaw('ingredient_id, COALESCE(SUM(quantity), 0) as qty')
            ->groupBy('ingredient_id')
            ->pluck('qty', 'ingredient_id')
            ->map(fn ($value) => (float) $value)
            ->all();

        $this->countedAt = CarbonImmutable::now()->format('Y-m-d');

        if ($stockOpname) {
            $this->stockOpnameId = (int) $stockOpname->id;
            $this->code = (string) $stockOpname->code;
            $this->status = (string) $stockOpname->status;
            $this->countedAt = optional($stockOpname->counted_at)->format('Y-m-d') ?: $this->countedAt;
            $this->note = $stockOpname->note;

            $this->items = $stockOpname->items()
                ->orderBy('id')
                ->get()
                ->map(fn (StockOpnameItem $item) => [
                    'ingredient_id' => (int) $item->ingredient_id,
                    'system_qty' => (float) $item->system_qty,
                    'counted_qty' => QuantityFormatter::format((float) $item->counted_qty),
                    'note' => (string) ($item->note ?? ''),
                ])
                ->all();
        } else {
            $this->code = $this->generateCode();
            $this->items = [];
        }
    }

    public function addItem(): void
    {
        $this->authorizeAny(['inventory.opnames.create', 'inventory.opnames.edit', 'inventory.opnames.manage', 'inventory.manage']);

        $this->items[] = [
            'ingredient_id' => null,
            'system_qty' => 0,
            'counted_qty' => '0',
            'note' => '',
        ];
    }

    public function removeItem(int $index): void
    {
        $this->authorizeAny(['inventory.opnames.create', 'inventory.opnames.edit', 'inventory.opnames.manage', 'inventory.manage']);

        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function refreshSystemStocks(): void
    {
        $this->authorizeAny(['inventory.opnames.refresh_system_stocks', 'inventory.opnames.edit', 'inventory.opnames.manage', 'inventory.manage']);

        $this->systemStocks = InventoryMovement::query()
            ->selectRaw('ingredient_id, COALESCE(SUM(quantity), 0) as qty')
            ->groupBy('ingredient_id')
            ->pluck('qty', 'ingredient_id')
            ->map(fn ($value) => (float) $value)
            ->all();

        $this->updatedItems();
        $this->dispatch('toast', type: 'success', message: 'Stok sistem diperbarui.');
    }

    public function updatedItems(): void
    {
        foreach ($this->items as $i => $row) {
            $ingredientId = (int) ($row['ingredient_id'] ?? 0);
            if ($ingredientId > 0) {
                $this->items[$i]['system_qty'] = (float) ($this->systemStocks[$ingredientId] ?? 0);
            }
        }
    }

    public function openPostConfirm(): void
    {
        $this->authorizeAny(['inventory.opnames.post', 'inventory.opnames.manage', 'inventory.manage']);

        if (in_array($this->status, ['posted', 'cancelled'], true)) {
            return;
        }

        $this->postConfirmOpen = true;
    }

    public function closePostConfirm(): void
    {
        $this->postConfirmOpen = false;
    }

    public function openCancelConfirm(): void
    {
        $this->authorizeAny(['inventory.opnames.cancel', 'inventory.opnames.manage', 'inventory.manage']);

        if ($this->stockOpnameId === null) {
            return;
        }

        if ($this->status !== 'draft') {
            return;
        }

        $this->cancelConfirmOpen = true;
    }

    public function closeCancelConfirm(): void
    {
        $this->cancelConfirmOpen = false;
    }

    public function cancelOpname(): void
    {
        $this->authorizeAny(['inventory.opnames.cancel', 'inventory.opnames.manage', 'inventory.manage']);

        if ($this->stockOpnameId === null) {
            return;
        }

        $opname = StockOpname::query()->findOrFail($this->stockOpnameId);
        if ((string) $opname->status !== 'draft') {
            return;
        }

        $opname->update([
            'status' => 'cancelled',
        ]);

        $this->status = 'cancelled';
        $this->postConfirmOpen = false;
        $this->cancelConfirmOpen = false;
        $this->dispatch('toast', type: 'success', message: 'Opname dibatalkan.');
        $this->redirectRoute('stock-opnames.edit', ['stockOpname' => $opname->id], navigate: true);
    }

    public function saveDraft(): void
    {
        $this->authorizeAny(['inventory.opnames.create', 'inventory.opnames.edit', 'inventory.opnames.manage', 'inventory.manage']);

        $this->persistDraft(true);
        $this->dispatch('toast', type: 'success', message: 'Draft opname tersimpan.');
    }

    public function post(): void
    {
        $this->authorizeAny(['inventory.opnames.post', 'inventory.opnames.manage', 'inventory.manage']);

        $user = auth()->user();
        $canManageDraft = (bool) (($user && method_exists($user, 'can')) ? ($user->can('inventory.opnames.manage') || $user->can('inventory.manage')) : false);
        if ($canManageDraft) {
            $this->persistDraft(false);
        }

        $opname = StockOpname::query()->findOrFail($this->stockOpnameId);
        if ($opname->status === 'posted') {
            return;
        }

        $items = $opname->items()->get();
        $costByIngredientId = Ingredient::query()
            ->whereIn('id', $items->pluck('ingredient_id')->map(fn ($id) => (int) $id)->all())
            ->pluck('cost_price', 'id')
            ->map(fn ($v) => (float) $v)
            ->all();

        foreach ($items as $item) {
            $variance = (float) $item->counted_qty - (float) $item->system_qty;

            $item->update(['variance_qty' => $variance]);

            if (abs($variance) < 0.0005) {
                continue;
            }

            InventoryMovement::query()->create([
                'ingredient_id' => $item->ingredient_id,
                'supplier_id' => null,
                'type' => 'opname_adjustment',
                'quantity' => $variance,
                'unit_cost' => (float) ($costByIngredientId[(int) $item->ingredient_id] ?? 0),
                'reference_type' => 'stock_opnames',
                'reference_id' => $opname->id,
                'note' => 'Stock opname '.$opname->code,
                'happened_at' => $opname->counted_at ? $opname->counted_at->format('Y-m-d').' 00:00:00' : null,
            ]);
        }

        $opname->update([
            'status' => 'posted',
            'posted_at' => now(),
        ]);

        $this->status = 'posted';
        $this->postConfirmOpen = false;
        $this->dispatch('toast', type: 'success', message: 'Opname diposting. Penyesuaian stok dibuat.');
        $this->redirectRoute('stock-opnames.edit', ['stockOpname' => $opname->id], navigate: true);
    }

    private function persistDraft(bool $redirectToEditAfterCreate): void
    {
        if (in_array($this->status, ['posted', 'cancelled'], true)) {
            return;
        }

        if ($this->stockOpnameId) {
            $this->authorizeAny(['inventory.opnames.edit', 'inventory.opnames.manage', 'inventory.manage']);
        } else {
            $this->authorizeAny(['inventory.opnames.create', 'inventory.opnames.manage', 'inventory.manage']);
        }

        $this->validateOpname();

        $wasCreated = false;
        if ($this->stockOpnameId) {
            $opname = StockOpname::query()->findOrFail($this->stockOpnameId);
        } else {
            $opname = StockOpname::query()->create([
                'code' => $this->code,
                'status' => 'draft',
                'counted_at' => $this->countedAt,
                'note' => $this->note,
            ]);
            $wasCreated = true;
        }

        $opname->update([
            'counted_at' => $this->countedAt,
            'note' => $this->note,
        ]);

        $this->stockOpnameId = (int) $opname->id;

        $opname->items()->delete();

        foreach ($this->normalizedItems() as $row) {
            $systemQty = (float) ($this->systemStocks[$row['ingredient_id']] ?? 0);
            $countedQty = (float) $row['counted_qty'];

            StockOpnameItem::query()->create([
                'stock_opname_id' => $opname->id,
                'ingredient_id' => $row['ingredient_id'],
                'system_qty' => $systemQty,
                'counted_qty' => $countedQty,
                'variance_qty' => 0,
                'note' => $row['note'] !== '' ? $row['note'] : null,
            ]);
        }

        $this->items = $opname->items()
            ->orderBy('id')
            ->get()
            ->map(fn (StockOpnameItem $item) => [
                'ingredient_id' => (int) $item->ingredient_id,
                'system_qty' => (float) $item->system_qty,
                'counted_qty' => QuantityFormatter::format((float) $item->counted_qty),
                'note' => (string) ($item->note ?? ''),
            ])
            ->all();

        if ($wasCreated && $redirectToEditAfterCreate) {
            $this->redirectRoute('stock-opnames.edit', ['stockOpname' => $opname->id], navigate: true);
        }
    }

    private function validateOpname(): void
    {
        $this->validate([
            'code' => ['required', 'string', 'max:255'],
            'countedAt' => ['required', 'date'],
            'note' => ['nullable', 'string'],
            'items' => ['array', 'min:1'],
            'items.*.ingredient_id' => ['required', 'integer', 'exists:ingredients,id'],
            'items.*.counted_qty' => ['required', function (string $attribute, $value, $fail): void {
                $parsed = QuantityParser::parse($value);
                if ($parsed === null || $parsed < 0) {
                    $fail('Stok fisik tidak valid.');
                }
            }],
            'items.*.note' => ['nullable', 'string'],
        ]);

        $ids = array_map(fn ($row) => (int) ($row['ingredient_id'] ?? 0), $this->items);
        $ids = array_filter($ids, fn ($id) => $id > 0);
        if (count($ids) !== count(array_unique($ids))) {
            throw ValidationException::withMessages([
                'items' => 'Bahan baku tidak boleh duplikat dalam satu opname.',
            ]);
        }

        if (! $this->stockOpnameId) {
            $exists = StockOpname::query()->where('code', $this->code)->exists();
            if ($exists) {
                throw ValidationException::withMessages([
                    'code' => 'Kode opname sudah digunakan.',
                ]);
            }
        }
    }

    public function render(): View
    {
        $isPosted = $this->status === 'posted';
        $isCancelled = $this->status === 'cancelled';
        $isLocked = $isPosted || $isCancelled;

        $user = auth()->user();
        $canCreate = (bool) (($user && method_exists($user, 'can')) ? ($user->can('inventory.opnames.create') || $user->can('inventory.opnames.manage') || $user->can('inventory.manage')) : false);
        $canEdit = (bool) (($user && method_exists($user, 'can')) ? ($user->can('inventory.opnames.edit') || $user->can('inventory.opnames.manage') || $user->can('inventory.manage')) : false);
        $canManage = $canCreate || $canEdit;
        $canPost = (bool) (($user && method_exists($user, 'can')) ? ($user->can('inventory.opnames.post') || $user->can('inventory.manage')) : false);
        $canCancel = (bool) (($user && method_exists($user, 'can')) ? ($user->can('inventory.opnames.cancel') || $user->can('inventory.manage')) : false);
        $canRefresh = (bool) (($user && method_exists($user, 'can')) ? ($user->can('inventory.opnames.refresh_system_stocks') || $user->can('inventory.opnames.manage') || $user->can('inventory.manage')) : false);

        return view('livewire.inventory.stock-opname-form-page', [
            'ingredients' => $this->ingredients,
            'isPosted' => $isPosted,
            'isCancelled' => $isCancelled,
            'isLocked' => $isLocked,
            'stockOpnameId' => $this->stockOpnameId,
            'canManage' => $canManage,
            'canCreate' => $canCreate,
            'canEdit' => $canEdit,
            'canPost' => $canPost,
            'canCancel' => $canCancel,
            'canRefresh' => $canRefresh,
        ])->layout('layouts.app', ['title' => $this->title]);
    }

    private function normalizedItems(): array
    {
        $rows = [];
        foreach ($this->items as $row) {
            $ingredientId = (int) ($row['ingredient_id'] ?? 0);
            if ($ingredientId <= 0) {
                continue;
            }

            $rows[] = [
                'ingredient_id' => $ingredientId,
                'counted_qty' => (float) (QuantityParser::parse($row['counted_qty'] ?? null) ?? 0),
                'note' => (string) ($row['note'] ?? ''),
            ];
        }

        return $rows;
    }

    private function generateCode(): string
    {
        $prefix = 'OPN';
        for ($i = 0; $i < 5; $i++) {
            $code = $prefix.'-'.CarbonImmutable::now()->format('ymdHis').'-'.Str::upper(Str::random(3));
            if (! StockOpname::query()->where('code', $code)->exists()) {
                return $code;
            }
        }

        return $prefix.'-'.CarbonImmutable::now()->format('ymdHis').'-'.Str::upper(Str::random(6));
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
