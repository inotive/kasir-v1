<?php

use App\Models\Category;
use App\Models\DiningTable;
use App\Models\Ingredient;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductPackageItem;
use App\Models\ProductVariant;
use App\Models\ProductVariantRecipe;
use App\Models\Transaction;
use App\Services\Inventory\InventoryService;
use Illuminate\Support\Str;

it('membuat item parent + child untuk paket pada self order cashier dan inventory mengikuti komponen', function () {
    $category = Category::query()->create(['name' => 'Food']);

    $table = DiningTable::query()->create([
        'table_number' => 'A1',
        'image' => null,
        'qr_value' => null,
    ]);

    $ingredient = Ingredient::query()->create([
        'name' => 'Beras',
        'sku' => null,
        'unit' => 'pcs',
        'cost_price' => 1000,
        'reorder_level' => 0,
        'is_active' => true,
    ]);

    InventoryMovement::query()->create([
        'ingredient_id' => $ingredient->id,
        'supplier_id' => null,
        'type' => 'opening_balance',
        'quantity' => 100,
        'input_quantity' => null,
        'input_unit' => null,
        'unit_cost' => null,
        'input_unit_cost' => null,
        'reference_type' => null,
        'reference_id' => null,
        'note' => null,
        'happened_at' => now(),
    ]);

    $componentProduct = Product::query()->create([
        'name' => 'Nasi Goreng',
        'description' => 'Test',
        'image' => 'test.png',
        'is_available' => true,
        'is_promo' => false,
        'is_favorite' => false,
        'is_package' => false,
        'category_id' => $category->id,
        'printer_source_id' => null,
    ]);

    $componentVariant = ProductVariant::query()->create([
        'product_id' => $componentProduct->id,
        'name' => 'Normal',
        'price' => 10000,
        'price_afterdiscount' => null,
        'percent' => null,
        'hpp' => 0,
    ]);

    ProductVariantRecipe::query()->create([
        'product_variant_id' => $componentVariant->id,
        'ingredient_id' => $ingredient->id,
        'quantity' => 1,
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
        'price_afterdiscount' => null,
        'percent' => null,
        'hpp' => 0,
    ]);

    ProductPackageItem::query()->create([
        'package_product_id' => $packageProduct->id,
        'component_product_variant_id' => $componentVariant->id,
        'quantity' => 2,
        'sort_order' => 0,
    ]);

    $paymentToken = Str::random(32);
    $csrf = Str::random(40);

    $cart = [[
        'id' => (int) $packageProduct->id,
        'variant_id' => (int) $packageVariant->id,
        'quantity' => 3,
        'selected' => true,
        'note' => '',
    ]];

    $this
        ->withSession([
            '_token' => $csrf,
            'dining_table_id' => $table->id,
            'self_order_token' => Str::random(40),
            'customer_ready' => true,
            'name' => 'Guest',
            'phone' => '628123',
            'cart_items' => $cart,
            'payment_token' => $paymentToken,
        ])
        ->post(route('self-order.payment.pay'), [
            '_token' => $csrf,
            'action' => 'pay',
            'method' => 'cashier',
            'token' => $paymentToken,
        ])
        ->assertRedirect();

    $transaction = Transaction::query()->with('transactionItems')->firstOrFail();

    expect($transaction->transactionItems)->toHaveCount(2);

    $parent = $transaction->transactionItems->firstWhere('parent_transaction_item_id', null);
    $child = $transaction->transactionItems->firstWhere('parent_transaction_item_id', '!=', null);

    expect((int) $parent->product_id)->toBe((int) $packageProduct->id);
    expect((int) $parent->product_variant_id)->toBe((int) $packageVariant->id);
    expect((int) $parent->quantity)->toBe(3);

    expect((int) $child->parent_transaction_item_id)->toBe((int) $parent->id);
    expect((int) $child->product_id)->toBe((int) $componentProduct->id);
    expect((int) $child->product_variant_id)->toBe((int) $componentVariant->id);
    expect((int) $child->quantity)->toBe(6);
    expect((int) $child->price)->toBe(0);
    expect((int) $child->subtotal)->toBe(0);

    app(InventoryService::class)->applyTransaction($transaction->fresh());

    $transaction = Transaction::query()->with('transactionItems')->whereKey($transaction->id)->firstOrFail();
    $parent = $transaction->transactionItems->firstWhere('parent_transaction_item_id', null);
    $child = $transaction->transactionItems->firstWhere('parent_transaction_item_id', '!=', null);

    expect((float) $parent->hpp_total)->toBe(6000.0);
    expect((float) $parent->hpp_unit)->toBe(2000.0);
    expect((float) $child->hpp_total)->toBe(6000.0);
    expect((float) $child->hpp_unit)->toBe(1000.0);

    $movements = InventoryMovement::query()
        ->where('reference_type', 'transactions')
        ->where('reference_id', $transaction->id)
        ->where('type', 'sale_consumption')
        ->get();

    expect($movements)->toHaveCount(1);
    expect((float) $movements->first()->quantity)->toBe(-6.0);
    expect((float) $movements->first()->unit_cost)->toBe(1000.0);
});
