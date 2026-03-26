<?php

namespace App\Services\Inventory;

use App\Models\Ingredient;
use App\Models\InventoryMovement;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function applyTransaction(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction): void {
            $locked = Transaction::query()
                ->whereKey($transaction->id)
                ->lockForUpdate()
                ->first();

            if (! $locked || $locked->inventory_applied_at) {
                return;
            }

            $exists = InventoryMovement::query()
                ->where('reference_type', 'transactions')
                ->where('reference_id', $locked->id)
                ->where('type', 'sale_consumption')
                ->exists();

            if ($exists) {
                $this->applyTransactionHpp($locked);
                $locked->forceFill(['inventory_applied_at' => now()])->save();

                return;
            }

            $locked->loadMissing([
                'transactionItems.variant.recipes.ingredient',
                'transactionItems.product.recipes.ingredient',
            ]);

            $items = collect($locked->transactionItems);
            $invalidPackages = $items
                ->filter(fn ($item) => $item->parent_transaction_item_id === null && $item->product && (bool) $item->product->is_package)
                ->filter(fn ($parent) => $items->where('parent_transaction_item_id', (int) $parent->id)->isEmpty())
                ->map(function ($parent): string {
                    $product = $parent->product;
                    $variant = $parent->variant;

                    if ($variant) {
                        return (string) ($product?->name ?? 'Paket').' - '.$variant->name;
                    }

                    return (string) ($product?->name ?? 'Paket');
                })
                ->values();

            if ($invalidPackages->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'inventory' => 'Komponen paket belum terbentuk untuk: '.$invalidPackages->implode(', '),
                ]);
            }

            $missingItems = collect($locked->transactionItems)
                ->filter(function ($item): bool {
                    if ($item->parent_transaction_item_id === null && $item->product && (bool) $item->product->is_package) {
                        return false;
                    }

                    if ($item->variant) {
                        return $item->variant->recipes->count() === 0;
                    }

                    if ($item->product) {
                        return $item->product->recipes->count() === 0;
                    }

                    return true;
                })
                ->map(function ($item): string {
                    $product = $item->product;
                    $variant = $item->variant;

                    if ($variant) {
                        return (string) $variant->name;
                    }

                    return (string) (($product?->name ?? 'Produk').' (tanpa varian)');
                })
                ->values();

            if ($missingItems->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'inventory' => 'Resep/BOM belum diatur untuk: '.$missingItems->implode(', '),
                ]);
            }

            $ingredientTotals = [];

            foreach ($locked->transactionItems as $item) {
                if ($item->parent_transaction_item_id === null && $item->product && (bool) $item->product->is_package) {
                    continue;
                }

                $soldQty = (float) $item->quantity;

                $recipes = $item->variant
                    ? $item->variant->recipes
                    : ($item->product?->recipes ?? collect());

                foreach ($recipes as $recipe) {
                    $ingredientId = (int) $recipe->ingredient_id;
                    $consumeQty = -1 * $soldQty * (float) $recipe->quantity;

                    if (! array_key_exists($ingredientId, $ingredientTotals)) {
                        $ingredientTotals[$ingredientId] = 0.0;
                    }

                    $ingredientTotals[$ingredientId] += $consumeQty;
                }
            }

            $this->applyTransactionHpp($locked);

            if ($ingredientTotals === []) {
                $locked->forceFill(['inventory_applied_at' => now()])->save();

                return;
            }

            $ingredientCosts = Ingredient::query()
                ->whereIn('id', array_keys($ingredientTotals))
                ->pluck('cost_price', 'id')
                ->map(fn ($v) => (float) $v)
                ->all();

            $happenedAt = $locked->created_at ? $locked->created_at : now();

            foreach ($ingredientTotals as $ingredientId => $qty) {
                if (abs((float) $qty) < 0.0005) {
                    continue;
                }

                InventoryMovement::query()->create([
                    'ingredient_id' => $ingredientId,
                    'supplier_id' => null,
                    'type' => 'sale_consumption',
                    'quantity' => (float) $qty,
                    'unit_cost' => (float) ($ingredientCosts[(int) $ingredientId] ?? 0),
                    'reference_type' => 'transactions',
                    'reference_id' => $locked->id,
                    'note' => 'Transaksi '.$locked->code,
                    'happened_at' => $happenedAt,
                ]);
            }

            $locked->forceFill(['inventory_applied_at' => now()])->save();
        });
    }

    public function reverseTransaction(Transaction $transaction, string $note): void
    {
        $exists = InventoryMovement::query()
            ->where('reference_type', 'transactions')
            ->where('reference_id', $transaction->id)
            ->where('type', 'sale_reversal')
            ->exists();

        if ($exists) {
            return;
        }

        $movements = InventoryMovement::query()
            ->where('reference_type', 'transactions')
            ->where('reference_id', $transaction->id)
            ->where('type', 'sale_consumption')
            ->get();

        if ($movements->isEmpty()) {
            return;
        }

        $happenedAt = now();

        foreach ($movements as $movement) {
            $qty = (float) $movement->quantity;
            if (abs($qty) < 0.0005) {
                continue;
            }

            InventoryMovement::query()->create([
                'ingredient_id' => (int) $movement->ingredient_id,
                'supplier_id' => null,
                'type' => 'sale_reversal',
                'quantity' => -1 * $qty,
                'unit_cost' => $movement->unit_cost === null ? null : (float) $movement->unit_cost,
                'reference_type' => 'transactions',
                'reference_id' => $transaction->id,
                'note' => $note,
                'happened_at' => $happenedAt,
            ]);
        }
    }

    private function applyTransactionHpp(Transaction $transaction): void
    {
        $transaction->loadMissing([
            'transactionItems.variant.recipes.ingredient',
            'transactionItems.product.recipes.ingredient',
        ]);

        $items = collect($transaction->transactionItems);

        foreach ($transaction->transactionItems as $item) {
            if ($item->parent_transaction_item_id === null && $item->product && (bool) $item->product->is_package) {
                continue;
            }

            if ($item->hpp_unit !== null) {
                if ($item->hpp_total === null) {
                    $item->update([
                        'hpp_total' => round((float) $item->hpp_unit * (int) $item->quantity, 2),
                    ]);
                }

                continue;
            }

            $recipes = $item->variant
                ? $item->variant->recipes
                : ($item->product?->recipes ?? collect());

            $hppUnit = 0.0;

            foreach ($recipes as $recipe) {
                $hppUnit += (float) ($recipe->ingredient?->cost_price ?? 0) * (float) $recipe->quantity;
            }

            $hppUnit = round($hppUnit, 2);

            $item->update([
                'hpp_unit' => $hppUnit,
                'hpp_total' => round($hppUnit * (int) $item->quantity, 2),
            ]);
        }

        foreach ($items as $parent) {
            if ($parent->parent_transaction_item_id !== null) {
                continue;
            }

            if (! $parent->product || ! (bool) $parent->product->is_package) {
                continue;
            }

            $children = $items->where('parent_transaction_item_id', (int) $parent->id);
            if ($children->isEmpty()) {
                continue;
            }

            $sum = 0.0;
            foreach ($children as $child) {
                $sum += (float) ($child->hpp_total ?? 0);
            }

            $qty = (int) $parent->quantity;
            $hppTotal = round($sum, 2);
            $hppUnit = $qty > 0 ? round($hppTotal / $qty, 2) : 0;

            $parent->update([
                'hpp_unit' => $hppUnit,
                'hpp_total' => $hppTotal,
            ]);
        }
    }
}
