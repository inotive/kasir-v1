<?php

use App\Helpers\DataLabelHelper;
use Tests\TestCase;

uses(TestCase::class);

it('formats enum values into uppercase labels', function () {
    expect(DataLabelHelper::enum('cash', 'payment_method'))->toBe('Tunai');
    expect(DataLabelHelper::enum('qris_midtrans', 'payment_method'))->toBe('QRIS Midtrans');
    expect(DataLabelHelper::enum('pending', 'payment_status'))->toBe('Belum Dibayar');
    expect(DataLabelHelper::enum('take_away', 'order_type'))->toBe('TAKE AWAY');
    expect(DataLabelHelper::enum('midtrans.qris'))->toBe('MIDTRANS QRIS');
});

it('returns placeholder for empty values', function () {
    expect(DataLabelHelper::enum(null))->toBe('-');
    expect(DataLabelHelper::enum(''))->toBe('-');
    expect(DataLabelHelper::enum('   '))->toBe('-');
});
