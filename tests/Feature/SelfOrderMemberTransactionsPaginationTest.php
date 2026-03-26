<?php

use App\Models\DiningTable;
use App\Models\Member;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('renders custom pagination on self-order member transactions page', function () {
    $table = DiningTable::query()->create([
        'table_number' => 'A1',
        'image' => null,
        'qr_value' => 'TBL-A1',
    ]);

    $member = Member::query()->create([
        'name' => 'Member A',
        'email' => 'member@example.com',
        'phone' => '628111',
        'points' => 0,
        'email_verified_at' => now(),
        'verification_token' => null,
    ]);

    foreach (range(1, 11) as $i) {
        Transaction::query()->create([
            'code' => 'TRX-PAG-'.$i,
            'member_id' => $member->id,
            'channel' => 'self_order',
            'name' => $member->name,
            'phone' => $member->phone,
            'email' => $member->email,
            'order_type' => 'take_away',
            'dining_table_id' => $table->id,
            'subtotal' => 10000,
            'voucher_discount_amount' => 0,
            'manual_discount_amount' => 0,
            'discount_total_amount' => 0,
            'points_redeemed' => 0,
            'point_discount_amount' => 0,
            'tax_percentage' => 0,
            'tax_amount' => 0,
            'payment_fee_amount' => 0,
            'rounding_amount' => 0,
            'total' => 10000,
            'checkout_link' => '-',
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'order_status' => 'new',
            'external_id' => 'ext-TRX-PAG-'.$i,
            'is_midtrans_processed' => false,
        ]);
    }

    $this
        ->withSession([
            'dining_table_id' => $table->id,
            'self_order_token' => Str::random(40),
            'customer_ready' => true,
            'customer_type' => 'member',
            'member_id' => $member->id,
            'name' => $member->name,
            'phone' => $member->phone,
            'email' => $member->email,
            'cart_items' => [],
        ])
        ->get(route('self-order.member.transactions'))
        ->assertOk()
        ->assertSee('SELF ORDER')
        ->assertSee('Sudah Bayar')
        ->assertSee('Sebelumnya')
        ->assertSee('Berikutnya')
        ->assertSee('Menampilkan');
});
