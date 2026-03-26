<?php

namespace App\Support\Number;

class QuantityFormatter
{
    public static function format(float|int|null $value, int $maxDecimals = 3): string
    {
        $value = (float) ($value ?? 0);
        $maxDecimals = max(0, min(6, (int) $maxDecimals));

        $formatted = number_format($value, $maxDecimals, ',', '.');

        if ($maxDecimals <= 0) {
            return $formatted;
        }

        $formatted = rtrim($formatted, '0');
        $formatted = rtrim($formatted, ',');

        return $formatted;
    }
}
