<?php

use App\Models\Category;
use App\Models\PrinterSource;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Services\Printing\PosPrintPayloadService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('menggabungkan item paket dan addon yang sama pada payload print dapur', function () {
    $source = PrinterSource::query()->create([
        'name' => 'Dapur',
        'type' => 'dapur',
    ]);

    $category = Category::query()->create(['name' => 'Food']);

    $componentProduct = Product::query()->create([
        'name' => 'Nasi Goreng',
        'description' => 'Test',
        'image' => 'test.png',
        'is_available' => true,
        'is_promo' => false,
        'is_favorite' => false,
        'is_package' => false,
        'category_id' => $category->id,
        'printer_source_id' => $source->id,
    ]);

    $componentVariant = ProductVariant::query()->create([
        'product_id' => $componentProduct->id,
        'name' => 'Normal',
        'price' => 10000,
        'hpp' => 0,
    ]);

    $packageProduct = Product::query()->create([
        'name' => 'Paket Hemat',
        'description' => 'Test',
        'image' => 'test.png',
        'is_available' => true,
        'is_promo' => false,
        'is_favorite' => false,
        'is_package' => true,
        'category_id' => $category->id,
        'printer_source_id' => null,
    ]);

    $packageVariant = ProductVariant::query()->create([
        'product_id' => $packageProduct->id,
        'name' => 'Normal',
        'price' => 25000,
        'hpp' => 0,
    ]);

    $trx = Transaction::query()->create([
        'code' => 'TRX-PRINT-001',
        'external_id' => 'EXT-PRINT-001',
        'name' => 'Walk-in',
        'checkout_link' => '',
        'subtotal' => 35000,
        'total' => 35000,
        'payment_method' => 'cash',
        'payment_status' => 'pending',
    ]);

    $parent = TransactionItem::query()->create([
        'transaction_id' => $trx->id,
        'product_id' => $packageProduct->id,
        'product_variant_id' => $packageVariant->id,
        'quantity' => 1,
        'price' => 25000,
        'subtotal' => 25000,
        'note' => null,
    ]);

    TransactionItem::query()->create([
        'transaction_id' => $trx->id,
        'parent_transaction_item_id' => $parent->id,
        'product_id' => $componentProduct->id,
        'product_variant_id' => $componentVariant->id,
        'quantity' => 1,
        'price' => 0,
        'subtotal' => 0,
        'note' => null,
    ]);

    TransactionItem::query()->create([
        'transaction_id' => $trx->id,
        'parent_transaction_item_id' => null,
        'product_id' => $componentProduct->id,
        'product_variant_id' => $componentVariant->id,
        'quantity' => 1,
        'price' => 10000,
        'subtotal' => 10000,
        'note' => null,
    ]);

    $payload = app(PosPrintPayloadService::class)->build($trx->id, 'Kasir');
    expect($payload)->not->toBeNull();

    $bySource = (array) ($payload['items_by_source'] ?? []);
    expect($bySource)->toHaveKey((string) $source->id);

    $items = $bySource[(string) $source->id];
    expect($items)->toHaveCount(1);
    expect((int) $items[0]['quantity'])->toBe(2);
});
