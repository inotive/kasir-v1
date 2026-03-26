<?php

use App\Livewire\SelfOrder\Pages\MemberProfileEditPage;
use App\Mail\MemberVerificationMail;
use App\Models\DiningTable;
use App\Models\Member;
use App\Models\MemberRegion;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('redirects guest away from self-order member account pages', function () {
    $table = DiningTable::query()->create([
        'table_number' => 'A1',
        'image' => null,
        'qr_value' => 'TBL-A1',
    ]);

    $this
        ->withSession([
            'dining_table_id' => $table->id,
            'self_order_token' => Str::random(40),
            'customer_ready' => true,
            'customer_type' => 'guest',
            'member_id' => null,
            'name' => 'Guest',
            'phone' => '628123',
            'cart_items' => [],
        ])
        ->get(route('self-order.member.account'))
        ->assertRedirect(route('self-order.start'));
});

it('allows member to open self-order account page', function () {
    $table = DiningTable::query()->create([
        'table_number' => 'A1',
        'image' => null,
        'qr_value' => 'TBL-A1',
    ]);

    $member = Member::query()->create([
        'name' => 'Member A',
        'email' => 'member@example.com',
        'phone' => '628111',
        'points' => 12345,
        'email_verified_at' => now(),
        'verification_token' => null,
    ]);

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
        ->get(route('self-order.member.account'))
        ->assertOk()
        ->assertSee('Edit Profil')
        ->assertSee('Riwayat Transaksi')
        ->assertSee('Poin')
        ->assertSee('12.345');
});

it('auto-logins and redirects to self-order when verifying email with existing self-order session', function () {
    $table = DiningTable::query()->create([
        'table_number' => 'A1',
        'image' => null,
        'qr_value' => 'TBL-A1',
    ]);

    $token = Str::random(40);
    $member = Member::query()->create([
        'name' => 'Member A',
        'email' => 'member@example.com',
        'phone' => '628111',
        'points' => 0,
        'email_verified_at' => null,
        'verification_token' => $token,
    ]);

    $this
        ->withSession([
            'dining_table_id' => $table->id,
            'self_order_token' => Str::random(40),
            'cart_items' => [],
        ])
        ->get(route('members.verify', ['token' => $token]))
        ->assertRedirect(route('self-order.home'))
        ->assertSessionHas('customer_ready', true)
        ->assertSessionHas('customer_type', 'member')
        ->assertSessionHas('member_id', $member->id)
        ->assertSessionHas('email', $member->email);
});

it('auto-logins and redirects to self-order when verifying email with table context param', function () {
    $table = DiningTable::query()->create([
        'table_number' => 'A1',
        'image' => null,
        'qr_value' => 'TBL-A1',
    ]);

    $token = Str::random(40);
    $member = Member::query()->create([
        'name' => 'Member A',
        'email' => 'member@example.com',
        'phone' => '628111',
        'points' => 0,
        'email_verified_at' => null,
        'verification_token' => $token,
    ]);

    $this
        ->get(route('members.verify', ['token' => $token]).'?t='.$table->qr_value)
        ->assertRedirect(route('self-order.home'))
        ->assertSessionHas('dining_table_id', $table->id)
        ->assertSessionHas('customer_ready', true)
        ->assertSessionHas('member_id', $member->id);
});

