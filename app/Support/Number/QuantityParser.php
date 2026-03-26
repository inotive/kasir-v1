<?php

namespace App\Support\Number;

class QuantityParser
{
    public static function parse(float|int|string|null $input): ?float
    {
        if ($input === null) {
            return null;
        }

        if (is_int($input) || is_float($input)) {
            return (float) $input;
        }

        $value = trim((string) $input);
        if ($value === '') {
            return null;
        }

        $value = preg_replace('/\s+/', '', $value) ?? $value;
        $value = str_replace(['Rp', 'rp'], '', $value);

        $hasComma = str_contains($value, ',');
        $hasDot = str_contains($value, '.');

        if ($hasComma && $hasDot) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } elseif ($hasComma) {
            $value = str_replace(',', '.', $value);
        } elseif ($hasDot) {
            if (preg_match('/^-?\d{1,3}(\.\d{3})+$/', $value) === 1) {
                $first = explode('.', $value, 2)[0] ?? '';
                $first = ltrim((string) $first, '-');
                if ($first !== '0') {
                    $value = str_replace('.', '', $value);
                }
            }
        }

        $value = preg_replace('/[^0-9\.\-]/', '', $value) ?? $value;
        if ($value === '' || $value === '-' || $value === '.' || $value === '-.') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }
}
