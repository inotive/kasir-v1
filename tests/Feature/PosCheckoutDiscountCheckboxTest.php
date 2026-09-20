<?php

use App\Livewire\Pos\PosPage;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Printing\PosPrintPayloadService;
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

it('checkbox discount flow: toggle, percent, total akhir, db and receipt', function () {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'checkboxtest@example.com',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);
    $user->givePermissionTo(['pos.access', 'discounts.manual.apply']);

    $component = Livewire::actingAs($user)
        ->test(PosPage::class)
        ->call('addVariantToCart', $this->variant->id)
        ->assertSet('applyManualDiscount', false)
        ->assertSet('manualDiscountAmount', 0)
        ->set('checkoutModalOpen', true)
        ->assertSee('Diskon')
        ->set('applyManualDiscount', true)
        ->assertSet('manualDiscountType', null)
        ->assertSet('manualDiscountValue', null)
        ->assertSet('manualDiscountAmount', 0)
        ->assertSee('Jenis Diskon')
        ->assertSee('Pilih Jenis Diskon')
        ->set('manualDiscountType', 'percent')
        ->set('manualDiscountValue', 10)
        ->assertSet('manualDiscountAmount', 10000)
        ->assertSet('discountTotalAmount', 10000)
        ->assertSee('Total Diskon')
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
    expect((int) $trx->discount_total_amount)->toBe(10000);
    expect((int) $trx->total)->toBe(90000);

    $payload = app(PosPrintPayloadService::class)->build((int) $trx->id, 'Kasir');
    expect((int) ($payload['order']['manual_discount_amount'] ?? 0))->toBe(10000);
    expect((int) ($payload['order']['total'] ?? 0))->toBe(90000);
});

it('nominal discount via checkbox is capped and unchecking clears it', function () {
    $user = User::create([
        'name' => 'Test User 2',
        'email' => 'checkboxtest2@example.com',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);
    $user->givePermissionTo(['pos.access', 'discounts.manual.apply']);

    Livewire::actingAs($user)
        ->test(PosPage::class)
        ->call('addVariantToCart', $this->variant->id)
        ->set('applyManualDiscount', true)
        ->set('manualDiscountType', 'fixed_amount')
        ->set('manualDiscountValue', 200000) // over subtotal, capped
        ->assertSet('manualDiscountAmount', 100000)
        ->set('applyManualDiscount', false)
        ->assertSet('manualDiscountAmount', 0)
        ->assertSet('discountTotalAmount', 0)
        ->set('customerName', 'Guest')
        ->set('paymentMethod', 'cash')
        ->set('cashReceived', 100000)
        ->call('checkout')
        ->assertHasNoErrors();

    $trx = Transaction::latest('id')->first();
    expect((int) $trx->manual_discount_amount)->toBe(0);
    expect($trx->manual_discount_type)->toBeNull();
});

it('checking discount with zero value checks out as no discount', function () {
    $user = User::create([
        'name' => 'Test User 3',
        'email' => 'checkboxtest3@example.com',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);
    $user->givePermissionTo(['pos.access', 'discounts.manual.apply']);

    Livewire::actingAs($user)
        ->test(PosPage::class)
        ->call('addVariantToCart', $this->variant->id)
        ->set('applyManualDiscount', true)
        ->assertSet('manualDiscountType', null)
        ->assertSet('manualDiscountValue', null)
        ->set('customerName', 'Guest')
        ->set('paymentMethod', 'cash')
        ->set('cashReceived', 100000)
        ->call('checkout')
        ->assertHasNoErrors();

    $trx = Transaction::latest('id')->first();
    expect($trx)->not->toBeNull();
    expect((int) $trx->manual_discount_amount)->toBe(0);
    expect($trx->manual_discount_type)->toBeNull();
    expect((int) $trx->total)->toBe(100000);
});

it('rejects percent above 100 at checkout', function () {
    $user = User::create([
        'name' => 'Test User 4',
        'email' => 'checkboxtest4@example.com',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);
    $user->givePermissionTo(['pos.access', 'discounts.manual.apply']);

    Livewire::actingAs($user)
        ->test(PosPage::class)
        ->call('addVariantToCart', $this->variant->id)
        ->set('applyManualDiscount', true)
        ->set('manualDiscountType', 'percent')
        ->set('manualDiscountValue', 150)
        ->set('customerName', 'Guest')
        ->set('paymentMethod', 'cash')
        ->set('cashReceived', 100000)
        ->call('checkout')
        ->assertHasErrors(['manualDiscountValue']);

    expect(Transaction::count())->toBe(0);
});

it('caps nominal above subtotal at checkout', function () {
    $user = User::create([
        'name' => 'Test User 5',
        'email' => 'checkboxtest5@example.com',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);
    $user->givePermissionTo(['pos.access', 'discounts.manual.apply']);

    Livewire::actingAs($user)
        ->test(PosPage::class)
        ->call('addVariantToCart', $this->variant->id)
        ->set('applyManualDiscount', true)
        ->set('manualDiscountType', 'fixed_amount')
        ->set('manualDiscountValue', 200000)
        ->assertSet('manualDiscountAmount', 100000)
        ->set('customerName', 'Guest')
        ->set('paymentMethod', 'cash')
        ->set('cashReceived', 0)
        ->call('checkout')
        ->assertHasNoErrors();

    $trx = Transaction::latest('id')->first();
    expect($trx)->not->toBeNull();
    expect((int) $trx->manual_discount_amount)->toBe(100000);
    expect((int) $trx->total)->toBe(0);
});

it('normalizes float discount value without crashing', function () {
    $user = User::create([
        'name' => 'Test User 6',
        'email' => 'checkboxtest6@example.com',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);
    $user->givePermissionTo(['pos.access', 'discounts.manual.apply']);

    Livewire::actingAs($user)
        ->test(PosPage::class)
        ->call('addVariantToCart', $this->variant->id)
        ->set('applyManualDiscount', true)
        ->set('manualDiscountType', 'percent')
        ->set('manualDiscountValue', 10.5)
        ->assertSet('manualDiscountValue', 10)
        ->assertSet('manualDiscountAmount', 10000)
        ->set('customerName', 'Guest')
        ->set('paymentMethod', 'cash')
        ->set('cashReceived', 90000)
        ->call('checkout')
        ->assertHasNoErrors();

    $trx = Transaction::latest('id')->first();
    expect($trx)->not->toBeNull();
    expect((int) $trx->manual_discount_amount)->toBe(10000);
});

it('caps huge nominal value at subtotal without crashing', function () {
    $user = User::create([
        'name' => 'Test User 7',
        'email' => 'checkboxtest7@example.com',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);
    $user->givePermissionTo(['pos.access', 'discounts.manual.apply']);

    Livewire::actingAs($user)
        ->test(PosPage::class)
        ->call('addVariantToCart', $this->variant->id)
        ->set('applyManualDiscount', true)
        ->set('manualDiscountType', 'fixed_amount')
        ->set('manualDiscountValue', 99999999999999999999)
        ->assertSet('manualDiscountAmount', 100000)
        ->set('customerName', 'Guest')
        ->set('paymentMethod', 'cash')
        ->set('cashReceived', 0)
        ->call('checkout')
        ->assertHasNoErrors();

    $trx = Transaction::latest('id')->first();
    expect($trx)->not->toBeNull();
    expect((int) $trx->manual_discount_amount)->toBe(100000);
    expect((int) $trx->total)->toBe(0);
});
