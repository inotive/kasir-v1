<?php

use App\Support\Number\QuantityFormatter;
use App\Support\Number\QuantityParser;

test('quantity parser accepts comma and thousand separators', function () {
    expect(QuantityParser::parse('2,5'))->toBe(2.5);
    expect(QuantityParser::parse('1.200,75'))->toBe(1200.75);
    expect(QuantityParser::parse('1 200,75'))->toBe(1200.75);
    expect(QuantityParser::parse('0.125'))->toBe(0.125);
    expect(QuantityParser::parse('1.000'))->toBe(1000.0);
});

test('quantity formatter trims trailing zeros and uses id separators', function () {
    expect(QuantityFormatter::format(10))->toBe('10');
    expect(QuantityFormatter::format(10.5))->toBe('10,5');
    expect(QuantityFormatter::format(2.25))->toBe('2,25');
    expect(QuantityFormatter::format(1000.5))->toBe('1.000,5');
    expect(QuantityFormatter::format(0.125))->toBe('0,125');
});

test('quantity formatter handles whole and decimal values', function () {
    expect(QuantityFormatter::format(10))->toBe('10');
    expect(QuantityFormatter::format(10.5))->toBe('10,5');
    expect(QuantityFormatter::format(1000.5))->toBe('1.000,5');
});
