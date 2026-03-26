<?php

namespace Tests\Feature;

use App\Livewire\Pos\PosPage;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('pos checkout wizard flow works correctly', function () {
    $this->seed(RolePermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('cashier');

    $category = Category::create(['name' => 'Food']);

    $product = Product::create([
        'name' => 'Test Product',
        'description' => 'Test Description',
        'image' => 'test.jpg',
        'price' => 50000,
        'is_available' => true,
        'category_id' => $category->id,
    ]);

    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'name' => 'Standard',
        'price' => 50000,
        'stock' => 10,
        'hpp' => 40000,
    ]);

    Livewire::actingAs($user)
        ->test(PosPage::class)
        ->call('addVariantToCart', $variant->id)
        ->call('openCheckout')
        ->assertSet('checkoutStep', 1)
        ->assertSet('checkoutModalOpen', true)

        // Step 1: Customer Data
        ->set('customerName', '')
        ->call('nextStep')
        ->assertHasErrors(['customerName'])
        ->assertSet('checkoutStep', 1)

        ->set('customerName', 'Budi')
        ->call('nextStep')
        ->assertHasNoErrors()
        ->assertSet('checkoutStep', 2)

        // Step 2: Discount (Optional)
        ->call('nextStep')
        ->assertSet('checkoutStep', 3)

        // Step 3: Payment
        ->set('paymentMethod', 'cash')
        ->set('cashReceived', 0) // Less than total
        ->call('checkout')
        ->assertHasErrors(['cashReceived'])

        ->set('cashReceived', 100000)
        ->call('checkout')
        ->assertHasNoErrors()
        ->assertSet('checkoutModalOpen', false);
});

test('pos checkout wizard navigation works', function () {
    $this->seed(RolePermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('cashier');

    $category = Category::create(['name' => 'Food']);

    $product = Product::create([
        'name' => 'Test Product',
        'description' => 'Test Description',
        'image' => 'test.jpg',
        'price' => 50000,
        'is_available' => true,
        'category_id' => $category->id,
    ]);
    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'name' => 'Standard',
        'price' => 50000,
        'stock' => 10,
        'hpp' => 40000,
    ]);

    Livewire::actingAs($user)
        ->test(PosPage::class)
        ->call('addVariantToCart', $variant->id)
        ->call('openCheckout')
        ->assertSet('checkoutStep', 1)

        ->set('customerName', 'Budi')
        ->call('nextStep')
        ->assertSet('checkoutStep', 2)

        ->call('prevStep')
        ->assertSet('checkoutStep', 1)

        ->call('nextStep')
        ->assertSet('checkoutStep', 2)

        ->call('nextStep')
        ->assertSet('checkoutStep', 3)

        ->call('prevStep')
        ->assertSet('checkoutStep', 2);
});
