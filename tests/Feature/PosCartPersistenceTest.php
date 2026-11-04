<?php

namespace Tests\Feature;

use App\Livewire\Pos\PosPage;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function posPersistenceVariant(bool $available = true): ProductVariant
{
    $category = Category::firstOrCreate(['name' => 'Food']);
    $product = Product::create([
        'name' => 'Produk '.uniqid(),
        'description' => 'Test',
        'image' => 'test.jpg',
        'price' => 50000,
        'is_available' => $available,
        'category_id' => $category->id,
    ]);

    return ProductVariant::create([
        'product_id' => $product->id,
        'name' => 'Standard',
        'price' => 50000,
        'stock' => 10,
        'hpp' => 40000,
    ]);
}

function posPersistenceCashier(): User
{
    test()->seed(RolePermissionSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('cashier');
    test()->actingAs($user);

    return $user;
}

test('cart is restored after the pos page is mounted again', function () {
    posPersistenceCashier();
    $variant = posPersistenceVariant();

    Livewire::test(PosPage::class)
        ->call('addVariantToCart', $variant->id)
        ->set('customerName', 'Budi');

    Livewire::test(PosPage::class)
        ->assertCount('cartItems', 1)
        ->assertSet('customerName', 'Budi')
        ->assertSet('subtotal', 50000);
});

test('cart is empty after clearCart', function () {
    posPersistenceCashier();
    $variant = posPersistenceVariant();

    Livewire::test(PosPage::class)
        ->call('addVariantToCart', $variant->id)
        ->call('clearCart');

    Livewire::test(PosPage::class)->assertCount('cartItems', 0);
});

test('unavailable products are dropped when restoring', function () {
    posPersistenceCashier();
    $variant = posPersistenceVariant();

    Livewire::test(PosPage::class)->call('addVariantToCart', $variant->id);

    $variant->product->update(['is_available' => false]);

    Livewire::test(PosPage::class)->assertCount('cartItems', 0);
});

test('editing transaction id is reset when the transaction is no longer pending', function () {
    posPersistenceCashier();
    $variant = posPersistenceVariant();

    Livewire::test(PosPage::class)->call('addVariantToCart', $variant->id);

    session()->put('pos.draft.editingTransactionId', 999999);

    Livewire::test(PosPage::class)
        ->assertCount('cartItems', 1)
        ->assertSet('editingTransactionId', null);
});

test('draft is not restored when opening pos for a specific table', function () {
    posPersistenceCashier();
    $variant = posPersistenceVariant();

    Livewire::test(PosPage::class)->call('addVariantToCart', $variant->id);

    Livewire::withQueryParams(['type' => 'take_away'])
        ->test(PosPage::class)
        ->assertCount('cartItems', 0);
});
