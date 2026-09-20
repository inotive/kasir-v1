<?php

use App\Livewire\Pos\PosPage;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $category = Category::create(['name' => 'Food']);
    $this->product = Product::create([
        'name' => 'Nasi Goreng',
        'description' => 'Test',
        'image' => 'test.png',
        'is_available' => true,
        'category_id' => $category->id,
    ]);
    $this->variant = ProductVariant::create([
        'product_id' => $this->product->id,
        'name' => 'Normal',
        'price' => 100000,
    ]);
});

it('allows user with permission to apply manual discount without approval', function () {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);
    $user->givePermissionTo(['pos.access', 'discounts.manual.apply']);

    Livewire::actingAs($user)
        ->test(PosPage::class)
        ->call('addVariantToCart', $this->variant->id)
        ->set('manualDiscountType', 'percent')
        ->set('manualDiscountValue', 10) // 10%
        ->assertSet('manualDiscountAmount', 10000)
        ->set('customerName', 'Guest')
        ->set('paymentMethod', 'cash')
        ->set('cashReceived', 90000)
        ->call('checkout')
        ->assertHasNoErrors();

    $trx = Transaction::latest('id')->first();
    expect($trx)->not->toBeNull();
    expect((int) $trx->manual_discount_amount)->toBe(10000);
    expect($trx->manual_discount_type)->toBe('percent');
    expect((int) $trx->manual_discount_value)->toBe(10);
});

it('saves discount type fixed amount correctly', function () {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);
    $user->givePermissionTo(['pos.access', 'discounts.manual.apply']);

    Livewire::actingAs($user)
        ->test(PosPage::class)
        ->call('addVariantToCart', $this->variant->id)
        ->set('manualDiscountType', 'fixed_amount')
        ->set('manualDiscountValue', 15000)
        ->assertSet('manualDiscountAmount', 15000)
        ->set('customerName', 'Guest')
        ->set('paymentMethod', 'cash')
        ->set('cashReceived', 85000)
        ->call('checkout')
        ->assertHasNoErrors();

    $trx = Transaction::latest('id')->first();
    expect((int) $trx->manual_discount_amount)->toBe(15000);
    expect($trx->manual_discount_type)->toBe('fixed_amount');
    expect((int) $trx->manual_discount_value)->toBe(15000);
});

it('saves optional note', function () {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);
    $user->givePermissionTo(['pos.access', 'discounts.manual.apply']);

    Livewire::actingAs($user)
        ->test(PosPage::class)
        ->call('addVariantToCart', $this->variant->id)
        ->set('manualDiscountType', 'fixed_amount')
        ->set('manualDiscountValue', 5000)
        ->set('manualDiscountNote', 'Special Discount')
        ->set('customerName', 'Guest')
        ->set('paymentMethod', 'cash')
        ->set('cashReceived', 95000)
        ->call('checkout')
        ->assertHasNoErrors();

    $trx = Transaction::latest('id')->first();
    expect($trx->manual_discount_note)->toBe('Special Discount');
});

it('does not require reason', function () {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);
    $user->givePermissionTo(['pos.access', 'discounts.manual.apply']);

    Livewire::actingAs($user)
        ->test(PosPage::class)
        ->call('addVariantToCart', $this->variant->id)
        ->set('manualDiscountType', 'percent')
        ->set('manualDiscountValue', 10)
        ->set('customerName', 'Guest')
        ->set('paymentMethod', 'cash')
        ->set('cashReceived', 90000)
        ->call('checkout')
        ->assertHasNoErrors();
});

it('prevents user without permission from applying discount', function () {
    $user = User::create([
        'name' => 'No Access User',
        'email' => 'noaccess@example.com',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);
    $user->givePermissionTo('pos.access');

    Livewire::actingAs($user)
        ->test(PosPage::class)
        ->call('addVariantToCart', $this->variant->id)
        ->set('manualDiscountType', 'percent')
        ->set('manualDiscountValue', 10)
        ->set('customerName', 'Guest')
        ->set('paymentMethod', 'cash')
        ->set('cashReceived', 90000)
        ->call('checkout')
        ->assertHasErrors(['manualDiscountType']); // Check validation error key from PosPage
});

it('requires manual discount type when value is filled', function () {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'type-required@example.com',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);
    $user->givePermissionTo(['pos.access', 'discounts.manual.apply']);

    Livewire::actingAs($user)
        ->test(PosPage::class)
        ->call('addVariantToCart', $this->variant->id)
        ->set('manualDiscountType', null)
        ->set('manualDiscountValue', 10)
        ->set('customerName', 'Guest')
        ->set('paymentMethod', 'cash')
        ->set('cashReceived', 100000)
        ->call('checkout')
        ->assertHasErrors(['manualDiscountType']);

    expect(Transaction::count())->toBe(0);
});
