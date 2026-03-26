<?php

namespace App\Services\Inventory;

use App\Models\Ingredient;
use App\Models\InventoryMovement;
use App\Models\ProductRecipe;
use App\Models\ProductVariant;
use App\Models\ProductVariantRecipe;

class VariantIngredientStockStatusService
{
    public function statusesForVariantIds(array $variantIds): array
    {
        $variantIds = array_values(array_unique(array_map('intval', $variantIds)));
        $variantIds = array_values(array_filter($variantIds, fn (int $id): bool => $id > 0));

        if ($variantIds === []) {
            return [];
        }

        $productIdByVariantId = ProductVariant::query()
            ->whereIn('id', $variantIds)
            ->pluck('product_id', 'id')
            ->map(fn ($v) => (int) $v)
            ->all();

        $rows = ProductVariantRecipe::query()
            ->whereIn('product_variant_id', $variantIds)
            ->get(['product_variant_id', 'ingredient_id', 'quantity']);

        $recipesByVariantId = [];
        $ingredientIds = [];

        foreach ($rows as $row) {
            $variantId = (int) $row->product_variant_id;
            $ingredientId = (int) $row->ingredient_id;
            $qty = (float) $row->quantity;

            if ($variantId <= 0 || $ingredientId <= 0 || $qty <= 0) {
                continue;
            }

            $recipesByVariantId[$variantId][] = [
                'ingredient_id' => $ingredientId,
                'quantity' => $qty,
            ];

            $ingredientIds[] = $ingredientId;
        }

        $productRecipesByProductId = [];
        $variantIdsWithoutVariantRecipes = array_values(array_filter(
            $variantIds,
            fn (int $variantId): bool => ($recipesByVariantId[$variantId] ?? []) === []
        ));

        if ($variantIdsWithoutVariantRecipes !== []) {
            $productIds = [];

            foreach ($variantIdsWithoutVariantRecipes as $variantId) {
                $productId = (int) ($productIdByVariantId[$variantId] ?? 0);
                if ($productId > 0) {
                    $productIds[] = $productId;
                }
            }

            $productIds = array_values(array_unique($productIds));

            if ($productIds !== []) {
                $productRows = ProductRecipe::query()
                    ->whereIn('product_id', $productIds)
                    ->get(['product_id', 'ingredient_id', 'quantity']);

                foreach ($productRows as $row) {
                    $productId = (int) $row->product_id;
                    $ingredientId = (int) $row->ingredient_id;
                    $qty = (float) $row->quantity;

                    if ($productId <= 0 || $ingredientId <= 0 || $qty <= 0) {
                        continue;
                    }

                    $productRecipesByProductId[$productId][] = [
                        'ingredient_id' => $ingredientId,
                        'quantity' => $qty,
                    ];

                    $ingredientIds[] = $ingredientId;
                }
            }
        }

        $ingredientIds = array_values(array_unique($ingredientIds));

        $stocksByIngredientId = $ingredientIds === []
            ? []
            : InventoryMovement::query()
                ->whereIn('ingredient_id', $ingredientIds)
                ->selectRaw('ingredient_id, COALESCE(SUM(quantity), 0) as stock')
                ->groupBy('ingredient_id')
                ->pluck('stock', 'ingredient_id')
                ->map(fn ($v) => (float) $v)
                ->all();

        $reorderLevelsByIngredientId = $ingredientIds === []
            ? []
            : Ingredient::query()
                ->whereIn('id', $ingredientIds)
                ->pluck('reorder_level', 'id')
                ->map(fn ($v) => (float) $v)
                ->all();

        $statuses = [];

        foreach ($variantIds as $variantId) {
            $recipes = $recipesByVariantId[$variantId] ?? [];

            if ($recipes === []) {
                $productId = (int) ($productIdByVariantId[$variantId] ?? 0);
                $recipes = $productId > 0 ? ($productRecipesByProductId[$productId] ?? []) : [];
            }

            if ($recipes === []) {
                $statuses[$variantId] = 'missing_bom';

                continue;
            }

            $isInsufficient = false;
            $isLow = false;

            foreach ($recipes as $recipe) {
                $ingredientId = (int) ($recipe['ingredient_id'] ?? 0);
                $requiredQty = (float) ($recipe['quantity'] ?? 0);

                if ($ingredientId <= 0 || $requiredQty <= 0) {
                    continue;
                }

                $stock = (float) ($stocksByIngredientId[$ingredientId] ?? 0.0);

                if ($stock + 0.0000001 < $requiredQty) {
                    $isInsufficient = true;
                    break;
                }

                $reorderLevel = (float) ($reorderLevelsByIngredientId[$ingredientId] ?? 0.0);
                if ($reorderLevel > 0 && $stock <= $reorderLevel + 0.0000001) {
                    $isLow = true;
                }
            }

            if ($isInsufficient) {
                $statuses[$variantId] = 'insufficient';
            } elseif ($isLow) {
                $statuses[$variantId] = 'low';
            } else {
                $statuses[$variantId] = 'ok';
            }
        }

        return $statuses;
    }
}
