<?php

namespace App\Services\Product;

use App\Models\ProductPackageItem;
use App\Models\ProductVariant;
use App\Models\ProductVariantRecipe;
use Illuminate\Support\Facades\DB;

class ProductHppSyncService
{
    public function syncForIngredient(int $ingredientId): void
    {
        $ingredientId = (int) $ingredientId;
        if ($ingredientId <= 0) {
            return;
        }

        DB::transaction(function () use ($ingredientId): void {
            $variantIds = ProductVariantRecipe::query()
                ->where('ingredient_id', $ingredientId)
                ->distinct()
                ->pluck('product_variant_id')
                ->map(fn ($id) => (int) $id)
                ->filter(fn (int $id) => $id > 0)
                ->values()
                ->all();

            if ($variantIds === []) {
                return;
            }

            $hppRows = ProductVariantRecipe::query()
                ->selectRaw('product_variant_recipes.product_variant_id, SUM(ingredients.cost_price * product_variant_recipes.quantity) as hpp')
                ->join('ingredients', 'ingredients.id', '=', 'product_variant_recipes.ingredient_id')
                ->whereIn('product_variant_recipes.product_variant_id', $variantIds)
                ->groupBy('product_variant_recipes.product_variant_id')
                ->get();

            foreach ($hppRows as $row) {
                $variantId = (int) $row->product_variant_id;
                $hpp = (int) round((float) $row->hpp);
                ProductVariant::query()
                    ->whereKey($variantId)
                    ->update(['hpp' => $hpp]);
            }

            $packageProductIds = ProductPackageItem::query()
                ->whereIn('component_product_variant_id', $variantIds)
                ->distinct()
                ->pluck('package_product_id')
                ->map(fn ($id) => (int) $id)
                ->filter(fn (int $id) => $id > 0)
                ->values()
                ->all();

            foreach ($packageProductIds as $packageProductId) {
                $packageHpp = (int) round((float) ProductPackageItem::query()
                    ->where('package_product_id', $packageProductId)
                    ->join('product_variants', 'product_variants.id', '=', 'product_package_items.component_product_variant_id')
                    ->selectRaw('SUM(product_variants.hpp * product_package_items.quantity) as hpp')
                    ->value('hpp'));

                ProductVariant::query()
                    ->where('product_id', $packageProductId)
                    ->update(['hpp' => $packageHpp]);
            }
        });
    }
}
