<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use App\Services\Inventory\InventoryService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Validation\ValidationException;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('inventory applyTransaction rejects package parent without child items', function () {
    $this->seed(RolePermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('admin');
    $this->actingAs($user);

    $category = Category::query()->create(['name' => 'Food']);
    $package = Product::query()->create([
        'name' => 'Paket Simple',
        'description' => '-',
        'image' => 'test.png',
        'is_available' => true,
        'is_promo' => false,
        'is_favorite' => false,
        'is_package' => true,
        'package_type' => 'simple',
        'category_id' => $category->id,
        'printer_source_id' => null,
    ]);

    $packageVariant = ProductVariant::query()->create([
        'product_id' => $package->id,
        'name' => 'Normal',
        'price' => 40000,
        'hpp' => 0,
    ]);

    $trx = Transaction::query()->create([
        'code' => 'TRX-PKG-INVALID-001',
        'external_id' => 'EXT-PKG-INVALID-001',
        'name' => 'Walk-in',
        'checkout_link' => '',
        'subtotal' => 40000,
        'total' => 40000,
        'payment_method' => 'pending',
        'payment_status' => 'pending',
        'channel' => 'pos',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    TransactionItem::query()->create([
        'transaction_id' => $trx->id,
        'product_id' => $package->id,
        'product_variant_id' => $packageVariant->id,
        'quantity' => 1,
        'price' => 40000,
        'subtotal' => 40000,
    ]);

    $this->expectException(ValidationException::class);
    app(InventoryService::class)->applyTransaction($trx->fresh(['transactionItems.product', 'transactionItems.variant']));
});
