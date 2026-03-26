<?php

namespace App\Livewire\SelfOrder\Traits;

use App\Livewire\SelfOrder\Components\CartBadge;
use App\Models\Member;
use App\Models\Setting;
use App\Services\PriceService;

trait CartManagement
{
    public bool $vouchersEnabled = true;

    public int $voucherDiscountAmount = 0;

    public bool $usePoints = false;

    public int $availablePoints = 0;

    public int $minRedemptionPoints = 0;

    public float $pointRedemptionValue = 0;

    public int $maxRedeemablePoints = 0;

    public bool $canUsePoints = false;

    public string $pointsEligibilityMessage = '';

    public int $pointsToRedeem = 0;

    public int $pointDiscountAmount = 0;

    public int $discountTotalAmount = 0;

    public int $netSubtotal = 0;

    public bool $voucherValid = false;

    public string $voucherMessage = '';

    public function increment($index)
    {
        $this->cartItems[$index]['quantity']++;
        session(['cart_items' => $this->cartItems]);
        $this->hasUnpaidTransaction = false;
        $this->updateTotals();
        $this->dispatch('cart-updated')->to(CartBadge::class);
    }

    public function decrement($index)
    {
        if ($this->cartItems[$index]['quantity'] > 1) {
            $this->cartItems[$index]['quantity']--;
        }
        session(['cart_items' => $this->cartItems]);
        $this->hasUnpaidTransaction = false;
        $this->updateTotals();
        $this->dispatch('cart-updated')->to(CartBadge::class);
    }

    public function removeItem(int $index)
    {
        if (! isset($this->cartItems[$index])) {
            return;
        }

        array_splice($this->cartItems, $index, 1);
        session(['cart_items' => $this->cartItems]);
        $this->hasUnpaidTransaction = false;
        $this->updateTotals();
        $this->dispatch('cart-updated')->to(CartBadge::class);
    }