it('updates member profile and syncs self-order session', function () {
    $table = DiningTable::query()->create([
        'table_number' => 'A1',
        'image' => null,
        'qr_value' => 'TBL-A1',
    ]);

    $oldRegion = MemberRegion::query()->create([
        'province' => 'Jawa Barat',
        'regency' => 'Bandung',
        'district' => 'Coblong',
        'geojson' => null,
    ]);

    $newRegion = MemberRegion::query()->create([
        'province' => 'Jawa Tengah',
        'regency' => 'Semarang',
        'district' => 'Tembalang',
        'geojson' => null,
    ]);

    $member = Member::query()->create([
        'name' => 'Member A',
        'email' => 'member@example.com',
        'phone' => '628111',
        'member_region_id' => $oldRegion->id,
        'points' => 0,
        'email_verified_at' => now(),
        'verification_token' => null,
    ]);

    $this->withSession([
        'dining_table_id' => $table->id,
        'self_order_token' => Str::random(40),
        'customer_ready' => true,
        'customer_type' => 'member',
        'member_id' => $member->id,
        'name' => $member->name,
        'phone' => $member->phone,
        'email' => $member->email,
        'cart_items' => [],
    ]);
    session([
        'dining_table_id' => $table->id,
        'self_order_token' => session('self_order_token'),
        'customer_ready' => true,
        'customer_type' => 'member',
        'member_id' => $member->id,
        'name' => $member->name,
        'phone' => $member->phone,
        'email' => $member->email,
        'cart_items' => [],
    ]);

    Livewire::test(MemberProfileEditPage::class)
        ->set('name', 'Member Baru')
        ->set('email', 'member@example.com')
        ->set('phone', '081234567890')
        ->set('province', $newRegion->province)
        ->set('regency', $newRegion->regency)
        ->set('district', $newRegion->district)
        ->call('save')
        ->assertSet('notice', 'Profil berhasil diperbarui.');

    $member->refresh();
    expect($member->name)->toBe('Member Baru');
    expect($member->phone)->toBe('6281234567890');
    expect((int) $member->member_region_id)->toBe((int) $newRegion->id);
    expect((string) session('name'))->toBe('Member Baru');
    expect((string) session('phone'))->toBe('6281234567890');
});

it('requires re-verification when member changes email', function () {
    Mail::fake();

    $table = DiningTable::query()->create([
        'table_number' => 'A1',
        'image' => null,
        'qr_value' => 'TBL-A1',
    ]);

    $region = MemberRegion::query()->create([
        'province' => 'Jawa Barat',
        'regency' => 'Bandung',
        'district' => 'Coblong',
        'geojson' => null,
    ]);

    $member = Member::query()->create([
        'name' => 'Member A',
        'email' => 'member@example.com',
        'phone' => '628111',
        'member_region_id' => $region->id,
        'points' => 0,
        'email_verified_at' => now(),
        'verification_token' => null,
    ]);

    $this->withSession([
        'dining_table_id' => $table->id,
        'self_order_token' => Str::random(40),
        'customer_ready' => true,
        'customer_type' => 'member',
        'member_id' => $member->id,
        'name' => $member->name,
        'phone' => $member->phone,
        'email' => $member->email,
        'cart_items' => [],
    ]);
    session([
        'dining_table_id' => $table->id,
        'self_order_token' => session('self_order_token'),
        'customer_ready' => true,
        'customer_type' => 'member',
        'member_id' => $member->id,
        'name' => $member->name,
        'phone' => $member->phone,
        'email' => $member->email,
        'cart_items' => [],
    ]);

    Livewire::test(MemberProfileEditPage::class)
        ->set('name', 'Member A')
        ->set('email', 'newmember@example.com')
        ->set('phone', '628111')
        ->set('province', $region->province)
        ->set('regency', $region->regency)
        ->set('district', $region->district)
        ->call('save')
        ->assertSet('notice', 'Profil berhasil diperbarui. Silakan cek email untuk verifikasi email baru.');

    $member->refresh();
    expect($member->email)->toBe('newmember@example.com');
    expect($member->email_verified_at)->toBeNull();
    expect($member->verification_token)->not()->toBeNull();

    Mail::assertQueued(MemberVerificationMail::class);
});

