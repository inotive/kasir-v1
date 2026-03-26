<?php

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantRecipe;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Services\Inventory\InventoryService;

it('stores unit_cost on sale_consumption and keeps it on reversal', function () {
    $category = Category::query()->create(['name' => 'Food']);

    $ingredient = Ingredient::query()->create([
        'name' => 'Garam',
        'unit' => 'gr',
        'cost_price' => 250,
        'is_active' => true,
    ]);

    InventoryMovement::query()->create([
        'ingredient_id' => $ingredient->id,
        'supplier_id' => null,
        'type' => 'opening_balance',
        'quantity' => 100,
        'unit_cost' => 250,
        'reference_type' => null,
        'reference_id' => null,
        'note' => null,
        'happened_at' => now(),
    ]);

    $product = Product::query()->create([
        'name' => 'Sup',
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
        'hpp' => 0,
    ]);

    ProductVariantRecipe::query()->create([
        'product_variant_id' => $variant->id,
        'ingredient_id' => $ingredient->id,
        'quantity' => 2,
    ]);

    $transaction = Transaction::query()->create([
        'code' => 'TRX-UNITCOST-001',
        'external_id' => 'EXT-UNITCOST-001',
        'name' => 'Walk-in',
        'checkout_link' => '',
        'subtotal' => 10000,
        'total' => 10000,
        'payment_method' => 'cash',
        'payment_status' => 'pending',
    ]);

    TransactionItem::query()->create([
        'transaction_id' => $transaction->id,
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'quantity' => 1,
        'price' => 10000,
        'subtotal' => 10000,
    ]);

    $transaction->load('transactionItems.variant.recipes');
    expect($transaction->transactionItems)->toHaveCount(1);
    expect($transaction->transactionItems->first()->variant)->not->toBeNull();
    expect($transaction->transactionItems->first()->variant->recipes)->toHaveCount(1);

    $transaction->update(['payment_status' => 'paid']);

    $consumption = InventoryMovement::query()
        ->where('reference_type', 'transactions')
        ->where('reference_id', $transaction->id)
        ->where('type', 'sale_consumption')
        ->firstOrFail();

    expect((float) $consumption->quantity)->toBe(-2.0);
    expect((float) $consumption->unit_cost)->toBe(250.0);

    app(InventoryService::class)->reverseTransaction($transaction->fresh(), 'Void');

    $reversal = InventoryMovement::query()
        ->where('reference_type', 'transactions')
        ->where('reference_id', $transaction->id)
        ->where('type', 'sale_reversal')
        ->firstOrFail();

    expect((float) $reversal->quantity)->toBe(2.0);
    expect((float) $reversal->unit_cost)->toBe(250.0);
});
