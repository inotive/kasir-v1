<?php

use App\Livewire\Product\AddonsPage;
use App\Models\Addon;
use App\Models\AddonCategory;
use App\Models\AddonRecipe;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantRecipe;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\TransactionItemAddon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function makeAddonRecipeIngredient(string $name, float $cost): Ingredient
{
    return Ingredient::query()->create([
        'name' => $name,
        'sku' => 'SKU-'.$name,
        'unit' => 'pcs',
        'cost_price' => $cost,
        'reorder_level' => 0,
        'is_active' => true,
    ]);
}

function makePendingAddonTransaction(array $overrides = []): Transaction
{
    return Transaction::query()->create(array_merge([
        'code' => (string) Str::uuid(),
        'channel' => 'pos',
        'name' => 'Test',
        'subtotal' => 10000,
        'total' => 10000,
        'checkout_link' => '-',
        'payment_method' => 'cash',
        'payment_status' => 'pending',
        'external_id' => 'x',
    ], $overrides));
}

function makeSimpleAddonProduct(): array
{
    $category = Category::query()->create(['name' => 'Food']);

    $product = Product::query()->create([
        'name' => 'Nasi Goreng',
        'description' => 'desc',
        'image' => '',
        'is_available' => true,
        'is_promo' => false,
        'is_favorite' => false,
        'is_package' => false,
        'category_id' => $category->id,
        'printer_source_id' => null,
    ]);

    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'name' => 'Regular',
        'price' => 10000,
        'percent' => null,
        'price_afterdiscount' => null,
        'hpp' => 0,
    ]);

    return [$product, $variant];
}

test('addon recipes deduct stock and roll into HPP on paid transaction', function () {
    $cheese = makeAddonRecipeIngredient('Cheese', 5000);
    $milk = makeAddonRecipeIngredient('Milk', 2000);

    [$product, $variant] = makeSimpleAddonProduct();

    ProductVariantRecipe::query()->create([
        'product_variant_id' => $variant->id,
        'ingredient_id' => $cheese->id,
        'quantity' => 0.5,
    ]);

    $cat = AddonCategory::query()->create(['name' => 'Extra']);
    $addon = Addon::query()->create([
        'name' => 'Extra Milk',
        'addon_category_id' => $cat->id,
        'price' => 3000,
        'is_available' => true,
    ]);
    AddonRecipe::query()->create([
        'addon_id' => $addon->id,
        'ingredient_id' => $milk->id,
        'quantity' => 2,
    ]);

    $trx = makePendingAddonTransaction();

    $item = TransactionItem::query()->create([
        'transaction_id' => $trx->id,
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'quantity' => 2,
        'price' => 10000,
        'subtotal' => 20000,
        'note' => null,
    ]);

    TransactionItemAddon::query()->create([
        'transaction_item_id' => $item->id,
        'addon_id' => $addon->id,
        'name' => 'Extra Milk',
        'price' => 3000,
        'quantity' => 2,
    ]);

    $trx->update(['payment_status' => 'paid']);

    $milkUsed = InventoryMovement::query()
        ->where('reference_type', 'transactions')
        ->where('reference_id', $trx->id)
        ->where('type', 'sale_consumption')
        ->where('ingredient_id', $milk->id)
        ->sum('quantity');

    $cheeseUsed = InventoryMovement::query()
        ->where('reference_type', 'transactions')
        ->where('reference_id', $trx->id)
        ->where('type', 'sale_consumption')
        ->where('ingredient_id', $cheese->id)
        ->sum('quantity');

    // addon: 2 pcs milk x addon qty 2 = 4
    expect((float) $milkUsed)->toEqual(-4.0)
        // variant: 0.5 cheese x item qty 2 = 1
        ->and((float) $cheeseUsed)->toEqual(-1.0);

    $item->refresh();

    // hpp_unit = 5000*0.5 + (2000*2*2)/2 = 2500 + 4000 = 6500; total = 13000
    expect((float) $item->hpp_unit)->toEqual(6500.0)
        ->and((float) $item->hpp_total)->toEqual(13000.0);
});

