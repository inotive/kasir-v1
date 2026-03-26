<?php

namespace App\Services\Vouchers;

use App\Models\VoucherCampaign;

class VoucherDiscountCalculator
{
    public function calculate(VoucherCampaign $campaign, int $eligibleSubtotal): array
    {
        $eligibleSubtotal = max(0, (int) $eligibleSubtotal);

        if ($eligibleSubtotal <= 0) {
            return [
                'discount_amount' => 0,
            ];
        }

        $type = (string) $campaign->discount_type;
        $value = (int) ($campaign->discount_value ?? 0);

        if ($type === 'percent') {
            $percent = max(0, min(100, $value));
            $discount = (int) round($eligibleSubtotal * ($percent / 100));
        } else {
            $discount = max(0, $value);
        }

        $maxDiscount = $campaign->max_discount_amount === null ? null : (int) $campaign->max_discount_amount;
        if ($maxDiscount !== null) {
            $discount = min($discount, max(0, $maxDiscount));
        }

        $discount = min($discount, $eligibleSubtotal);

        return [
            'discount_amount' => max(0, (int) $discount),
        ];
    }

    public function allocate(int $discountAmount, array $eligibleLines): array
    {
        $discountAmount = max(0, (int) $discountAmount);
        if ($discountAmount <= 0) {
            return [
                'allocations' => array_fill(0, count($eligibleLines), 0),
            ];
        }

        $subtotals = array_map(fn ($l) => max(0, (int) ($l['subtotal'] ?? 0)), $eligibleLines);
        $sum = array_sum($subtotals);

        if ($sum <= 0) {
            return [
                'allocations' => array_fill(0, count($eligibleLines), 0),
            ];
        }

        $allocations = [];
        $remainders = [];
        $allocated = 0;

        foreach ($subtotals as $i => $st) {
            $raw = ($discountAmount * $st) / $sum;
            $floor = (int) floor($raw);
            $allocations[$i] = $floor;
            $remainders[$i] = $raw - $floor;
            $allocated += $floor;
        }

        $left = $discountAmount - $allocated;
        if ($left > 0) {
            arsort($remainders);
            foreach (array_keys($remainders) as $i) {
                if ($left <= 0) {
                    break;
                }
                $allocations[$i] = (int) $allocations[$i] + 1;
                $left--;
            }
        }

        ksort($allocations);

        return [
            'allocations' => array_values($allocations),
        ];
    }
}
