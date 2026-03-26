<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use App\Services\Printing\PosPrintPayloadService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('print payload includes cashier receipt logo toggle setting', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $category = Category::query()->create(['name' => 'Food']);
    $product = Product::query()->create([
        'name' => 'Produk A',
        'description' => 'Desc',
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
        'name' => 'Reg',
        'price' => 10000,
        'hpp' => 1000,
    ]);

    $trx = Transaction::query()->create([
        'code' => 'TRX-PRINT-LOGO-001',
        'external_id' => 'EXT-PRINT-LOGO-001',
        'name' => 'Customer',
        'checkout_link' => '',
        'subtotal' => 10000,
        'total' => 10000,
        'payment_method' => 'cash',
        'payment_status' => 'paid',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    TransactionItem::query()->create([
        'transaction_id' => $trx->id,
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'quantity' => 1,
        'price' => 10000,
        'subtotal' => 10000,
    ]);

    $setting = Setting::current();
    $setting->cashier_receipt_print_logo = false;
    $setting->save();

    $payload = app(PosPrintPayloadService::class)->build($trx->id);
    expect($payload)->not->toBeNull();
    expect((bool) ($payload['store']['cashier_receipt_print_logo'] ?? true))->toBeFalse();

    $setting->cashier_receipt_print_logo = true;
    $setting->save();

    $payload2 = app(PosPrintPayloadService::class)->build($trx->id);
    expect($payload2)->not->toBeNull();
    expect((bool) ($payload2['store']['cashier_receipt_print_logo'] ?? false))->toBeTrue();
});