    public function updateTotals()
    {
        $grossSubtotal = array_sum(array_map(function ($item) {
            $hasDiscount = isset($item['price_afterdiscount']) && (int) $item['price_afterdiscount'] > 0 && (int) $item['price_afterdiscount'] < (int) $item['price'];
            $price = $hasDiscount ? (int) $item['price_afterdiscount'] : (int) $item['price'];

            return $price * (int) $item['quantity'];
        }, $this->cartItems));

        $setting = Setting::current();
        $this->taxRate = (float) ($setting->tax_rate ?? 0);

        $this->voucherValid = false;
        $this->voucherMessage = '';
        $this->voucherDiscountAmount = 0;
        $this->availablePoints = 0;
        $this->minRedemptionPoints = 0;
        $this->pointRedemptionValue = 0;
        $this->maxRedeemablePoints = 0;
        $this->canUsePoints = false;
        $this->pointsEligibilityMessage = '';
        $this->pointsToRedeem = 0;
        $this->pointDiscountAmount = 0;

        if ($this->vouchersEnabled) {
            $voucherCode = session('self_order_voucher_code');
            if (is_string($voucherCode) && trim($voucherCode) !== '' && $grossSubtotal > 0) {
                $member = null;
                $memberId = session('member_id');
                if (is_numeric($memberId)) {
                    $member = Member::query()->find((int) $memberId);
                }

                $guestId = $member ? null : (is_string(session('phone')) ? (string) session('phone') : null);

                $elig = app(\App\Services\Vouchers\VoucherEligibilityService::class)
                    ->validate($voucherCode, $member, $this->cartItems, $guestId);

                if ((bool) ($elig['ok'] ?? false)) {
                    $this->voucherValid = true;
                    $this->voucherMessage = (string) ($elig['message'] ?? 'Voucher dapat digunakan.');
                    $this->voucherDiscountAmount = (int) ($elig['discount_amount'] ?? 0);
                } else {
                    $this->voucherMessage = (string) ($elig['message'] ?? 'Voucher tidak bisa digunakan.');
                }
            }
        }

        $this->subtotal = (int) $grossSubtotal;
        $this->discountTotalAmount = max(0, (int) $this->voucherDiscountAmount);

        $member = null;
        if ((string) session('customer_type') === 'member' && is_numeric(session('member_id'))) {
            $member = Member::query()->find((int) session('member_id'));
            $this->availablePoints = (int) ($member?->points ?? 0);
        }

        $remainingBase = max(0, (int) $this->subtotal - (int) $this->voucherDiscountAmount);

        if ($member && $grossSubtotal > 0) {
            $this->minRedemptionPoints = (int) ($setting->min_redemption_points ?? 0);
            $this->pointRedemptionValue = (float) ($setting->point_redemption_value ?? 0);

            if ($this->pointRedemptionValue > 0 && $remainingBase > 0) {
                $this->maxRedeemablePoints = (int) floor($remainingBase / $this->pointRedemptionValue);
            }

            if ($this->availablePoints <= 0) {
                $this->pointsEligibilityMessage = 'Poin Anda belum tersedia.';
            } elseif ($remainingBase <= 0) {
                $this->pointsEligibilityMessage = 'Total sudah nol setelah diskon.';
            } elseif ($this->pointRedemptionValue <= 0) {
                $this->pointsEligibilityMessage = 'Poin belum bisa digunakan saat ini.';
            } elseif ($this->minRedemptionPoints > 0 && $this->availablePoints < $this->minRedemptionPoints) {
                $this->pointsEligibilityMessage = 'Minimal '.$this->minRedemptionPoints.' poin untuk digunakan.';
            } elseif ($this->minRedemptionPoints > 0 && $this->maxRedeemablePoints > 0 && $this->maxRedeemablePoints < $this->minRedemptionPoints) {
                $this->pointsEligibilityMessage = 'Total belanja belum cukup untuk menggunakan '.$this->minRedemptionPoints.' poin.';
            }

            if ($this->minRedemptionPoints > 0) {
                $this->canUsePoints =
                    $this->pointRedemptionValue > 0 &&
                    $remainingBase > 0 &&
                    $this->availablePoints >= $this->minRedemptionPoints &&
                    $this->maxRedeemablePoints >= $this->minRedemptionPoints;
            } else {
                $this->canUsePoints =
                    $this->pointRedemptionValue > 0 &&
                    $remainingBase > 0 &&
                    $this->availablePoints > 0 &&
                    $this->maxRedeemablePoints > 0;
            }
        }

        if ($this->usePoints && $this->canUsePoints && $member && $grossSubtotal > 0) {
            $min = (int) $this->minRedemptionPoints;
            $min = $min > 0 ? $min : 1;
            $maxPointsByAmount = (int) $this->maxRedeemablePoints;
            $points = min($this->availablePoints, $maxPointsByAmount);

            if ($points >= $min) {
                $this->pointsToRedeem = (int) $points;
                $this->pointDiscountAmount = (int) app(\App\Services\PointService::class)->calculateRedemptionValue($this->pointsToRedeem);
                $this->pointDiscountAmount = min($this->pointDiscountAmount, $remainingBase);
            }
        }

        $this->discountTotalAmount = max(0, (int) $this->voucherDiscountAmount + (int) $this->pointDiscountAmount);
        $this->netSubtotal = max(0, (int) $this->subtotal - (int) $this->discountTotalAmount);

        $taxBase = (bool) ($setting->discount_applies_before_tax ?? true) ? $this->netSubtotal : $this->subtotal;
        $this->tax = PriceService::calculateTax($taxBase, $this->taxRate);

        $totalBeforeRounding = $this->netSubtotal + $this->tax;
        $roundingBase = max(0, (int) ($setting->rounding_base ?? 0));
        $rounded = PriceService::applyRounding($totalBeforeRounding, $roundingBase);
        $this->total = (int) ($rounded['total'] ?? $totalBeforeRounding);
        $this->rounding_adjustment = (int) ($rounded['rounding_amount'] ?? 0);
    }
}