test('child addon consumption scales with parent package quantity', function () {
    $sugar = makeAddonRecipeIngredient('Sugar', 1000);
    $rice = makeAddonRecipeIngredient('Rice', 4000);

    $category = Category::query()->create(['name' => 'Food']);

    $parentProduct = Product::query()->create([
        'name' => 'Paket Hemat',
        'description' => 'desc',
        'image' => '',
        'is_available' => true,
        'is_promo' => false,
        'is_favorite' => false,
        'is_package' => true,
        'package_type' => 'complex',
        'category_id' => $category->id,
        'printer_source_id' => null,
    ]);

    $parentVariant = ProductVariant::query()->create([
        'product_id' => $parentProduct->id,
        'name' => 'Paket',
        'price' => 20000,
        'percent' => null,
        'price_afterdiscount' => null,
        'hpp' => 0,
    ]);

    $childProduct = Product::query()->create([
        'name' => 'Nasi',
        'description' => 'desc',
        'image' => '',
        'is_available' => true,
        'is_promo' => false,
        'is_favorite' => false,
        'is_package' => false,
        'category_id' => $category->id,
        'printer_source_id' => null,
    ]);

    $childVariant = ProductVariant::query()->create([
        'product_id' => $childProduct->id,
        'name' => 'Putih',
        'price' => 0,
        'percent' => null,
        'price_afterdiscount' => null,
        'hpp' => 0,
    ]);

    ProductVariantRecipe::query()->create([
        'product_variant_id' => $childVariant->id,
        'ingredient_id' => $rice->id,
        'quantity' => 1,
    ]);

    $cat = AddonCategory::query()->create(['name' => 'Extra']);
    $addon = Addon::query()->create([
        'name' => 'Extra Sugar',
        'addon_category_id' => $cat->id,
        'price' => 1000,
        'is_available' => true,
    ]);
    AddonRecipe::query()->create([
        'addon_id' => $addon->id,
        'ingredient_id' => $sugar->id,
        'quantity' => 3,
    ]);

    $trx = makePendingAddonTransaction();

    $parent = TransactionItem::query()->create([
        'transaction_id' => $trx->id,
        'product_id' => $parentProduct->id,
        'product_variant_id' => $parentVariant->id,
        'quantity' => 2,
        'price' => 20000,
        'subtotal' => 40000,
        'note' => null,
    ]);

    $child = TransactionItem::query()->create([
        'transaction_id' => $trx->id,
        'parent_transaction_item_id' => $parent->id,
        'product_id' => $childProduct->id,
        'product_variant_id' => $childVariant->id,
        'quantity' => 2,
        'price' => 0,
        'subtotal' => 0,
        'note' => null,
    ]);

    TransactionItemAddon::query()->create([
        'transaction_item_id' => $child->id,
        'addon_id' => $addon->id,
        'name' => 'Extra Sugar',
        'price' => 1000,
        'quantity' => 1,
    ]);

    $trx->update(['payment_status' => 'paid']);

    $sugarUsed = InventoryMovement::query()
        ->where('reference_type', 'transactions')
        ->where('reference_id', $trx->id)
        ->where('type', 'sale_consumption')
        ->where('ingredient_id', $sugar->id)
        ->sum('quantity');

    // child addon stored qty 1 x parent qty 2 x recipe 3 = 6
    expect((float) $sugarUsed)->toEqual(-6.0);

    $child->refresh();

    // child hpp_unit = 4000*1 + (1000*3*1*2)/2 = 4000 + 3000 = 7000
    expect((float) $child->hpp_unit)->toEqual(7000.0)
        ->and((float) $child->hpp_total)->toEqual(14000.0);
});

test('addon without recipes does not block inventory and deducts nothing', function () {
    $cheese = makeAddonRecipeIngredient('Cheese', 5000);

    [$product, $variant] = makeSimpleAddonProduct();

    ProductVariantRecipe::query()->create([
        'product_variant_id' => $variant->id,
        'ingredient_id' => $cheese->id,
        'quantity' => 1,
    ]);

    $cat = AddonCategory::query()->create(['name' => 'Extra']);
    $addon = Addon::query()->create([
        'name' => 'Plain Extra',
        'addon_category_id' => $cat->id,
        'price' => 1000,
        'is_available' => true,
    ]);

    $trx = makePendingAddonTransaction();

    $item = TransactionItem::query()->create([
        'transaction_id' => $trx->id,
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'quantity' => 1,
        'price' => 10000,
        'subtotal' => 10000,
        'note' => null,
    ]);

    TransactionItemAddon::query()->create([
        'transaction_item_id' => $item->id,
        'addon_id' => $addon->id,
        'name' => 'Plain Extra',
        'price' => 1000,
        'quantity' => 1,
    ]);

    $trx->update(['payment_status' => 'paid']);

    expect($trx->fresh()->inventory_applied_at)->not->toBeNull();

    $count = InventoryMovement::query()
        ->where('reference_type', 'transactions')
        ->where('reference_id', $trx->id)
        ->where('type', 'sale_consumption')
        ->count();

    // only the variant cheese movement
    expect($count)->toBe(1);
});

