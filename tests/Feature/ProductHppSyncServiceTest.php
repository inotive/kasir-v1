<?php

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\ProductPackageItem;
use App\Models\ProductVariant;
use App\Models\ProductVariantRecipe;
use App\Services\Product\ProductHppSyncService;

it('recalculates hpp for variants that use the updated ingredient', function () {
    $category = Category::query()->create(['name' => 'Food']);

    $ingA = Ingredient::query()->create([
        'name' => 'A',
        'unit' => 'gr',
        'cost_price' => 1000,
        'reorder_level' => 0,
        'is_active' => true,
    ]);
    $ingB = Ingredient::query()->create([
        'name' => 'B',
        'unit' => 'gr',
        'cost_price' => 2000,
        'reorder_level' => 0,
        'is_active' => true,
    ]);

    $product = Product::query()->create([
        'name' => 'Nasi',
        'description' => 'Desc',
        'image' => '',
        'category_id' => $category->id,
        'printer_source_id' => null,
        'is_available' => true,
        'is_promo' => false,
        'is_favorite' => false,
        'is_package' => false,
    ]);

    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'name' => 'Reg',
        'price' => 10000,
        'hpp' => 4000,
    ]);

    ProductVariantRecipe::query()->create([
        'product_variant_id' => $variant->id,
        'ingredient_id' => $ingA->id,
        'quantity' => 2,
    ]);
    ProductVariantRecipe::query()->create([
        'product_variant_id' => $variant->id,
        'ingredient_id' => $ingB->id,
        'quantity' => 1,
    ]);

    $ingA->update(['cost_price' => 1500]);

    app(ProductHppSyncService::class)->syncForIngredient($ingA->id);

    $variant->refresh();
    expect((float) $variant->hpp)->toBe(5000.0);
});

it('recalculates package variant hpp when a component variant hpp changes via ingredient update', function () {
    $category = Category::query()->create(['name' => 'Food']);

    $ingA = Ingredient::query()->create([
        'name' => 'A',
        'unit' => 'gr',
        'cost_price' => 1000,
        'reorder_level' => 0,
        'is_active' => true,
    ]);

    $componentProduct = Product::query()->create([
        'name' => 'Ayam',
        'description' => 'Desc',
        'image' => '',
        'category_id' => $category->id,
        'printer_source_id' => null,
        'is_available' => true,
        'is_promo' => false,
        'is_favorite' => false,
        'is_package' => false,
    ]);

    $componentVariant = ProductVariant::query()->create([
        'product_id' => $componentProduct->id,
        'name' => 'Reg',
        'price' => 10000,
        'hpp' => 1000,
    ]);

    ProductVariantRecipe::query()->create([
        'product_variant_id' => $componentVariant->id,
        'ingredient_id' => $ingA->id,
        'quantity' => 1,
    ]);

    $packageProduct = Product::query()->create([
        'name' => 'Paket Ayam',
        'description' => 'Desc',
        'image' => '',
        'category_id' => $category->id,
        'printer_source_id' => null,
        'is_available' => true,
        'is_promo' => false,
        'is_favorite' => false,
        'is_package' => true,
    ]);

    $packageVariant = ProductVariant::query()->create([
        'product_id' => $packageProduct->id,
        'name' => 'Paket',
        'price' => 15000,
        'hpp' => 2000,
    ]);

    ProductPackageItem::query()->create([
        'package_product_id' => $packageProduct->id,
        'component_product_variant_id' => $componentVariant->id,
        'quantity' => 2,
        'sort_order' => 0,
    ]);

    $ingA->update(['cost_price' => 1500]);

    app(ProductHppSyncService::class)->syncForIngredient($ingA->id);

    $componentVariant->refresh();
    $packageVariant->refresh();

    expect((float) $componentVariant->hpp)->toBe(1500.0);
    expect((float) $packageVariant->hpp)->toBe(3000.0);
});
