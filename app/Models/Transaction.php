<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'member_id',
        'channel',
        'name',
        'phone',
        'email',
        'order_type',
        'dining_table_id',
        'voucher_campaign_id',
        'voucher_code_id',
        'voucher_code',
        'subtotal',
        'voucher_discount_amount',
        'manual_discount_type',
        'manual_discount_value',
        'manual_discount_amount',
        'manual_discount_note',
        'manual_discount_by_user_id',
        'discount_total_amount',
        'point_discount_amount',
        'points_redeemed',
        'points_earned',
        'cash_received',
        'cash_change',
        'refunded_amount',
        'total',
        'checkout_link',
        'self_order_token',
        'payment_session_hash',
        'payment_method',
        'payment_status',
        'order_status',
        'paid_at',
        'receipt_emailed_at',
        'external_id',
        'is_midtrans_processed',
        'midtrans_snap_token',
        'midtrans_redirect_url',
        'midtrans_status',
        'midtrans_payload',
        'tax_percentage',
        'tax_amount',
        'payment_fee_amount',
        'rounding_amount',
        'voided_at',
        'voided_by_user_id',
        'void_reason',
        'refunded_at',
        'refunded_by_user_id',
        'refund_reason',
        'kitchen_processed_at',
        'kitchen_processed_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'integer',
            'voucher_discount_amount' => 'integer',
            'manual_discount_value' => 'integer',
            'manual_discount_amount' => 'integer',
            'manual_discount_by_user_id' => 'integer',
            'discount_total_amount' => 'integer',
            'point_discount_amount' => 'integer',
            'points_redeemed' => 'integer',
            'points_earned' => 'integer',
            'cash_received' => 'integer',
            'cash_change' => 'integer',
            'refunded_amount' => 'integer',
            'total' => 'integer',
            'is_midtrans_processed' => 'boolean',
            'tax_percentage' => 'decimal:2',
            'tax_amount' => 'integer',
            'payment_fee_amount' => 'integer',
            'rounding_amount' => 'integer',
            'inventory_applied_at' => 'datetime',
            'voided_at' => 'datetime',
            'refunded_at' => 'datetime',
            'paid_at' => 'datetime',
            'receipt_emailed_at' => 'datetime',
            'midtrans_payload' => 'array',
            'kitchen_processed_at' => 'datetime',
        ];
    }

    public function diningTable(): BelongsTo
    {
        return $this->belongsTo(DiningTable::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function transactionItems(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function voucherCampaign(): BelongsTo
    {
        return $this->belongsTo(VoucherCampaign::class);
    }

    public function voucherCode(): BelongsTo
    {
        return $this->belongsTo(VoucherCode::class);
    }

    public function manualDiscountByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manual_discount_by_user_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(TransactionEvent::class);
    }

    public static function generateUniqueCode(int $length = 8): string
    {
        $currentLength = max(1, $length);
        $maxAttempts = 10;
        $attempts = 0;

        while ($attempts < $maxAttempts) {
            $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            $code = '';

            $hasLetter = false;
            $hasNumber = false;

            for ($i = 0; $i < $currentLength; $i++) {
                $char = $characters[random_int(0, strlen($characters) - 1)];
                $code .= $char;

                if (ctype_alpha($char)) {
                    $hasLetter = true;
                } elseif (ctype_digit($char)) {
                    $hasNumber = true;
                }
            }

            // If we don't have both letters and numbers, regenerate
            if (! $hasLetter || ! $hasNumber) {
                continue;
            }

            $exists = self::where('code', $code)->exists();
            if (! $exists) {
                return $code;
            }

            $attempts++;

            if ($attempts >= $maxAttempts / 2) {
                $currentLength++;
                $attempts = 0;
            }
        }

        return Str::upper(Str::random($length - 4)).now()->format('His');
    }
}
