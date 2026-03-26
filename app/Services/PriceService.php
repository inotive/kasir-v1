<?php

namespace App\Services;

final class PriceService
{
    public static function calculateTax(int $subtotal, float $taxRate): int
    {
        if ($subtotal <= 0 || $taxRate <= 0) {
            return 0;
        }

        return (int) round($subtotal * ($taxRate / 100));
    }

    public static function applyRounding(int $amount, int $roundingBase): array
    {
        if ($roundingBase <= 0) {
            return [
                'total' => $amount,
                'rounding_amount' => 0,
            ];
        }

        $rounded = (int) (round($amount / $roundingBase) * $roundingBase);

        return [
            'total' => $rounded,
            'rounding_amount' => $rounded - $amount,
        ];
    }
}
