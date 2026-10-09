<?php

use App\Livewire\Transaction\TransactionShowPage;
use App\Models\Transaction;
use App\Models\TransactionEvent;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function makePaymentMethodActor(array $permissions): User
{
    $role = Role::firstOrCreate(['name' => 'pm_actor_'.uniqid()]);
    $role->givePermissionTo($permissions);

    $user = User::factory()->create();
    $user->assignRole($role);
    $user->manager_pin = '1234';
    $user->manager_pin_set_at = now();
    $user->save();

    return $user;
}

function makePaymentMethodTransaction(array $attributes = []): Transaction
{
    return Transaction::query()->create(array_merge([
        'code' => 'TRX-PM-'.uniqid(),
        'external_id' => 'EXT-PM-'.uniqid(),
        'name' => 'Walk-in',
        'checkout_link' => '',
        'subtotal' => 10000,
        'total' => 10000,
        'payment_method' => 'cash',
        'payment_status' => 'paid',
        'cash_received' => 50000,
        'cash_change' => 40000,
    ], $attributes));
}

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actor = makePaymentMethodActor(['transactions.details', 'transactions.refund', 'transactions.refund.approve']);
});

test('cash to qris clears cash received and change and logs event', function () {
    $transaction = makePaymentMethodTransaction();

    Livewire::actingAs($this->actor)
        ->test(TransactionShowPage::class, ['transaction' => $transaction])
        ->assertSee('Ubah Metode Bayar')
        ->call('openPaymentMethodModal')
        ->set('newPaymentMethod', 'qris')
        ->assertSee('sudah dikembalikan ke pelanggan')
        ->set('correctionReason', 'Pelanggan ganti bayar QRIS')
        ->set('approverPin', '1234')
        ->call('changePaymentMethod')
        ->assertHasNoErrors();

    $transaction->refresh();
    expect($transaction->payment_method)->toBe('qris');
    expect($transaction->cash_received)->toBeNull();
    expect($transaction->cash_change)->toBeNull();
    expect((int) $transaction->total)->toBe(10000);

    $event = TransactionEvent::query()->where('transaction_id', $transaction->id)->where('action', 'payment_method_change')->first();
    expect($event)->not->toBeNull();
    expect($event->meta['previous_payment_method'])->toBe('cash');
    expect($event->meta['new_payment_method'])->toBe('qris');
    expect((int) $event->meta['previous_cash_change'])->toBe(40000);
    expect((int) $event->meta['approved_by_user_id'])->toBe((int) $this->actor->id);
});

test('qris to cash requires enough cash and computes change', function () {
    $transaction = makePaymentMethodTransaction(['payment_method' => 'qris', 'cash_received' => null, 'cash_change' => null]);

    $component = Livewire::actingAs($this->actor)
        ->test(TransactionShowPage::class, ['transaction' => $transaction])
        ->set('newPaymentMethod', 'cash')
        ->set('newCashReceived', '5.000')
        ->set('correctionReason', 'Salah pilih metode')
        ->set('approverPin', '1234')
        ->call('changePaymentMethod')
        ->assertHasErrors(['newCashReceived']);

    expect($transaction->fresh()->payment_method)->toBe('qris');

    $component
        ->set('newCashReceived', '20.000')
        ->call('changePaymentMethod')
        ->assertHasNoErrors();

    $transaction->refresh();
    expect($transaction->payment_method)->toBe('cash');
    expect((int) $transaction->cash_received)->toBe(20000);
    expect((int) $transaction->cash_change)->toBe(10000);
});

test('change requires approver PIN', function () {
    $transaction = makePaymentMethodTransaction();

    Livewire::actingAs($this->actor)
        ->test(TransactionShowPage::class, ['transaction' => $transaction])
        ->set('newPaymentMethod', 'transfer_bank')
        ->set('correctionReason', 'Tanpa PIN')
        ->call('changePaymentMethod')
        ->assertHasErrors(['approverPin']);

    expect($transaction->fresh()->payment_method)->toBe('cash');
});

test('change is rejected for gateway, refunded, and pending transactions', function (array $attributes) {
    $transaction = makePaymentMethodTransaction($attributes);
    $previousMethod = (string) $transaction->payment_method;

    Livewire::actingAs($this->actor)
        ->test(TransactionShowPage::class, ['transaction' => $transaction])
        ->set('newPaymentMethod', $previousMethod === 'transfer_bank' ? 'qris' : 'transfer_bank')
        ->set('correctionReason', 'Coba ubah')
        ->set('approverPin', '1234')
        ->call('changePaymentMethod')
        ->assertHasErrors(['newPaymentMethod']);

    expect($transaction->fresh()->payment_method)->toBe($previousMethod);
})->with([
    'midtrans' => [['payment_method' => 'qris_midtrans', 'cash_received' => null, 'cash_change' => null]],
    'with gateway fee' => [['payment_method' => 'qris', 'payment_fee_amount' => 70, 'cash_received' => null, 'cash_change' => null]],
    'partial refund' => [['payment_status' => 'partial_refund', 'refunded_amount' => 2000]],
    'pending' => [['payment_status' => 'pending']],
]);

test('change requires transactions.refund permission', function () {
    $user = makePaymentMethodActor(['transactions.details']);
    $transaction = makePaymentMethodTransaction();

    Livewire::actingAs($user)
        ->test(TransactionShowPage::class, ['transaction' => $transaction])
        ->set('newPaymentMethod', 'qris')
        ->set('correctionReason', 'Coba ubah')
        ->set('approverPin', '1234')
        ->call('changePaymentMethod')
        ->assertHasErrors(['newPaymentMethod']);

    expect($transaction->fresh()->payment_method)->toBe('cash');
});
