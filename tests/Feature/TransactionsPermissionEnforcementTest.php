<?php

use App\Livewire\Transaction\TransactionShowPage;
use App\Livewire\Transaction\TransactionsPage;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function makeTransactionForTests(array $overrides = []): Transaction
{
    return Transaction::query()->create(array_merge([
        'code' => 'TRX-TEST-'.Str::upper(Str::random(6)),
        'external_id' => (string) Str::uuid(),
        'name' => 'Walk-in',
        'checkout_link' => '',
        'subtotal' => 10000,
        'total' => 10000,
        'payment_method' => 'cash',
        'payment_status' => 'pending',
    ], $overrides));
}

test('transactions list hides PII when user lacks transactions.pii.view', function () {
    $this->seed(RolePermissionSeeder::class);

    $role = Role::firstOrCreate(['name' => 'transactions_viewer']);
    $role->syncPermissions(['dashboard.access', 'transactions.view']);

    $user = User::factory()->create();
    $user->assignRole('transactions_viewer');

    $trx = makeTransactionForTests([
        'code' => 'TRX-PII-001',
        'name' => 'Alice',
        'phone' => '081234567890',
        'email' => 'alice@example.com',
        'external_id' => 'EXT-PII-001',
    ]);

    Livewire::actingAs($user)
        ->test(TransactionsPage::class)
        ->set('search', $trx->code)
        ->assertSee('TRX-PII-001')
        ->assertDontSee('081234567890')
        ->set('search', '081234567890')
        ->assertDontSee('TRX-PII-001');
});

test('transactions list allows searching PII when user has transactions.pii.view', function () {
    $this->seed(RolePermissionSeeder::class);

    $role = Role::firstOrCreate(['name' => 'transactions_pii_viewer']);
    $role->syncPermissions(['dashboard.access', 'transactions.view', 'transactions.pii.view']);

    $user = User::factory()->create();
    $user->assignRole('transactions_pii_viewer');

    $trx = makeTransactionForTests([
        'code' => 'TRX-PII-002',
        'name' => 'Bob',
        'phone' => '089999000111',
        'email' => 'bob@example.com',
        'external_id' => 'EXT-PII-002',
    ]);

    Livewire::actingAs($user)
        ->test(TransactionsPage::class)
        ->set('search', '089999000111')
        ->assertSee('TRX-PII-002')
        ->assertSee('089999000111');
});

test('transaction show denies process inventory when user lacks inventory.manage', function () {
    $this->seed(RolePermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('cashier');

    $trx = makeTransactionForTests([
        'code' => 'TRX-INV-001',
    ]);

    Livewire::actingAs($user)
        ->test(TransactionShowPage::class, ['transaction' => $trx])
        ->call('processInventory')
        ->assertStatus(403);
});

test('transaction show denies opening void modal when user lacks transactions.void', function () {
    $this->seed(RolePermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('cashier');

    $trx = makeTransactionForTests([
        'code' => 'TRX-VOID-001',
    ]);

    Livewire::actingAs($user)
        ->test(TransactionShowPage::class, ['transaction' => $trx])
        ->call('openVoidModal')
        ->assertStatus(403);
});

test('midtrans unprocessed endpoint requires transactions.view', function () {
    $this->seed(RolePermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('waiter');

    $this->actingAs($user)
        ->get(route('midtrans.unprocessed'))
        ->assertForbidden();
});

test('self-order receipt requires token or matching session', function () {
    $trx = makeTransactionForTests([
        'code' => 'TRX-RECEIPT-001',
        'channel' => 'self_order',
        'self_order_token' => Str::random(40),
        'payment_status' => 'paid',
    ]);

    $this->get(route('self-order.payment.receipt', ['code' => $trx->code]))
        ->assertStatus(403);

    $this->get(route('self-order.payment.receipt', ['code' => $trx->code, 'token' => $trx->self_order_token]))
        ->assertOk();
});
