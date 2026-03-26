<?php

namespace App\Services\Vouchers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class VoucherCodeGenerator
{
    public function generateAlphanumeric(int $count, int $length = 10, bool $excludeAmbiguous = true): array
    {
        $count = max(1, $count);
        $length = max(4, $length);

        $alphabet = $excludeAmbiguous
            ? 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'
            : 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

        $codes = [];
        while (count($codes) < $count) {
            $code = '';
            for ($i = 0; $i < $length; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $codes[$code] = true;
        }

        return array_keys($codes);
    }

    public function normalizeCustom(string $code): string
    {
        $code = trim($code);
        $code = preg_replace('/\s+/', '', $code);
        $code = preg_replace('/[^A-Za-z0-9\-_]/', '', (string) $code);

        return Str::upper((string) $code);
    }

    public function generateFromPattern(string $pattern, int $count, ?Carbon $now = null, int $seqStart = 1): array
    {
        $count = max(1, $count);
        $seqStart = max(1, $seqStart);
        $now = $now ?: now();

        $codes = [];
        $seq = $seqStart;

        while (count($codes) < $count) {
            $code = $this->renderPattern($pattern, $now, $seq);
            $code = $this->normalizeCustom($code);

            if ($code !== '') {
                $codes[$code] = true;
            }

            $seq++;
        }

        return array_keys($codes);
    }

    protected function renderPattern(string $pattern, Carbon $now, int $seq): string
    {
        $out = (string) $pattern;

        $out = str_replace('{YYYY}', $now->format('Y'), $out);
        $out = str_replace('{YY}', $now->format('y'), $out);
        $out = str_replace('{MM}', $now->format('m'), $out);
        $out = str_replace('{DD}', $now->format('d'), $out);

        $out = preg_replace_callback('/\{RAND:(\d+)\}/', function (array $m): string {
            $len = max(1, (int) $m[1]);

            return Str::upper(Str::random($len));
        }, $out) ?? $out;

        $out = preg_replace_callback('/\{SEQ:(\d+)\}/', function (array $m) use ($seq): string {
            $pad = max(1, (int) $m[1]);

            return str_pad((string) $seq, $pad, '0', STR_PAD_LEFT);
        }, $out) ?? $out;

        return $out;
    }
}
