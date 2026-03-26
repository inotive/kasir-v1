<?php

namespace App\Livewire\Inventory;

use App\Models\Ingredient;
use App\Models\InventoryMovement;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Services\Product\ProductHppSyncService;
use App\Support\Number\QuantityFormatter;
use App\Support\Number\QuantityParser;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class PurchaseFormPage extends Component
{
    public string $title = 'Pembelian';

    public ?int $purchaseId = null;

    public string $code = '';

    public string $status = 'draft';

    public ?int $supplierId = null;

    public ?string $purchasedAt = null;

    public ?string $note = null;

    public array $items = [];

    public array $ingredients = [];

    public array $suppliers = [];

    public array $unitFactors = [];

    public bool $receiveConfirmOpen = false;

    public bool $cancelConfirmOpen = false;

    public function mount(?Purchase $purchase = null): void
    {
        $this->authorizeAny(['inventory.purchases.view', 'inventory.purchases.manage', 'inventory.manage', 'inventory.view']);

        $this->ingredients = Ingredient::query()
            ->with('unitConversions')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'unit'])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'base_unit' => (string) $row->unit,
                'units' => array_values(array_unique(array_merge(
                    [(string) $row->unit],
                    $row->unitConversions->pluck('unit')->map(fn ($u) => (string) $u)->all(),
                ))),
                'unit_factors' => array_merge(
                    [(string) $row->unit => 1.0],
                    $row->unitConversions->pluck('factor_to_base', 'unit')->map(fn ($v) => (float) $v)->all(),
                ),
            ])
            ->all();

        foreach ($this->ingredients as $ingredient) {
            $this->unitFactors[(int) $ingredient['id']] = (array) ($ingredient['unit_factors'] ?? []);
        }

        $this->suppliers = Supplier::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
            ])
            ->all();

        $this->purchasedAt = CarbonImmutable::now()->format('Y-m-d');

        if (! $purchase) {
            $this->code = $this->generateCode();
            $this->items = [];

            return;
        }

        $purchase->load('items');

        $this->purchaseId = (int) $purchase->id;
        $this->code = (string) $purchase->code;
        $this->status = (string) $purchase->status;
        $this->supplierId = $purchase->supplier_id === null ? null : (int) $purchase->supplier_id;
        $this->purchasedAt = optional($purchase->purchased_at)->format('Y-m-d') ?: $this->purchasedAt;
        $this->note = $purchase->note;

        $this->items = $purchase->items
            ->map(fn (PurchaseItem $item) => [
                'id' => (int) $item->id,
                'key' => (string) Str::uuid(),
                'ingredient_id' => (int) $item->ingredient_id,
                'input_quantity' => QuantityFormatter::format((float) $item->input_quantity),
                'input_unit' => (string) $item->input_unit,
                'input_unit_cost' => $item->input_unit_cost === null ? null : (string) $item->input_unit_cost,
                'note' => (string) ($item->note ?? ''),
            ])
            ->values()
            ->all();
    }

    public function addItem(): void
    {
        $this->authorizeAny(['inventory.purchases.create', 'inventory.purchases.edit', 'inventory.purchases.manage', 'inventory.manage']);

        $this->items[] = [
            'id' => null,
            'key' => (string) Str::uuid(),
            'ingredient_id' => null,
            'input_quantity' => '',
            'input_unit' => '',
            'input_unit_cost' => '',
            'note' => '',
        ];
    }

    public function removeItem(string $key): void
    {
        $this->authorizeAny(['inventory.purchases.create', 'inventory.purchases.edit', 'inventory.purchases.manage', 'inventory.manage']);

        $this->items = collect($this->items)
            ->reject(fn (array $item) => (string) ($item['key'] ?? '') === $key)
            ->values()
            ->all();
    }

    public function openReceiveConfirm(): void
    {
        $this->authorizeAny(['inventory.purchases.receive', 'inventory.purchases.manage', 'inventory.manage']);

        if (in_array($this->status, ['received', 'cancelled'], true)) {
            return;
        }

        $this->receiveConfirmOpen = true;
    }

    public function closeReceiveConfirm(): void
    {
        $this->receiveConfirmOpen = false;
    }

    public function openCancelConfirm(): void
    {
        $this->authorizeAny(['inventory.purchases.cancel', 'inventory.purchases.manage', 'inventory.manage']);

        if ($this->purchaseId === null) {
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

    public function cancelPurchase(): void
    {
        $this->authorizeAny(['inventory.purchases.cancel', 'inventory.purchases.manage', 'inventory.manage']);

        if ($this->purchaseId === null) {
            return;
        }

        $purchase = Purchase::query()->findOrFail($this->purchaseId);
        if ((string) $purchase->status !== 'draft') {
            return;
        }

        $purchase->update([
            'status' => 'cancelled',
        ]);

        $this->status = 'cancelled';
        $this->receiveConfirmOpen = false;
        $this->cancelConfirmOpen = false;
        $this->dispatch('toast', type: 'success', message: 'Pembelian dibatalkan.');
        $this->redirectRoute('purchases.edit', ['purchase' => $purchase->id], navigate: true);
    }

    public function saveDraft(): void
    {
        $this->authorizeAny(['inventory.purchases.create', 'inventory.purchases.edit', 'inventory.purchases.manage', 'inventory.manage']);

        $this->persistDraft(true);
        $this->dispatch('toast', type: 'success', message: 'Draft pembelian tersimpan.');
    }

    public function receive(ProductHppSyncService $hppSync): void
    {
        $this->authorizeAny(['inventory.purchases.receive', 'inventory.purchases.manage', 'inventory.manage']);

        $user = auth()->user();
        $canManageDraft = (bool) (($user && method_exists($user, 'can')) ? ($user->can('inventory.purchases.manage') || $user->can('inventory.manage')) : false);
        if ($canManageDraft) {
            $this->persistDraft(false);
        }

        $purchase = Purchase::query()->with(['items'])->findOrFail($this->purchaseId);
        if ($purchase->status === 'received') {
            return;
        }

        $updatedIngredientIds = [];
        $negativeStockNames = [];

        DB::transaction(function () use ($purchase, &$updatedIngredientIds, &$negativeStockNames): void {
            $happenedAt = $purchase->purchased_at ? $purchase->purchased_at->format('Y-m-d').' 00:00:00' : null;

            foreach ($purchase->items as $item) {
                $receivedQty = (float) $item->quantity_base;
                if ($receivedQty <= 0) {
                    continue;
                }

                $ingredientId = (int) $item->ingredient_id;
                $unitCost = (float) $item->unit_cost_base;

                $ingredient = Ingredient::query()->lockForUpdate()->find($ingredientId);
                if (! $ingredient) {
                    continue;
                }

                $oldCost = (float) $ingredient->cost_price;

                if ($unitCost > 0) {
                    $stockOnHandRaw = (float) InventoryMovement::query()
                        ->where('ingredient_id', $ingredientId)
                        ->sum('quantity');

                    if ($stockOnHandRaw < -0.0005) {
                        $negativeStockNames[] = (string) $ingredient->name;
                    }

                    $stockOnHand = max(0.0, $stockOnHandRaw);

                    if ($stockOnHand <= 0) {
                        $newCost = $unitCost;
                    } else {
                        $newCost = (($stockOnHand * $oldCost) + ($receivedQty * $unitCost)) / ($stockOnHand + $receivedQty);
                    }

                    if (abs($newCost - $oldCost) >= 0.0005) {
                        $ingredient->update([
                            'cost_price' => $newCost,
                        ]);
                        $updatedIngredientIds[] = $ingredientId;
                    }
                }

                InventoryMovement::query()->create([
                    'ingredient_id' => $ingredientId,
                    'supplier_id' => $purchase->supplier_id,
                    'type' => 'purchase',
                    'quantity' => $receivedQty,
                    'input_quantity' => (float) $item->input_quantity,
                    'input_unit' => (string) $item->input_unit,
                    'unit_cost' => $unitCost,
                    'input_unit_cost' => $item->input_unit_cost === null ? null : (float) $item->input_unit_cost,
                    'reference_type' => 'purchases',
                    'reference_id' => $purchase->id,
                    'note' => 'Pembelian '.$purchase->code,
                    'happened_at' => $happenedAt,
                ]);
            }

            $purchase->update([
                'status' => 'received',
                'received_at' => now(),
            ]);
        });

        foreach (array_values(array_unique($updatedIngredientIds)) as $ingredientId) {
            $hppSync->syncForIngredient((int) $ingredientId);
        }

        $this->status = 'received';
        $this->receiveConfirmOpen = false;
        $negativeStockNames = array_values(array_unique(array_filter(array_map('strval', $negativeStockNames))));
        if (! empty($negativeStockNames)) {
            $shown = array_slice($negativeStockNames, 0, 3);
            $more = count($negativeStockNames) - count($shown);
            $suffix = $more > 0 ? ' +'.$more.' lainnya' : '';
            $this->dispatch('toast', type: 'warning', message: 'Ada stok minus sebelum penerimaan: '.implode(', ', $shown).$suffix.'. Periksa penyesuaian/opname agar costing tidak bias.');
        }
        $this->dispatch('toast', type: 'success', message: 'Pembelian diterima. Stok bertambah dan HPP/Unit bahan diperbarui.');
        $this->redirectRoute('purchases.edit', ['purchase' => $purchase->id], navigate: true);
    }

    private function persistDraft(bool $redirectToEditAfterCreate): void
    {
        if (in_array($this->status, ['received', 'cancelled'], true)) {
            return;
        }

        if ($this->purchaseId) {
            $this->authorizeAny(['inventory.purchases.edit', 'inventory.purchases.manage', 'inventory.manage']);
        } else {
            $this->authorizeAny(['inventory.purchases.create', 'inventory.purchases.manage', 'inventory.manage']);
        }

        $validated = $this->validate([
            'code' => ['required', 'string', 'max:255'],
            'supplierId' => ['nullable', 'integer', 'exists:suppliers,id'],
            'purchasedAt' => ['required', 'date'],
            'note' => ['nullable', 'string'],
            'items' => ['array', 'min:1'],
            'items.*.ingredient_id' => ['required', 'integer', 'exists:ingredients,id'],
            'items.*.input_quantity' => ['required', function (string $attribute, $value, $fail): void {
                $parsed = QuantityParser::parse($value);
                if ($parsed === null || $parsed < 0.001) {
                    $fail('Qty tidak valid.');
                }
            }],
            'items.*.input_unit' => ['required', 'string', 'max:50'],
            'items.*.input_unit_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.note' => ['nullable', 'string'],
        ]);

        $ingredientIds = array_map(fn ($row) => (int) ($row['ingredient_id'] ?? 0), $validated['items']);
        $ingredientIds = array_filter($ingredientIds, fn ($id) => $id > 0);
        if (count($ingredientIds) !== count(array_unique($ingredientIds))) {
            throw ValidationException::withMessages([
                'items' => 'Bahan baku tidak boleh duplikat dalam satu pembelian.',
            ]);
        }

        $wasCreated = false;
        if ($this->purchaseId) {
            $purchase = Purchase::query()->findOrFail($this->purchaseId);
        } else {
            $purchase = Purchase::query()->create([
                'code' => $validated['code'],
                'status' => 'draft',
                'supplier_id' => $validated['supplierId'],
                'purchased_at' => $validated['purchasedAt'],
                'note' => $validated['note'],
            ]);
            $wasCreated = true;
        }

        $purchase->update([
            'supplier_id' => $validated['supplierId'],
            'purchased_at' => $validated['purchasedAt'],
            'note' => $validated['note'],
        ]);

        $this->purchaseId = (int) $purchase->id;

        $purchase->items()->delete();

        $totalCost = 0.0;

        foreach ($validated['items'] as $row) {
            $ingredientId = (int) $row['ingredient_id'];
            $inputUnit = (string) $row['input_unit'];
            $inputQty = (float) (QuantityParser::parse($row['input_quantity'] ?? null) ?? 0);
            $inputUnitCost = $row['input_unit_cost'] === null || $row['input_unit_cost'] === '' ? null : (float) $row['input_unit_cost'];
            $factor = (float) ($this->unitFactors[$ingredientId][$inputUnit] ?? 0);

            if ($factor <= 0) {
                throw ValidationException::withMessages([
                    'items' => 'Unit tidak valid untuk bahan yang dipilih.',
                ]);
            }

            $baseQty = $inputQty * $factor;
            $baseUnitCost = $inputUnitCost === null ? 0 : ($factor > 0 ? $inputUnitCost / $factor : 0);
            $subtotal = $inputUnitCost === null ? 0 : $inputQty * $inputUnitCost;

            $totalCost += $subtotal;

            $purchase->items()->create([
                'ingredient_id' => $ingredientId,
                'input_quantity' => $inputQty,
                'input_unit' => $inputUnit,
                'quantity_base' => $baseQty,
                'input_unit_cost' => $inputUnitCost,
                'unit_cost_base' => $baseUnitCost,
                'subtotal_cost' => $subtotal,
                'note' => $row['note'] !== '' ? $row['note'] : null,
            ]);
        }

        $purchase->update(['total_cost' => $totalCost]);

        if ($wasCreated && $redirectToEditAfterCreate) {
            $this->redirectRoute('purchases.edit', ['purchase' => $purchase->id], navigate: true);
        }
    }

    private function generateCode(): string
    {
        $prefix = 'PUR';

        for ($i = 0; $i < 5; $i++) {
            $code = $prefix.'-'.CarbonImmutable::now()->format('ymdHis').'-'.Str::upper(Str::random(3));
            if (! Purchase::query()->where('code', $code)->exists()) {
                return $code;
            }
        }

        return $prefix.'-'.CarbonImmutable::now()->format('ymdHis').'-'.Str::upper(Str::random(6));
    }

    public function render(): View
    {
        $isReceived = $this->status === 'received';
        $isCancelled = $this->status === 'cancelled';
        $isLocked = $isReceived || $isCancelled;

        $user = auth()->user();
        $canCreate = (bool) (($user && method_exists($user, 'can')) ? ($user->can('inventory.purchases.create') || $user->can('inventory.purchases.manage') || $user->can('inventory.manage')) : false);
        $canEdit = (bool) (($user && method_exists($user, 'can')) ? ($user->can('inventory.purchases.edit') || $user->can('inventory.purchases.manage') || $user->can('inventory.manage')) : false);
        $canManage = $canCreate || $canEdit;
        $canReceive = (bool) (($user && method_exists($user, 'can')) ? ($user->can('inventory.purchases.receive') || $user->can('inventory.manage')) : false);
        $canCancel = (bool) (($user && method_exists($user, 'can')) ? ($user->can('inventory.purchases.cancel') || $user->can('inventory.manage')) : false);

        return view('livewire.inventory.purchase-form-page', [
            'ingredients' => $this->ingredients,
            'suppliers' => $this->suppliers,
            'isReceived' => $isReceived,
            'isCancelled' => $isCancelled,
            'isLocked' => $isLocked,
            'purchaseId' => $this->purchaseId,
            'canManage' => $canManage,
            'canCreate' => $canCreate,
            'canEdit' => $canEdit,
            'canReceive' => $canReceive,
            'canCancel' => $canCancel,
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