it('prevents member from viewing other member transactions', function () {
    $table = DiningTable::query()->create([
        'table_number' => 'A1',
        'image' => null,
        'qr_value' => 'TBL-A1',
    ]);

    $memberA = Member::query()->create([
        'name' => 'Member A',
        'email' => 'a@example.com',
        'phone' => '628111',
        'points' => 0,
        'email_verified_at' => now(),
        'verification_token' => null,
    ]);

    $memberB = Member::query()->create([
        'name' => 'Member B',
        'email' => 'b@example.com',
        'phone' => '628222',
        'points' => 0,
        'email_verified_at' => now(),
        'verification_token' => null,
    ]);

    $trx = Transaction::query()->create([
        'code' => 'TRX001',
        'member_id' => $memberB->id,
        'channel' => 'self_order',
        'name' => 'Member B',
        'phone' => '628222',
        'email' => 'b@example.com',
        'order_type' => 'dine_in',
        'dining_table_id' => $table->id,
        'subtotal' => 10000,
        'tax_percentage' => 0,
        'tax_amount' => 0,
        'rounding_amount' => 0,
        'refunded_amount' => 0,
        'total' => 10000,
        'checkout_link' => 'http://example.com/checkout/TRX001',
        'payment_method' => 'cashier',
        'payment_status' => 'paid',
        'external_id' => 'ext-TRX001',
        'is_midtrans_processed' => false,
    ]);

    $this
        ->withSession([
            'dining_table_id' => $table->id,
            'self_order_token' => Str::random(40),
            'customer_ready' => true,
            'customer_type' => 'member',
            'member_id' => $memberA->id,
            'name' => $memberA->name,
            'phone' => $memberA->phone,
            'email' => $memberA->email,
            'cart_items' => [],
        ])
        ->get(route('self-order.member.transactions.show', ['transaction' => $trx->id]))
        ->assertRedirect(route('self-order.member.transactions'));
});

it('shows point info on self-order member transaction history', function () {
    $table = DiningTable::query()->create([
        'table_number' => 'A1',
        'image' => null,
        'qr_value' => 'TBL-A1',
    ]);

    $member = Member::query()->create([
        'name' => 'Member A',
        'email' => 'a@example.com',
        'phone' => '628111',
        'points' => 0,
        'email_verified_at' => now(),
        'verification_token' => null,
    ]);

    Transaction::query()->create([
        'code' => 'TRX002',
        'member_id' => $member->id,
        'channel' => 'self_order',
        'name' => 'Member A',
        'phone' => '628111',
        'email' => 'a@example.com',
        'order_type' => 'dine_in',
        'dining_table_id' => $table->id,
        'subtotal' => 10000,
        'tax_percentage' => 0,
        'tax_amount' => 0,
        'rounding_amount' => 0,
        'refunded_amount' => 0,
        'total' => 9000,
        'checkout_link' => 'http://example.com/checkout/TRX002',
        'payment_method' => 'cashier',
        'payment_status' => 'paid',
        'external_id' => 'ext-TRX002',
        'is_midtrans_processed' => false,
        'points_earned' => 5,
        'points_redeemed' => 10,
        'point_discount_amount' => 1000,
    ]);

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
        ->assertSee('Poin +5')
        ->assertSee('Poin -10');
});

it('shows point details on self-order member transaction show', function () {
    $table = DiningTable::query()->create([
        'table_number' => 'A1',
        'image' => null,
        'qr_value' => 'TBL-A1',
    ]);

    $member = Member::query()->create([
        'name' => 'Member A',
        'email' => 'a@example.com',
        'phone' => '628111',
        'points' => 0,
        'email_verified_at' => now(),
        'verification_token' => null,
    ]);

    $trx = Transaction::query()->create([
        'code' => 'TRX003',
        'member_id' => $member->id,
        'channel' => 'self_order',
        'name' => 'Member A',
        'phone' => '628111',
        'email' => 'a@example.com',
        'order_type' => 'dine_in',
        'dining_table_id' => $table->id,
        'subtotal' => 10000,
        'tax_percentage' => 0,
        'tax_amount' => 0,
        'rounding_amount' => 0,
        'refunded_amount' => 0,
        'total' => 9000,
        'checkout_link' => 'http://example.com/checkout/TRX003',
        'payment_method' => 'cashier',
        'payment_status' => 'paid',
        'external_id' => 'ext-TRX003',
        'is_midtrans_processed' => false,
        'points_earned' => 5,
        'points_redeemed' => 10,
        'point_discount_amount' => 1000,
    ]);

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
        ->get(route('self-order.member.transactions.show', ['transaction' => $trx->id]))
        ->assertOk()
        ->assertSee('Diskon Poin')
        ->assertSee('1.000')
        ->assertSee('Poin Dipakai')
        ->assertSee('10')
        ->assertSee('Poin Didapat')
        ->assertSee('5');
});
