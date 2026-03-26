<?php

use App\Livewire\Pos\PosPage;
use App\Livewire\SelfOrder\Pages\StartPage;
use App\Livewire\SelfOrder\Pages\VerifyEmailPage;
use App\Mail\MemberVerificationMail;
use App\Models\Category;
use App\Models\DiningTable;
use App\Models\Member;
use App\Models\MemberRegion;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('redirects to verify email page after member registers from self-order start', function () {
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

    session([
        'dining_table_id' => $table->id,
        'self_order_token' => Str::random(40),
    ]);

    $component = Livewire::test(StartPage::class)
        ->set('register_name', 'Member A')
        ->set('register_email', 'member-a@example.com')
        ->set('register_phone', '081234567890')
        ->set('register_province', (string) $region->province)
        ->set('register_regency', (string) $region->regency)
        ->set('register_district', (string) $region->district)
        ->call('registerMember');

    expect(Member::query()->count())->toBe(1);
    $member = Member::query()->first();
    $component->assertRedirect(route('self-order.member.verify-email'));
    expect((string) session('pending_member_email'))->toBe('member-a@example.com');
    expect((int) session('pending_member_id'))->toBe($member->id);

    Mail::assertQueued(MemberVerificationMail::class);
});

it('rate limits resend verification on verify email page', function () {
    Mail::fake();

    $member = Member::query()->create([
        'name' => 'Member A',
        'email' => 'member-a@example.com',
        'phone' => '6281234567890',
        'points' => 0,
        'member_region_id' => null,
        'verification_token' => Str::random(40),
        'email_verified_at' => null,
    ]);

    session([
        'pending_member_id' => $member->id,
        'pending_member_email' => $member->email,
        'dining_table_id' => null,
        'self_order_token' => Str::random(40),
    ]);

    Livewire::test(VerifyEmailPage::class)
        ->call('resend')
        ->assertSet('notice', 'Email verifikasi telah dikirim ulang. Silakan cek inbox/spam.')
        ->call('resend')
        ->assertSet('notice', 'Terlalu sering kirim ulang. Coba lagi dalam beberapa saat.');

    Mail::assertQueued(MemberVerificationMail::class);
});

it('prevents editing customer and member data on POS for self-order pending transaction', function () {
    $this->seed(RolePermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('cashier');

    $member = Member::query()->create([
        'name' => 'Member POS',
        'email' => 'pos@example.com',
        'phone' => '628111',
        'points' => 0,
        'email_verified_at' => now(),
        'verification_token' => null,
    ]);

    $table = DiningTable::query()->create([
        'table_number' => 'A1',
        'image' => null,
        'qr_value' => 'TBL-A1',
    ]);

    $category = Category::query()->create(['name' => 'Food']);
    $product = Product::query()->create([
        'name' => 'Nasi',
        'description' => 'Test',
        'image' => 'test.png',
        'is_available' => true,
        'is_promo' => false,
        'is_favorite' => false,
        'category_id' => $category->id,
        'printer_source_id' => null,
    ]);
    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'name' => 'Normal',
        'price' => 10000,
        'price_afterdiscount' => null,
        'percent' => null,
        'hpp' => 0,
    ]);

    $trx = Transaction::query()->create([
        'channel' => 'self_order',
        'checkout_link' => '-',
        'payment_method' => 'cash',
        'payment_status' => 'pending',
        'member_id' => $member->id,
        'name' => 'Member POS',
        'phone' => '628111',
        'email' => 'pos@example.com',
        'order_type' => 'dine_in',
        'subtotal' => 10000,
        'discount_total_amount' => 0,
        'tax_percentage' => 0,
        'tax_amount' => 0,
        'rounding_amount' => 0,
        'total' => 10000,
        'dining_table_id' => $table->id,
        'external_id' => (string) Str::uuid(),
        'code' => 'TRX-TEST',
        'self_order_token' => Str::random(40),
    ]);

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
        ->call('loadPending', $trx->id)
        ->assertSet('cartLocked', true)
        ->assertSet('memberId', $member->id)
        ->assertSet('customerName', 'Member POS')
        ->assertSet('customerPhone', '628111')
        ->set('memberId', null)
        ->set('customerName', 'Hacker')
        ->set('customerPhone', '000')
        ->assertSet('memberId', $member->id)
        ->assertSet('customerName', 'Member POS')
        ->assertSet('customerPhone', '628111');
});
