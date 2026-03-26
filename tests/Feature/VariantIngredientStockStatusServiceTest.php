<?php

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductRecipe;
use App\Models\ProductVariant;
use App\Models\ProductVariantRecipe;
use App\Services\Inventory\VariantIngredientStockStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('computes ingredient stock status per product variant', function () {
    $category = Category::create(['name' => 'Food']);

    $product = Product::create([
        'name' => 'Test Product',
        'description' => 'Test',
        'image' => 'test.jpg',
        'is_available' => true,
        'category_id' => $category->id,
    ]);

    $variantMissing = ProductVariant::create([
        'product_id' => $product->id,
        'name' => 'Missing',
        'price' => 10000,
    ]);

    $variantInsufficient = ProductVariant::create([
        'product_id' => $product->id,
        'name' => 'Insufficient',
        'price' => 10000,
    ]);

    $variantLow = ProductVariant::create([
        'product_id' => $product->id,
        'name' => 'Low',
        'price' => 10000,
    ]);

    $variantOk = ProductVariant::create([
        'product_id' => $product->id,
        'name' => 'Ok',
        'price' => 10000,
    ]);

    $ingredientInsufficient = Ingredient::create([
        'name' => 'Ingredient Insufficient',
        'unit' => 'pcs',
        'cost_price' => 0,
        'reorder_level' => 0,
        'is_active' => true,
    ]);

    $ingredientLow = Ingredient::create([
        'name' => 'Ingredient Low',
        'unit' => 'pcs',
        'cost_price' => 0,
        'reorder_level' => 5,
        'is_active' => true,
    ]);

    $ingredientOk = Ingredient::create([
        'name' => 'Ingredient Ok',
        'unit' => 'pcs',
        'cost_price' => 0,
        'reorder_level' => 5,
        'is_active' => true,
    ]);

    InventoryMovement::query()->create([
        'ingredient_id' => $ingredientInsufficient->id,
        'supplier_id' => null,
        'type' => 'purchase',
        'quantity' => 0.5,
        'unit_cost' => null,
        'note' => null,
        'happened_at' => now(),
    ]);

    InventoryMovement::query()->create([
        'ingredient_id' => $ingredientLow->id,
        'supplier_id' => null,
        'type' => 'purchase',
        'quantity' => 5,
        'unit_cost' => null,
        'note' => null,
        'happened_at' => now(),
    ]);

    InventoryMovement::query()->create([
        'ingredient_id' => $ingredientOk->id,
        'supplier_id' => null,
        'type' => 'purchase',
        'quantity' => 10,
        'unit_cost' => null,
        'note' => null,
        'happened_at' => now(),
    ]);

    ProductVariantRecipe::query()->create([
        'product_variant_id' => $variantInsufficient->id,
        'ingredient_id' => $ingredientInsufficient->id,
        'quantity' => 1,
    ]);

    ProductVariantRecipe::query()->create([
        'product_variant_id' => $variantLow->id,
        'ingredient_id' => $ingredientLow->id,
        'quantity' => 1,
    ]);

    ProductVariantRecipe::query()->create([
        'product_variant_id' => $variantOk->id,
        'ingredient_id' => $ingredientOk->id,
        'quantity' => 1,
    ]);

    $statuses = app(VariantIngredientStockStatusService::class)->statusesForVariantIds([
        $variantMissing->id,
        $variantInsufficient->id,
        $variantLow->id,
        $variantOk->id,
    ]);

    expect($statuses)->toMatchArray([
        $variantMissing->id => 'missing_bom',
        $variantInsufficient->id => 'insufficient',
        $variantLow->id => 'low',
        $variantOk->id => 'ok',
    ]);
});

it('falls back to product recipe when variant recipe is missing', function () {
    $category = Category::create(['name' => 'Food']);

    $product = Product::create([
        'name' => 'Fallback Product',
        'description' => 'Test',
        'image' => 'test.jpg',
        'is_available' => true,
        'category_id' => $category->id,
    ]);

    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'name' => 'No Variant Recipe',
        'price' => 10000,
    ]);

    $ingredient = Ingredient::create([
        'name' => 'Ingredient',
        'unit' => 'pcs',
        'cost_price' => 0,
        'reorder_level' => 0,
        'is_active' => true,
    ]);

    InventoryMovement::query()->create([
        'ingredient_id' => $ingredient->id,
        'supplier_id' => null,
        'type' => 'purchase',
        'quantity' => 10,
        'unit_cost' => null,
        'note' => null,
        'happened_at' => now(),
    ]);

    ProductRecipe::query()->create([
        'product_id' => $product->id,
        'ingredient_id' => $ingredient->id,
        'quantity' => 1,
    ]);

    $statuses = app(VariantIngredientStockStatusService::class)->statusesForVariantIds([$variant->id]);

    expect($statuses)->toMatchArray([
        $variant->id => 'ok',
    ]);
});

it('prefers variant recipe over product recipe when both exist', function () {
    $category = Category::create(['name' => 'Food']);

    $product = Product::create([
        'name' => 'Prefer Variant Product',
        'description' => 'Test',
        'image' => 'test.jpg',
        'is_available' => true,
        'category_id' => $category->id,
    ]);

    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'name' => 'Variant Has Recipe',
        'price' => 10000,
    ]);

    $ingredient = Ingredient::create([
        'name' => 'Ingredient Prefer Variant',
        'unit' => 'pcs',
        'cost_price' => 0,
        'reorder_level' => 0,
        'is_active' => true,
    ]);

    InventoryMovement::query()->create([
        'ingredient_id' => $ingredient->id,
        'supplier_id' => null,
        'type' => 'purchase',
        'quantity' => 1,
        'unit_cost' => null,
        'note' => null,
        'happened_at' => now(),
    ]);

    ProductRecipe::query()->create([
        'product_id' => $product->id,
        'ingredient_id' => $ingredient->id,
        'quantity' => 2,
    ]);

    ProductVariantRecipe::query()->create([
        'product_variant_id' => $variant->id,
        'ingredient_id' => $ingredient->id,
        'quantity' => 1,
    ]);

    $statuses = app(VariantIngredientStockStatusService::class)->statusesForVariantIds([$variant->id]);

    expect($statuses)->toMatchArray([
        $variant->id => 'ok',
    ]);
});
