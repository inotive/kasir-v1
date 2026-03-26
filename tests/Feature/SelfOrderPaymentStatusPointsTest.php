<?php

use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows point discount info on self-order payment status page', function () {
    $trx = Transaction::query()->create([
        'code' => 'TRX-STATUS-POINT',
        'member_id' => null,
        'channel' => 'self_order',
        'name' => 'Member A',
        'phone' => '628111',
        'email' => 'member@example.com',
        'order_type' => 'take_away',
        'dining_table_id' => null,
        'subtotal' => 10000,
        'voucher_discount_amount' => 0,
        'manual_discount_amount' => 0,
        'discount_total_amount' => 1000,
        'points_redeemed' => 10,
        'point_discount_amount' => 1000,
        'tax_percentage' => 0,
        'tax_amount' => 0,
        'payment_fee_amount' => 0,
        'rounding_amount' => 0,
        'total' => 9000,
        'checkout_link' => '-',
        'payment_method' => 'cash',
        'payment_status' => 'pending',
        'order_status' => 'new',
        'external_id' => 'ext-TRX-STATUS-POINT',
        'is_midtrans_processed' => false,
    ]);

    $this
        ->get(route('self-order.payment.status', ['code' => $trx->code]))
        ->assertOk()
        ->assertSee('Diskon Poin')
        ->assertSee('10 poin')
        ->assertSee('-Rp 1.000');
});
