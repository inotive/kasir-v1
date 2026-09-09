<?php

use App\Livewire\Transaction\TransactionShowPage;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantRecipe;
use App\Models\Transaction;
use App\Models\TransactionEvent;
use App\Models\TransactionItem;
use App\Models\TransactionItemAddon;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function makeDeleteActor(array $permissions): User
{
    $role = Role::firstOrCreate(['name' => 'delete_actor_'.uniqid()]);
    $role->givePermissionTo($permissions);

    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

function makeDeletePaidTransaction(): array
{
    $ingredient = Ingredient::query()->create([
        'name' => 'Flour',
        'sku' => 'FLOUR',
        'unit' => 'pcs',
        'cost_price' => 3000,
        'reorder_level' => 0,
        'is_active' => true,
    ]);

    $category = Category::query()->create(['name' => 'Food']);

    $product = Product::query()->create([
        'name' => 'Roti',
        'description' => 'desc',
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
        'name' => 'Regular',
        'price' => 10000,
        'percent' => null,
        'price_afterdiscount' => null,
        'hpp' => 0,
    ]);

    ProductVariantRecipe::query()->create([
        'product_variant_id' => $variant->id,
        'ingredient_id' => $ingredient->id,
        'quantity' => 2,
    ]);

    $transaction = Transaction::query()->create([
        'code' => 'TRX-DEL-'.uniqid(),
        'external_id' => 'EXT-DEL-'.uniqid(),
        'name' => 'Walk-in',
        'checkout_link' => '',
        'subtotal' => 10000,
        'total' => 10000,
        'payment_method' => 'cash',
        'payment_status' => 'pending',
    ]);

    $item = TransactionItem::query()->create([
        'transaction_id' => $transaction->id,
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'quantity' => 1,
        'price' => 10000,
        'subtotal' => 10000,
        'note' => null,
    ]);

    $transaction->update(['payment_status' => 'paid']);

    return [$transaction->fresh(), $item->id, $ingredient->id];
}

test('delete paid transaction reverses inventory and removes all records', function () {
    $this->seed(RolePermissionSeeder::class);
    $user = makeDeleteActor(['transactions.details', 'transactions.void', 'transactions.void.approve']);

    [$transaction, $itemId, $ingredientId] = makeDeletePaidTransaction();
    $transactionId = (int) $transaction->id;

    expect($transaction->inventory_applied_at)->not->toBeNull();

    Livewire::actingAs($user)
        ->test(TransactionShowPage::class, ['transaction' => $transaction])
        ->set('correctionReason', 'Salah input')
        ->call('deleteTransaction')
        ->assertHasNoErrors()
        ->assertRedirect(route('transactions.index'));

    expect(Transaction::query()->whereKey($transactionId)->exists())->toBeFalse();
    expect(TransactionItem::query()->where('transaction_id', $transactionId)->exists())->toBeFalse();
    expect(TransactionItemAddon::query()->whereHas('transactionItem', fn ($q) => $q->where('transaction_id', $transactionId))->exists())->toBeFalse();
    expect(TransactionEvent::query()->where('transaction_id', $transactionId)->exists())->toBeFalse();

    $net = (float) InventoryMovement::query()
        ->where('reference_type', 'transactions')
        ->where('reference_id', $transactionId)
        ->where('ingredient_id', $ingredientId)
        ->sum('quantity');

    expect($net)->toEqual(0.0);
    expect(InventoryMovement::query()
        ->where('reference_type', 'transactions')
        ->where('reference_id', $transactionId)
        ->where('type', 'sale_reversal')
        ->exists())->toBeTrue();
});

test('delete pending transaction without inventory removes records cleanly', function () {
    $this->seed(RolePermissionSeeder::class);
    $user = makeDeleteActor(['transactions.details', 'transactions.void', 'transactions.void.approve']);

    $transaction = Transaction::query()->create([
        'code' => 'TRX-DEL-PEND',
        'external_id' => 'EXT-DEL-PEND',
        'name' => 'Walk-in',
        'checkout_link' => '',
        'subtotal' => 5000,
        'total' => 5000,
        'payment_method' => 'cash',
        'payment_status' => 'pending',
    ]);
    $transactionId = (int) $transaction->id;

    Livewire::actingAs($user)
        ->test(TransactionShowPage::class, ['transaction' => $transaction])
        ->set('correctionReason', 'Batal')
        ->call('deleteTransaction')
        ->assertHasNoErrors()
        ->assertRedirect(route('transactions.index'));

    expect(Transaction::query()->whereKey($transactionId)->exists())->toBeFalse();
    expect(InventoryMovement::query()
        ->where('reference_type', 'transactions')
        ->where('reference_id', $transactionId)
        ->exists())->toBeFalse();
});

test('delete rejects already voided transaction', function () {
    $this->seed(RolePermissionSeeder::class);
    $user = makeDeleteActor(['transactions.details', 'transactions.void', 'transactions.void.approve']);

    $transaction = Transaction::query()->create([
        'code' => 'TRX-DEL-VOID',
        'external_id' => 'EXT-DEL-VOID',
        'name' => 'Walk-in',
        'checkout_link' => '',
        'subtotal' => 5000,
        'total' => 5000,
        'payment_method' => 'cash',
        'payment_status' => 'voided',
    ]);

    Livewire::actingAs($user)
        ->test(TransactionShowPage::class, ['transaction' => $transaction])
        ->set('correctionReason', 'Coba hapus')
        ->call('deleteTransaction')
        ->assertHasErrors(['correctionReason']);

    expect(Transaction::query()->whereKey($transaction->id)->exists())->toBeTrue();
});

test('delete requires transactions.void permission', function () {
    $this->seed(RolePermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('cashier');

    $transaction = Transaction::query()->create([
        'code' => 'TRX-DEL-NOPERM',
        'external_id' => 'EXT-DEL-NOPERM',
        'name' => 'Walk-in',
        'checkout_link' => '',
        'subtotal' => 5000,
        'total' => 5000,
        'payment_method' => 'cash',
        'payment_status' => 'pending',
    ]);

    Livewire::actingAs($user)
        ->test(TransactionShowPage::class, ['transaction' => $transaction])
        ->set('correctionReason', 'Coba hapus')
        ->call('deleteTransaction')
        ->assertHasErrors(['correctionReason']);

    expect(Transaction::query()->whereKey($transaction->id)->exists())->toBeTrue();
});