test('recipe-less product does not block healthy addon deduction', function () {
    $sugar = makeAddonRecipeIngredient('Sugar', 1000);

    [$product, $variant] = makeSimpleAddonProduct();
    // no variant recipe on purpose

    $cat = AddonCategory::query()->create(['name' => 'Extra']);
    $addon = Addon::query()->create([
        'name' => 'Extra Sugar',
        'addon_category_id' => $cat->id,
        'price' => 1000,
        'is_available' => true,
    ]);
    AddonRecipe::query()->create([
        'addon_id' => $addon->id,
        'ingredient_id' => $sugar->id,
        'quantity' => 2,
    ]);

    $trx = makePendingAddonTransaction();

    $item = TransactionItem::query()->create([
        'transaction_id' => $trx->id,
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'quantity' => 1,
        'price' => 10000,
        'subtotal' => 10000,
        'note' => null,
    ]);

    TransactionItemAddon::query()->create([
        'transaction_item_id' => $item->id,
        'addon_id' => $addon->id,
        'name' => 'Extra Sugar',
        'price' => 1000,
        'quantity' => 1,
    ]);

    $trx->update(['payment_status' => 'paid']);

    expect($trx->fresh()->inventory_applied_at)->not->toBeNull();

    $sugarUsed = InventoryMovement::query()
        ->where('reference_type', 'transactions')
        ->where('reference_id', $trx->id)
        ->where('type', 'sale_consumption')
        ->where('ingredient_id', $sugar->id)
        ->sum('quantity');

    expect((float) $sugarUsed)->toEqual(-2.0);

    $item->refresh();

    // product contributes zero, addon folds into unit: (1000*2*1)/1 = 2000
    expect((float) $item->hpp_unit)->toEqual(2000.0)
        ->and((float) $item->hpp_total)->toEqual(2000.0);
});

test('transaction with no recipes anywhere applies cleanly with zero movements', function () {
    [$product, $variant] = makeSimpleAddonProduct();

    $cat = AddonCategory::query()->create(['name' => 'Extra']);
    $addon = Addon::query()->create([
        'name' => 'Plain Extra',
        'addon_category_id' => $cat->id,
        'price' => 1000,
        'is_available' => true,
    ]);

    $trx = makePendingAddonTransaction();

    $item = TransactionItem::query()->create([
        'transaction_id' => $trx->id,
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'quantity' => 1,
        'price' => 10000,
        'subtotal' => 10000,
        'note' => null,
    ]);

    TransactionItemAddon::query()->create([
        'transaction_item_id' => $item->id,
        'addon_id' => $addon->id,
        'name' => 'Plain Extra',
        'price' => 1000,
        'quantity' => 1,
    ]);

    $trx->update(['payment_status' => 'paid']);

    expect($trx->fresh()->inventory_applied_at)->not->toBeNull();

    $count = InventoryMovement::query()
        ->where('reference_type', 'transactions')
        ->where('reference_id', $trx->id)
        ->where('type', 'sale_consumption')
        ->count();

    expect($count)->toBe(0);

    $skipped = app(\App\Services\Inventory\InventoryService::class)
        ->applyTransaction($trx->fresh());

    // already applied: idempotent re-run returns empty
    expect($skipped)->toBe([]);
});

test('addon form rejects duplicate recipe ingredients in Indonesian', function () {
    $flour = makeAddonRecipeIngredient('Flour', 3000);
    $cat = AddonCategory::query()->create(['name' => 'Extra']);

    $component = Livewire::test(AddonsPage::class)
        ->call('openAddonModal')
        ->set('formName', 'Extra Flour')
        ->set('formAddonCategoryId', $cat->id)
        ->set('formPrice', 2000)
        ->call('addFormRecipe')
        ->call('addFormRecipe');

    $recipes = $component->get('formRecipes');

    $component
        ->set('formRecipes.0.ingredient_id', $flour->id)
        ->set('formRecipes.0.quantity', '1')
        ->set('formRecipes.1.ingredient_id', $flour->id)
        ->set('formRecipes.1.quantity', '2')
        ->call('storeAddon')
        ->assertHasErrors(['formRecipes']);

    expect($component->errors()->first('formRecipes'))
        ->toBe('Bahan baku pada resep add-on tidak boleh duplikat.');
    expect(array_keys($recipes))->toEqual([0, 1]);
});
