<?php

use App\Livewire\Product\ProductFormPage;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('removing first variant does not reindex variant array keys', function () {
    $component = Livewire::test(ProductFormPage::class)
        ->call('addVariant')
        ->call('addVariant')
        ->call('addVariant');

    $variants = $component->get('variants');
    $firstKey = (string) ($variants[0]['key'] ?? '');

    $component->call('removeVariant', $firstKey);

    $variantsAfter = $component->get('variants');
    expect(array_keys($variantsAfter))->toEqual([1, 2, 3]);
});

test('cannot remove persisted variant that is already used in transactions', function () {
    $category = Category::query()->create(['name' => 'Food']);

    $product = Product::query()->create([
        'name' => 'Nasi Goreng',
        'description' => 'desc',
        'image' => 'x.jpg',
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

    $transaction = Transaction::query()->create([
        'code' => (string) Str::uuid(),
        'channel' => 'pos',
        'name' => 'Test',
        'subtotal' => 10000,
        'total' => 10000,
        'checkout_link' => '-',
        'payment_method' => 'cash',
        'payment_status' => 'paid',
        'external_id' => 'x',
    ]);

    TransactionItem::query()->create([
        'transaction_id' => $transaction->id,
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'quantity' => 1,
        'price' => 10000,
        'hpp_unit' => 0,
        'hpp_total' => 0,
        'subtotal' => 10000,
        'note' => null,
    ]);

    $component = Livewire::test(ProductFormPage::class, ['product' => $product]);
    $variants = $component->get('variants');
    $variantKey = (string) ($variants[0]['key'] ?? '');

    $component
        ->call('removeVariant', $variantKey)
        ->assertHasErrors(['variants']);

    expect($component->get('variants'))->toHaveCount(1);
});

test('removing first package item does not reindex package items array keys', function () {
    $component = Livewire::test(ProductFormPage::class)
        ->set('isPackage', true)
        ->call('addPackageItem')
        ->call('addPackageItem')
        ->call('addPackageItem');

    $items = $component->get('packageItems');
    $firstKey = (string) ($items[0]['key'] ?? '');

    $component->call('removePackageItem', $firstKey);

    $itemsAfter = $component->get('packageItems');
    expect(array_keys($itemsAfter))->toEqual([1, 2, 3]);
});

test('removing first recipe row does not reindex recipes array keys', function () {
    $component = Livewire::test(ProductFormPage::class);

    $variants = $component->get('variants');
    $variantKey = (string) ($variants[0]['key'] ?? '');

    $component
        ->call('addRecipe', $variantKey)
        ->call('addRecipe', $variantKey)
        ->call('addRecipe', $variantKey)
        ->call('addRecipe', $variantKey);

    $recipes = $component->get('variantRecipes')[$variantKey] ?? [];
    $firstRecipeKey = (string) ($recipes[0]['key'] ?? '');

    $component->call('removeRecipe', $variantKey, $firstRecipeKey);

    $recipesAfter = $component->get('variantRecipes')[$variantKey] ?? [];
    expect(array_keys($recipesAfter))->toEqual([1, 2, 3]);
});
