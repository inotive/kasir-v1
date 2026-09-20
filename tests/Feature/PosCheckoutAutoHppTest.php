<?php

namespace Tests\Feature;

use App\Livewire\Pos\PosPage;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantRecipe;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('pos checkout sets hpp automatically after payment', function () {
    $this->seed(RolePermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('cashier');

    $category = Category::create(['name' => 'Food']);

    $ingredient = Ingredient::query()->create([
        'name' => 'Garam',
        'unit' => 'gr',
        'cost_price' => 1000,
        'is_active' => true,
    ]);

    $product = Product::create([
        'name' => 'Sup',
        'description' => 'Desc',
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
        'hpp' => 0,
    ]);

    ProductVariantRecipe::query()->create([
        'product_variant_id' => $variant->id,
        'ingredient_id' => $ingredient->id,
        'quantity' => 2,
    ]);

    Livewire::actingAs($user)
        ->test(PosPage::class)
        ->call('addVariantToCart', $variant->id)
        ->call('openCheckout')
        ->set('customerName', 'Budi')
        ->call('nextStep')
        ->call('nextStep')
        ->set('paymentMethod', 'cash')
        ->set('cashReceived', 100000)
        ->call('checkout')
        ->assertHasNoErrors();

    $trx = Transaction::query()->latest('id')->with('transactionItems')->firstOrFail();
    expect((string) $trx->payment_status)->toBe('paid');
    expect($trx->inventory_applied_at)->not()->toBeNull();
    expect($trx->transactionItems)->toHaveCount(1);

    $item = $trx->transactionItems->first();
    expect((float) $item->hpp_unit)->toBe(2000.0);
    expect((float) $item->hpp_total)->toBe(2000.0);

    $movement = InventoryMovement::query()
        ->where('reference_type', 'transactions')
        ->where('reference_id', $trx->id)
        ->where('type', 'sale_consumption')
        ->first();

    expect($movement)->not()->toBeNull();
    expect((float) $movement->quantity)->toBe(-2.0);
    expect((float) $movement->unit_cost)->toBe(1000.0);
});
