<?php

namespace App\Services\Vouchers;

use App\Models\Member;
use App\Models\Product;
use App\Models\VoucherCode;
use Illuminate\Support\Carbon;

class VoucherEligibilityService
{
    public function __construct(
        protected VoucherDiscountCalculator $calculator,
    ) {}

    public function validate(string $code, ?Member $member, array $cartItems, ?string $guestIdentifier = null, ?Carbon $now = null): array
    {
        $normalized = strtoupper(trim($code));
        if ($normalized === '') {
            return $this->fail('Kode voucher kosong.');
        }

        $voucherCode = VoucherCode::query()
            ->where('code', $normalized)
            ->where('is_active', true)
            ->with(['campaign.eligibleCategories:id'])
            ->first();

        if (! $voucherCode) {
            return $this->fail('Voucher tidak ditemukan atau tidak aktif.');
        }

        $campaign = $voucherCode->campaign;
        if (! $campaign || ! (bool) $campaign->is_active) {
            return $this->fail('Program voucher tidak aktif.');
        }

        $now = $now ?: now();
        if ($campaign->starts_at && $now->lt($campaign->starts_at)) {
            return $this->fail('Voucher belum berlaku.');
        }
        if ($campaign->ends_at && $now->gt($campaign->ends_at)) {
            return $this->fail('Voucher sudah kedaluwarsa.');
        }

        if ($campaign->is_member_only && ! $member) {
            return $this->fail('Voucher khusus untuk member.');
        }

        $guestIdentifier = $guestIdentifier ? trim($guestIdentifier) : null;
        if (! $member && ($guestIdentifier === null || $guestIdentifier === '')) {
            return $this->fail('Tidak ada identitas pelanggan untuk kuota voucher.');
        }

        $effectiveTotalLimit = $voucherCode->usage_limit_total ?? $campaign->usage_limit_total;
        if ($effectiveTotalLimit !== null) {
            $totalUsed = (int) $voucherCode->times_redeemed;
            if ($totalUsed >= (int) $effectiveTotalLimit) {
                return $this->fail('Kuota voucher sudah habis.');
            }
        }

        $effectivePerUserLimit = $voucherCode->usage_limit_per_user ?? $campaign->usage_limit_per_user;
        if ($effectivePerUserLimit !== null) {
            $q = $voucherCode->redemptions()->newQuery();
            if ($member) {
                $q->where('member_id', (int) $member->id);
            } else {
                $q->where('guest_identifier', (string) $guestIdentifier);
            }
            $usedByUser = (int) $q->count();
            if ($usedByUser >= (int) $effectivePerUserLimit) {
                return $this->fail('Kuota voucher untuk pelanggan ini sudah habis.');
            }
        }

        $resolved = $this->resolveCart($cartItems);
        if ($resolved['cart_count'] <= 0) {
            return $this->fail('Keranjang kosong.');
        }

        $eligibleCategoryIds = $campaign->eligibleCategories->pluck('id')->map(fn ($v) => (int) $v)->all();
        $eligibleLines = $this->eligibleLines($resolved['lines'], $eligibleCategoryIds);
        $eligibleSubtotal = array_sum(array_map(fn ($l) => (int) $l['subtotal'], $eligibleLines));

        $min = $campaign->min_eligible_subtotal === null ? null : (int) $campaign->min_eligible_subtotal;
        if ($min !== null && $eligibleSubtotal < $min) {
            return $this->fail('Minimal belanja untuk voucher belum terpenuhi.');
        }

        $calc = $this->calculator->calculate($campaign, $eligibleSubtotal);
        $discountAmount = (int) ($calc['discount_amount'] ?? 0);

        if ($discountAmount <= 0) {
            return $this->fail('Voucher tidak menghasilkan diskon.');
        }

        $alloc = $this->calculator->allocate($discountAmount, $eligibleLines);
        $allocations = (array) ($alloc['allocations'] ?? []);

        return [
            'ok' => true,
            'message' => 'Voucher dapat digunakan.',
            'voucher_code' => $voucherCode,
            'campaign' => $campaign,
            'eligible_subtotal' => $eligibleSubtotal,
            'discount_amount' => $discountAmount,
            'eligible_lines' => $eligibleLines,
            'allocations' => $allocations,
        ];
    }

    protected function resolveCart(array $cartItems): array
    {
        $lines = [];
        $productIds = [];

        foreach ($cartItems as $i => $item) {
            $pid = (int) ($item['product_id'] ?? $item['id'] ?? 0);
            $qty = (int) ($item['quantity'] ?? 0);

            $price = $item['price'] ?? $item['price_afterdiscount'] ?? 0;
            $price = (int) round((float) $price);

            if ($pid <= 0 || $qty <= 0 || $price < 0) {
                continue;
            }

            $lines[] = [
                'index' => (int) $i,
                'product_id' => $pid,
                'variant_id' => (int) ($item['variant_id'] ?? 0),
                'quantity' => $qty,
                'price' => $price,
                'subtotal' => $price * $qty,
            ];
            $productIds[$pid] = true;
        }

        if (count($lines) === 0) {
            return ['cart_count' => 0, 'lines' => []];
        }

        $products = Product::query()
            ->whereIn('id', array_keys($productIds))
            ->get(['id', 'category_id'])
            ->keyBy('id');

        foreach ($lines as $k => $line) {
            $prod = $products->get($line['product_id']);
            $lines[$k]['category_id'] = $prod ? (int) $prod->category_id : null;
        }

        return [
            'cart_count' => count($lines),
            'lines' => $lines,
        ];
    }

    protected function eligibleLines(array $lines, array $eligibleCategoryIds): array
    {
        if (count($eligibleCategoryIds) === 0) {
            return array_values(array_filter($lines, fn ($l) => (int) ($l['subtotal'] ?? 0) > 0));
        }

        $set = [];
        foreach ($eligibleCategoryIds as $id) {
            $set[(int) $id] = true;
        }

        return array_values(array_filter($lines, function (array $l) use ($set): bool {
            $cat = $l['category_id'] ?? null;
            if ($cat === null) {
                return false;
            }

            return isset($set[(int) $cat]) && (int) ($l['subtotal'] ?? 0) > 0;
        }));
    }

    protected function fail(string $message): array
    {
        return [
            'ok' => false,
            'message' => $message,
        ];
    }
}
