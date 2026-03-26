<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use App\Services\Printing\PosPrintPayloadService;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\Models\Role;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('print payload masks customer name without transactions.pii.view', function () {
    $this->seed(RolePermissionSeeder::class);

    $guard = (string) config('auth.defaults.guard', 'web');
    $role = Role::findOrCreate('trx_print_no_pii', $guard);
    $role->syncPermissions(['transactions.print']);

    $user = User::factory()->create();
    $user->assignRole('trx_print_no_pii');

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
        'code' => 'TRX-PRINT-001',
        'external_id' => 'EXT-PRINT-001',
        'name' => 'Nama Rahasia',
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

    $this->actingAs($user);

    $payload = app(PosPrintPayloadService::class)->build($trx->id);
    expect($payload)->not->toBeNull();
    expect((string) ($payload['customer_name'] ?? ''))->toBe('-');
});

test('print payload shows customer name with transactions.pii.view', function () {
    $this->seed(RolePermissionSeeder::class);

    $guard = (string) config('auth.defaults.guard', 'web');
    $role = Role::findOrCreate('trx_print_with_pii', $guard);
    $role->syncPermissions(['transactions.print', 'transactions.pii.view']);

    $user = User::factory()->create();
    $user->assignRole('trx_print_with_pii');

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
        'code' => 'TRX-PRINT-002',
        'external_id' => 'EXT-PRINT-002',
        'name' => 'Nama Rahasia',
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

    $this->actingAs($user);

    $payload = app(PosPrintPayloadService::class)->build($trx->id);
    expect($payload)->not->toBeNull();
    expect((string) ($payload['customer_name'] ?? ''))->toBe('Nama Rahasia');
});
