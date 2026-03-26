<?php

use App\Livewire\Transaction\TransactionShowPage;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\TransactionEvent;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

it('denies void when user lacks transactions.void permission', function () {
    $this->seed(RolePermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('cashier');

    $transaction = Transaction::query()->create([
        'code' => 'TRX-PEND-001',
        'external_id' => 'EXT-PEND-001',
        'name' => 'Walk-in',
        'checkout_link' => '',
        'subtotal' => 10000,
        'total' => 10000,
        'payment_method' => 'cash',
        'payment_status' => 'pending',
    ]);

    Livewire::actingAs($user)
        ->test(TransactionShowPage::class, ['transaction' => $transaction])
        ->set('correctionReason', 'Salah input')
        ->call('voidTransaction')
        ->assertHasErrors(['correctionReason']);
});

it('requires approver with permission when void needs approval', function () {
    $this->seed(RolePermissionSeeder::class);

    Setting::current()->update([
        'corrections_void_pending_requires_approval' => true,
    ]);

    $actorRole = Role::firstOrCreate(['name' => 'void_actor']);
    $actorRole->givePermissionTo(['pos.access', 'transactions.void', 'transactions.details']);

    $actor = User::factory()->create();
    $actor->assignRole('void_actor');

    $approverRole = Role::firstOrCreate(['name' => 'void_approver']);
    $approverRole->givePermissionTo(['transactions.void.approve']);

    $approver = User::factory()->create([
        'manager_pin' => '1234',
        'manager_pin_set_at' => now(),
        'is_active' => true,
    ]);
    $approver->assignRole('void_approver');

    $transaction = Transaction::query()->create([
        'code' => 'TRX-PEND-002',
        'external_id' => 'EXT-PEND-002',
        'name' => 'Walk-in',
        'checkout_link' => '',
        'subtotal' => 10000,
        'total' => 10000,
        'payment_method' => 'cash',
        'payment_status' => 'pending',
    ]);

    Livewire::actingAs($actor)
        ->test(TransactionShowPage::class, ['transaction' => $transaction])
        ->set('correctionReason', 'Salah transaksi')
        ->set('approverUserId', $approver->id)
        ->set('approverPin', '1234')
        ->call('voidTransaction')
        ->assertHasNoErrors();

    $transaction->refresh();
    expect((string) $transaction->payment_status)->toBe('voided');

    $event = TransactionEvent::query()
        ->where('transaction_id', $transaction->id)
        ->where('action', 'void')
        ->latest()
        ->first();

    expect($event)->not->toBeNull();
    expect((int) ($event->meta['approved_by_user_id'] ?? 0))->toBe((int) $approver->id);
});
