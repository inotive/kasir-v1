<?php

namespace Tests\Feature;

use App\Livewire\Pos\PosPage;
use App\Models\Category;
use App\Models\DiningTable;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('pos imports self-order pending transaction with voucher info', function () {
    $this->seed(RolePermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('cashier');

    $table = DiningTable::query()->create([
        'table_number' => 'A1',
        'image' => null,
        'qr_value' => null,
    ]);

    $category = Category::query()->create(['name' => 'Food']);
    $product = Product::query()->create([
        'name' => 'Test Product',
        'description' => 'Test',
        'image' => 'test.jpg',
        'is_available' => true,
        'is_promo' => false,
        'is_favorite' => false,
        'category_id' => $category->id,
        'printer_source_id' => null,
    ]);
    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'name' => 'Standard',
        'price' => 10000,
        'price_afterdiscount' => null,
        'percent' => null,
        'hpp' => 0,
    ]);

    $trx = Transaction::query()->create([
        'code' => Transaction::generateUniqueCode(),
        'member_id' => null,
        'channel' => 'self_order',
        'name' => 'Guest',
        'phone' => '628123',
        'email' => null,
        'order_type' => 'dine_in',
        'dining_table_id' => $table->id,
        'voucher_campaign_id' => null,
        'voucher_code_id' => null,
        'voucher_code' => 'TEST10',
        'subtotal' => 10000,
        'voucher_discount_amount' => 1000,
        'discount_total_amount' => 1000,
        'tax_percentage' => 0,
        'tax_amount' => 0,
        'rounding_amount' => 0,
        'total' => 9000,
        'checkout_link' => '-',
        'payment_method' => 'cash',
        'payment_status' => 'pending',
        'order_status' => 'new',
        'external_id' => (string) Str::uuid(),
        'self_order_token' => (string) Str::random(40),
        'payment_session_hash' => null,
        'cart_hash' => null,
        'payment_intent_hash' => null,
    ]);

    TransactionItem::query()->create([
        'transaction_id' => $trx->id,
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'quantity' => 1,
        'price' => 10000,
        'subtotal' => 10000,
        'voucher_discount_amount' => 1000,
        'manual_discount_amount' => 0,
        'note' => null,
    ]);

    Livewire::actingAs($user)
        ->test(PosPage::class)
        ->set('scanCode', $trx->code)
        ->call('importTransactionCode')
        ->assertSet('cartLocked', true)
        ->assertSet('voucherCodeInput', 'TEST10')
        ->assertSet('voucherDiscountAmount', 1000)
        ->assertSet('total', 9000);
});
