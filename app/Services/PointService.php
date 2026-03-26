<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Transaction;
use Exception;

class PointService
{
    /**
     * Calculate points earned from a transaction amount.
     */
    public function calculateEarnedPoints(int $amount): int
    {
        $setting = Setting::current();
        $earningRate = (float) $setting->point_earning_rate;

        if ($earningRate <= 0 || $amount < $earningRate) {
            return 0;
        }

        return (int) floor($amount / $earningRate);
    }

    /**
     * Calculate discount amount from points.
     */
    public function calculateRedemptionValue(int $points): int
    {
        $setting = Setting::current();
        $redemptionValue = (float) $setting->point_redemption_value;

        return (int) ($points * $redemptionValue);
    }

    /**
     * Process points earning for a completed transaction.
     */
    public function awardPoints(Transaction $transaction): void
    {
        // Only award points if member exists
        if (! $transaction->member_id) {
            return;
        }

        // Prevent double awarding if points_earned is already set
        if ($transaction->points_earned > 0) {
            return;
        }

        // Calculate based on subtotal after discounts (as per spec)
        // Or simply use total (which is amount paid).
        // Spec: "calculate points based on subtotal (after other discounts)"
        // Let's use total as it represents the net amount paid by customer (including tax usually).
        // If we want strictly subtotal - discounts:
        // $amount = $transaction->subtotal - $transaction->discount_total_amount;
        // But usually points are awarded on the final bill amount.
        $amount = $transaction->total;

        $points = $this->calculateEarnedPoints($amount);

        if ($points > 0) {
            $transaction->updateQuietly(['points_earned' => $points]);

            $transaction->member->increment('points', $points);
        }
    }

    /**
     * Process points redemption (deduct points from member).
     * This should be called when applying the discount during order creation/update.
     */
    public function redeemPoints(Transaction $transaction, int $pointsToRedeem): void
    {
        if (! $transaction->member_id) {
            throw new Exception('Transaction does not belong to a member.');
        }

        $member = $transaction->member;

        $setting = Setting::current();
        $redemptionValue = (float) $setting->point_redemption_value;
        if ($redemptionValue <= 0) {
            throw new Exception('Point redemption value is not configured.');
        }

        $baseForPoints = max(0, (int) $transaction->subtotal - (int) ($transaction->voucher_discount_amount ?? 0) - (int) ($transaction->manual_discount_amount ?? 0));
        $maxByBase = $baseForPoints > 0 ? (int) floor($baseForPoints / $redemptionValue) : 0;

        $pointsToRedeem = min((int) $pointsToRedeem, (int) $member->points, (int) $maxByBase);
        if ($pointsToRedeem <= 0) {
            throw new Exception('Point redemption exceeds allowed amount.');
        }

        if ($member->points < $pointsToRedeem) {
            throw new Exception('Insufficient points.');
        }

        if ($pointsToRedeem < $setting->min_redemption_points) {
            throw new Exception("Minimum redemption is {$setting->min_redemption_points} points.");
        }

        $discountAmount = $this->calculateRedemptionValue($pointsToRedeem);
        $discountAmount = min($discountAmount, $baseForPoints);

        // Update transaction
        $transaction->update([
            'points_redeemed' => $pointsToRedeem,
            'point_discount_amount' => $discountAmount,
        ]);

        // Deduct points immediately
        $member->decrement('points', $pointsToRedeem);
    }
}
