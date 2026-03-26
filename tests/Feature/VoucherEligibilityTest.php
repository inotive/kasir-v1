<?php

use App\Models\Category;
use App\Models\Member;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Transaction;
use App\Models\VoucherCampaign;
use App\Models\VoucherCode;
use App\Models\VoucherRedemption;
use App\Services\Vouchers\VoucherEligibilityService;
use Illuminate\Support\Carbon;

function seedVoucherCart(): array
{
    $eligibleCategory = Category::query()->create(['name' => 'Eligible']);
    $otherCategory = Category::query()->create(['name' => 'Other']);

    $p1 = Product::query()->create([
        'name' => 'Item 1',
        'description' => 'Test',
        'image' => 'test.png',
        'is_available' => true,
        'is_promo' => false,
        'is_favorite' => false,
        'category_id' => $eligibleCategory->id,
        'printer_source_id' => null,
    ]);
    $v1 = ProductVariant::query()->create([
        'product_id' => $p1->id,
        'name' => 'Normal',
        'price' => 20000,
        'price_afterdiscount' => null,
        'percent' => null,
        'hpp' => 0,
    ]);

    $p2 = Product::query()->create([
        'name' => 'Item 2',
        'description' => 'Test',
        'image' => 'test.png',
        'is_available' => true,
        'is_promo' => false,
        'is_favorite' => false,
        'category_id' => $otherCategory->id,
        'printer_source_id' => null,
    ]);
    $v2 = ProductVariant::query()->create([
        'product_id' => $p2->id,
        'name' => 'Normal',
        'price' => 10000,
        'price_afterdiscount' => null,
        'percent' => null,
        'hpp' => 0,
    ]);

    $cart = [
        [
            'id' => (int) $p1->id,
            'variant_id' => (int) $v1->id,
            'quantity' => 2,
            'price' => 20000,
        ],
        [
            'id' => (int) $p2->id,
            'variant_id' => (int) $v2->id,
            'quantity' => 1,
            'price' => 10000,
        ],
    ];

    return [
        'eligibleCategory' => $eligibleCategory,
        'otherCategory' => $otherCategory,
        'product1' => $p1,
        'product2' => $p2,
        'cart' => $cart,
    ];
}

it('rejects expired vouchers', function () {
    $seed = seedVoucherCart();

    $campaign = VoucherCampaign::query()->create([
        'name' => 'Expired',
        'description' => null,
        'discount_type' => 'percent',
        'discount_value' => 10,
        'max_discount_amount' => null,
        'min_eligible_subtotal' => null,
        'is_active' => true,
        'starts_at' => now()->subDays(10),
        'ends_at' => now()->subDay(),
        'usage_limit_total' => null,
        'usage_limit_per_user' => null,
        'eligible_member_type' => null,
        'meta' => null,
        'terms' => null,
        'created_by_user_id' => null,
    ]);

    VoucherCode::query()->create([
        'voucher_campaign_id' => $campaign->id,
        'code' => 'EXPIRED10',
        'is_active' => true,
        'usage_limit_total' => null,
        'usage_limit_per_user' => null,
        'times_redeemed' => 0,
    ]);

    $service = app(VoucherEligibilityService::class);
    $res = $service->validate('EXPIRED10', null, $seed['cart'], '628123', Carbon::now());

    expect((bool) $res['ok'])->toBeFalse();
    expect((string) $res['message'])->toContain('kedaluwarsa');
});

it('enforces eligible category and minimum subtotal', function () {
    $seed = seedVoucherCart();

    $campaign = VoucherCampaign::query()->create([
        'name' => 'Category only',
        'description' => null,
        'discount_type' => 'percent',
        'discount_value' => 10,
        'max_discount_amount' => null,
        'min_eligible_subtotal' => 50000,
        'is_active' => true,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addDay(),
        'usage_limit_total' => null,
        'usage_limit_per_user' => null,
        'eligible_member_type' => null,
        'meta' => null,
        'terms' => null,
        'created_by_user_id' => null,
    ]);
    $campaign->eligibleCategories()->sync([$seed['eligibleCategory']->id]);

    VoucherCode::query()->create([
        'voucher_campaign_id' => $campaign->id,
        'code' => 'CATMIN',
        'is_active' => true,
        'usage_limit_total' => null,
        'usage_limit_per_user' => null,
        'times_redeemed' => 0,
    ]);

    $service = app(VoucherEligibilityService::class);
    $res = $service->validate('CATMIN', null, $seed['cart'], '628123');

    expect((bool) $res['ok'])->toBeFalse();
    expect((string) $res['message'])->toContain('Minimal belanja');
});

