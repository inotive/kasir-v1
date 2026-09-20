<?php

use App\Livewire\Pos\PosPage;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('pos import transaction code requires transactions.details', function () {
    $this->seed(RolePermissionSeeder::class);

    $guard = (string) config('auth.defaults.guard', 'web');
    $role = Role::findOrCreate('pos_only', $guard);
    $role->syncPermissions(['pos.access']);

    $user = User::factory()->create();
    $user->assignRole('pos_only');

    $trx = Transaction::query()->create([
        'code' => 'TRX-POS-SCAN-001',
        'external_id' => 'EXT-POS-SCAN-001',
        'name' => 'Customer',
        'checkout_link' => '',
        'subtotal' => 10000,
        'total' => 10000,
        'payment_method' => 'pending',
        'payment_status' => 'pending',
        'channel' => 'pos',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(PosPage::class)
        ->set('scanCode', $trx->code)
        ->call('importTransactionCode')
        ->assertStatus(403);
});

test('pos checkout ignores tampered cart item price and uses server price', function () {
    $this->seed(RolePermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('cashier');

    $category = Category::query()->create(['name' => 'Food']);
    $product = Product::query()->create([
        'name' => 'Produk A',
        'description' => 'Desc',
        'image' => 'test.jpg',
        'price' => 50000,
        'is_available' => true,
        'category_id' => $category->id,
    ]);
    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'name' => 'Standard',
        'price' => 50000,
        'stock' => 10,
        'hpp' => 0,
    ]);

    Livewire::actingAs($user)
        ->test(PosPage::class)
        ->call('addVariantToCart', $variant->id)
        ->set('cartItems.0.price', 1)
        ->call('openCheckout')
        ->set('customerName', 'Budi')
        ->set('paymentMethod', 'cash')
        ->set('cashReceived', 100000)
        ->call('checkout')
        ->assertHasNoErrors();

    $trx = Transaction::query()->latest('id')->first();
    expect($trx)->not->toBeNull();

    $item = TransactionItem::query()->where('transaction_id', $trx->id)->first();
    expect($item)->not->toBeNull();
    expect((int) $item->price)->toBe(50000);
    expect((int) $item->subtotal)->toBe(50000);
});
