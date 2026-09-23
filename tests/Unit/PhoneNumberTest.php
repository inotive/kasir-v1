<?php

use App\Support\Phone\PhoneNumber;

test('normalizeForMember converts leading zero to 62', function () {
    expect(PhoneNumber::normalizeForMember('081234567890'))->toBe('6281234567890');
});

test('normalizeForMember leaves an already-62 number untouched', function () {
    expect(PhoneNumber::normalizeForMember('6281234567890'))->toBe('6281234567890');
});

test('normalizeForMember prefixes a bare local number with 62', function () {
    expect(PhoneNumber::normalizeForMember('81234567890'))->toBe('6281234567890');
});

test('normalizeForMember strips non-digit characters', function () {
    expect(PhoneNumber::normalizeForMember('0812-3456-7890'))->toBe('6281234567890');
});

test('normalizeForMember returns null for empty input', function () {
    expect(PhoneNumber::normalizeForMember(null))->toBeNull();
    expect(PhoneNumber::normalizeForMember(''))->toBeNull();
});

test('toWhatsAppChatId appends the c.us suffix', function () {
    expect(PhoneNumber::toWhatsAppChatId('081234567890'))->toBe('6281234567890@c.us');
});

test('toWhatsAppChatId returns null for empty input', function () {
    expect(PhoneNumber::toWhatsAppChatId(null))->toBeNull();
});
