<?php

use App\Models\Member;
use App\Models\Setting;
use App\Models\Transaction;
use App\Services\PointService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $setting = Setting::current();
    $setting->point_earning_rate = 10000;
    $setting->point_redemption_value = 100;
    $setting->min_redemption_points = 10;
    $setting->save();
});

it('calculates earned points correctly', function () {
    $service = new PointService;

    expect($service->calculateEarnedPoints(9999))->toBe(0);
    expect($service->calculateEarnedPoints(10000))->toBe(1);
    expect($service->calculateEarnedPoints(25000))->toBe(2);
});

it('calculates redemption value correctly', function () {
    $service = new PointService;

    expect($service->calculateRedemptionValue(1))->toBe(100);
    expect($service->calculateRedemptionValue(10))->toBe(1000);
});

it('awards points when transaction is paid', function () {
    $member = Member::factory()->create(['points' => 0]);

    $transaction = Transaction::create([
        'code' => 'TEST001',
        'member_id' => $member->id,
        'total' => 100000,
        'subtotal' => 100000,
        'payment_status' => 'pending',
        'order_status' => 'new',
        'cash_received' => 0,
        'cash_change' => 0,
        'name' => 'Test Customer',
        'checkout_link' => 'http://example.com',
        'payment_method' => 'cash',
        'external_id' => 'EXT001',
        'channel' => 'pos',
    ]);

    // Simulate payment
    $transaction->update(['payment_status' => 'paid']);

    // Refresh member
    $member->refresh();
    $transaction->refresh();

    // 100,000 / 10,000 = 10 points
    expect($transaction->points_earned)->toBe(10);
    expect($member->points)->toBe(10);
});

it('does not award points if transaction has no member', function () {
    $transaction = Transaction::create([
        'code' => 'TEST002',
        'member_id' => null,
        'total' => 100000,
        'subtotal' => 100000,
        'payment_status' => 'pending',
        'order_status' => 'new',
        'cash_received' => 0,
        'cash_change' => 0,
        'name' => 'Guest',
        'checkout_link' => 'http://example.com',
        'payment_method' => 'cash',
        'external_id' => 'EXT002',
        'channel' => 'pos',
    ]);

    // Simulate payment
    $transaction->update(['payment_status' => 'paid']);

    $transaction->refresh();

    expect($transaction->points_earned)->toBe(0);
});

it('redeems points correctly', function () {
    $member = Member::factory()->create(['points' => 100]);

    $transaction = Transaction::create([
        'code' => 'TEST003',
        'member_id' => $member->id,
        'total' => 100000,
        'subtotal' => 100000,
        'payment_status' => 'pending',
        'order_status' => 'new',
        'name' => 'Test Customer',
        'checkout_link' => 'http://example.com',
        'payment_method' => 'cash',
        'external_id' => 'EXT003',
        'channel' => 'pos',
    ]);

    $service = new PointService;
    // 10 points >= min_redemption_points (10)
    $service->redeemPoints($transaction, 10); // 10 points -> 1000 IDR

    $transaction->refresh();
    $member->refresh();

    expect($transaction->points_redeemed)->toBe(10);
    expect($transaction->point_discount_amount)->toBe(1000);
    expect($member->points)->toBe(90);
});

it('fails redemption if below minimum', function () {
    $member = Member::factory()->create(['points' => 100]);
    $transaction = Transaction::create([
        'code' => 'TEST004',
        'member_id' => $member->id,
        'total' => 100000,
        'subtotal' => 100000,
        'payment_status' => 'pending',
        'order_status' => 'new',
        'checkout_link' => 'http://example.com',
        'payment_method' => 'cash',
        'external_id' => 'EXT004',
        'channel' => 'pos',
        'name' => 'Test',
    ]);

    $service = new PointService;

    expect(fn () => $service->redeemPoints($transaction, 5))
        ->toThrow(Exception::class, 'Minimum redemption is 10 points.');
});
