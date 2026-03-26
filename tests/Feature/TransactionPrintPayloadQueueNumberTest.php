<?php

use App\Models\Transaction;
use App\Models\User;
use App\Services\Printing\PosPrintPayloadService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('print payload includes daily queue number for transaction date and ignores voided transactions', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $day = now()->startOfDay();
    $t1 = $day->copy()->addHours(9);
    $tVoid = $day->copy()->addHours(9)->addMinutes(30);
    $t2 = $day->copy()->addHours(10);

    $trx1 = null;
    $trx2 = null;

    Transaction::unguarded(function () use ($t1, $tVoid, $t2, &$trx1, &$trx2) {
        $trx1 = Transaction::query()->create([
            'code' => 'TRX-Q-001',
            'external_id' => 'EXT-Q-001',
            'channel' => 'pos',
            'name' => 'Customer',
            'phone' => null,
            'email' => null,
            'order_type' => 'take_away',
            'dining_table_id' => null,
            'subtotal' => 0,
            'tax_percentage' => null,
            'tax_amount' => 0,
            'rounding_amount' => 0,
            'cash_received' => null,
            'cash_change' => null,
            'refunded_amount' => 0,
            'total' => 0,
            'checkout_link' => '',
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'order_status' => 'new',
            'paid_at' => $t1,
            'is_midtrans_processed' => false,
            'voided_at' => null,
            'created_at' => $t1,
            'updated_at' => $t1,
        ]);

        Transaction::query()->create([
            'code' => 'TRX-Q-VOID',
            'external_id' => 'EXT-Q-VOID',
            'channel' => 'pos',
            'name' => 'Customer',
            'phone' => null,
            'email' => null,
            'order_type' => 'take_away',
            'dining_table_id' => null,
            'subtotal' => 0,
            'tax_percentage' => null,
            'tax_amount' => 0,
            'rounding_amount' => 0,
            'cash_received' => null,
            'cash_change' => null,
            'refunded_amount' => 0,
            'total' => 0,
            'checkout_link' => '',
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'order_status' => 'new',
            'paid_at' => $tVoid,
            'is_midtrans_processed' => false,
            'voided_at' => $tVoid,
            'created_at' => $tVoid,
            'updated_at' => $tVoid,
        ]);

        $trx2 = Transaction::query()->create([
            'code' => 'TRX-Q-002',
            'external_id' => 'EXT-Q-002',
            'channel' => 'pos',
            'name' => 'Customer',
            'phone' => null,
            'email' => null,
            'order_type' => 'take_away',
            'dining_table_id' => null,
            'subtotal' => 0,
            'tax_percentage' => null,
            'tax_amount' => 0,
            'rounding_amount' => 0,
            'cash_received' => null,
            'cash_change' => null,
            'refunded_amount' => 0,
            'total' => 0,
            'checkout_link' => '',
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'order_status' => 'new',
            'paid_at' => $t2,
            'is_midtrans_processed' => false,
            'voided_at' => null,
            'created_at' => $t2,
            'updated_at' => $t2,
        ]);
    });

    $payload1 = app(PosPrintPayloadService::class)->build($trx1->id);
    expect($payload1)->not->toBeNull();
    expect((int) ($payload1['order']['queue_number'] ?? 0))->toBe(1);
    expect((string) ($payload1['order']['queue_date'] ?? ''))->toBe($day->toDateString());

    $payload2 = app(PosPrintPayloadService::class)->build($trx2->id);
    expect($payload2)->not->toBeNull();
    expect((int) ($payload2['order']['queue_number'] ?? 0))->toBe(2);
    expect((string) ($payload2['order']['queue_date'] ?? ''))->toBe($day->toDateString());
});

test('queue number resets on different transaction date', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $yesterday = now()->subDay()->startOfDay()->addHours(8);

    $trx = null;
    Transaction::unguarded(function () use ($yesterday, &$trx) {
        $trx = Transaction::query()->create([
            'code' => 'TRX-Q-Y-001',
            'external_id' => 'EXT-Q-Y-001',
            'channel' => 'pos',
            'name' => 'Customer',
            'phone' => null,
            'email' => null,
            'order_type' => 'take_away',
            'dining_table_id' => null,
            'subtotal' => 0,
            'tax_percentage' => null,
            'tax_amount' => 0,
            'rounding_amount' => 0,
            'cash_received' => null,
            'cash_change' => null,
            'refunded_amount' => 0,
            'total' => 0,
            'checkout_link' => '',
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'order_status' => 'new',
            'paid_at' => $yesterday,
            'is_midtrans_processed' => false,
            'voided_at' => null,
            'created_at' => $yesterday,
            'updated_at' => $yesterday,
        ]);
    });

    $payload = app(PosPrintPayloadService::class)->build($trx->id);
    expect($payload)->not->toBeNull();
    expect((int) ($payload['order']['queue_number'] ?? 0))->toBe(1);
    expect((string) ($payload['order']['queue_date'] ?? ''))->toBe($yesterday->toDateString());
});
