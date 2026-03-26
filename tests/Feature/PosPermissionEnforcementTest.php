<?php

use App\Livewire\Pos\PosPage;
use App\Models\Category;
use App\Models\Member;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function createPendingPosTransaction(): Transaction
{
    return Transaction::query()->create([
        'code' => Transaction::generateUniqueCode(),
        'member_id' => null,
        'channel' => 'pos',
        'name' => 'Guest',
        'phone' => null,
        'email' => null,
        'order_type' => 'take_away',
        'dining_table_id' => null,
        'subtotal' => 10000,
        'tax_percentage' => 0,
        'tax_amount' => 0,
        'rounding_amount' => 0,
        'cash_received' => null,
        'cash_change' => null,
        'total' => 10000,
        'checkout_link' => '',
        'payment_method' => 'cash',
        'payment_status' => 'pending',
        'external_id' => (string) Str::uuid(),
    ]);
}

test('pos: user tanpa transactions.details tidak bisa load pending', function () {
    $this->seed(RolePermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('waiter');

    $trx = createPendingPosTransaction();

    Livewire::actingAs($user)
        ->test(PosPage::class)
        ->call('loadPending', $trx->id)
        ->assertStatus(403);
});

test('pos: cashier tidak bisa menghapus pending (butuh transactions.void)', function () {
    $this->seed(RolePermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('cashier');

    $trx = createPendingPosTransaction();

    Livewire::actingAs($user)
        ->test(PosPage::class)
        ->call('deletePending', $trx->id)
        ->assertStatus(403);
});

test('pos: manager bisa menghapus pending pos dan items', function () {
    $this->seed(RolePermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('manager');

    $category = Category::query()->create(['name' => 'Food']);
    $product = Product::query()->create([
        'name' => 'Test Product',
        'description' => '-',
        'image' => '-',
        'is_available' => true,
        'category_id' => $category->id,
        'printer_source_id' => null,
    ]);
    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'name' => 'Standard',
        'price' => 10000,
        'hpp' => 0,
    ]);

    $trx = createPendingPosTransaction();

    TransactionItem::query()->create([
        'transaction_id' => $trx->id,
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'quantity' => 1,
        'price' => 10000,
        'subtotal' => 10000,
        'note' => null,
    ]);

    Livewire::actingAs($user)
        ->test(PosPage::class)
        ->call('deletePending', $trx->id);

    expect(Transaction::query()->whereKey($trx->id)->exists())->toBeFalse();
    expect(TransactionItem::query()->where('transaction_id', $trx->id)->exists())->toBeFalse();
});

test('pos: user tanpa members.view tidak bisa memilih member', function () {
    $this->seed(RolePermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('waiter');

    $member = Member::factory()->create();

    Livewire::actingAs($user)
        ->test(PosPage::class)
        ->set('memberId', $member->id)
        ->assertSet('memberId', null);
});