it('calculates discount with cap and allocates per eligible line', function () {
    $seed = seedVoucherCart();

    $campaign = VoucherCampaign::query()->create([
        'name' => 'Cap',
        'description' => null,
        'discount_type' => 'percent',
        'discount_value' => 50,
        'max_discount_amount' => 15000,
        'min_eligible_subtotal' => null,
        'is_active' => true,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addDay(),
        'usage_limit_total' => null,
        'usage_limit_per_user' => null,
        'eligible_member_type' => null,
        'meta' => null,
        'terms' => null,
        'created_by_user_id' => null,
    ]);
    $campaign->eligibleCategories()->sync([$seed['eligibleCategory']->id, $seed['otherCategory']->id]);

    VoucherCode::query()->create([
        'voucher_campaign_id' => $campaign->id,
        'code' => 'CAP50',
        'is_active' => true,
        'usage_limit_total' => null,
        'usage_limit_per_user' => null,
        'times_redeemed' => 0,
    ]);

    $service = app(VoucherEligibilityService::class);
    $res = $service->validate('CAP50', null, $seed['cart'], '628123');

    expect((bool) $res['ok'])->toBeTrue();
    expect((int) $res['eligible_subtotal'])->toBe(50000);
    expect((int) $res['discount_amount'])->toBe(15000);
    expect(array_sum((array) $res['allocations']))->toBe(15000);
});

it('enforces per-user quota for guest', function () {
    $seed = seedVoucherCart();

    $campaign = VoucherCampaign::query()->create([
        'name' => 'Per user',
        'description' => null,
        'discount_type' => 'fixed_amount',
        'discount_value' => 5000,
        'max_discount_amount' => null,
        'min_eligible_subtotal' => null,
        'is_active' => true,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addDay(),
        'usage_limit_total' => null,
        'usage_limit_per_user' => 1,
        'eligible_member_type' => null,
        'meta' => null,
        'terms' => null,
        'created_by_user_id' => null,
    ]);

    $code = VoucherCode::query()->create([
        'voucher_campaign_id' => $campaign->id,
        'code' => 'ONEPER',
        'is_active' => true,
        'usage_limit_total' => null,
        'usage_limit_per_user' => null,
        'times_redeemed' => 0,
    ]);

    $trx = Transaction::query()->create([
        'code' => Transaction::generateUniqueCode(),
        'member_id' => null,
        'channel' => 'pos',
        'name' => 'Guest',
        'phone' => '628123',
        'email' => null,
        'order_type' => 'take_away',
        'dining_table_id' => null,
        'subtotal' => 50000,
        'tax_percentage' => 0,
        'tax_amount' => 0,
        'rounding_amount' => 0,
        'cash_received' => 50000,
        'cash_change' => 0,
        'refunded_amount' => 0,
        'total' => 50000,
        'checkout_link' => '',
        'payment_method' => 'cash',
        'payment_status' => 'paid',
        'order_status' => 'new',
        'paid_at' => now(),
        'is_midtrans_processed' => false,
        'external_id' => Transaction::generateUniqueCode(10),
    ]);

    VoucherRedemption::query()->create([
        'voucher_campaign_id' => $campaign->id,
        'voucher_code_id' => $code->id,
        'transaction_id' => (int) $trx->id,
        'member_id' => null,
        'guest_identifier' => '628123',
        'discount_amount' => 5000,
        'snapshot' => [],
        'redeemed_at' => now(),
    ]);

    $service = app(VoucherEligibilityService::class);
    $res = $service->validate('ONEPER', null, $seed['cart'], '628123');

    expect((bool) $res['ok'])->toBeFalse();
    expect((string) $res['message'])->toContain('Kuota voucher');
});

it('restricts voucher to members when campaign is member-only', function () {
    $seed = seedVoucherCart();

    $member = Member::query()->create([
        'name' => 'M',
        'email' => null,
        'email_verified_at' => null,
        'verification_token' => null,
        'phone' => '628123',
        'member_region_id' => null,
        'member_type' => 'premium',
        'points' => 0,
    ]);

    $campaign = VoucherCampaign::query()->create([
        'name' => 'Regular only',
        'description' => null,
        'discount_type' => 'fixed_amount',
        'discount_value' => 5000,
        'max_discount_amount' => null,
        'min_eligible_subtotal' => null,
        'is_active' => true,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addDay(),
        'usage_limit_total' => null,
        'usage_limit_per_user' => null,
        'is_member_only' => true,
        'meta' => null,
        'terms' => null,
        'created_by_user_id' => null,
    ]);

    VoucherCode::query()->create([
        'voucher_campaign_id' => $campaign->id,
        'code' => 'REGONLY',
        'is_active' => true,
        'usage_limit_total' => null,
        'usage_limit_per_user' => null,
        'times_redeemed' => 0,
    ]);

    $service = app(VoucherEligibilityService::class);
    $resGuest = $service->validate('REGONLY', null, $seed['cart'], '628123');
    expect((bool) $resGuest['ok'])->toBeFalse();
    expect((string) $resGuest['message'])->toContain('khusus untuk member');

    $resMember = $service->validate('REGONLY', $member, $seed['cart'], null);
    expect((bool) $resMember['ok'])->toBeTrue();

    expect((string) $resMember['message'])->toContain('dapat digunakan');
});
