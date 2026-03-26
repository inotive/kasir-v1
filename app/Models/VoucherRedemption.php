<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoucherRedemption extends Model
{
    use HasFactory;

    protected $fillable = [
        'voucher_campaign_id',
        'voucher_code_id',
        'transaction_id',
        'member_id',
        'guest_identifier',
        'discount_amount',
        'snapshot',
        'redeemed_at',
    ];

    protected function casts(): array
    {
        return [
            'discount_amount' => 'integer',
            'snapshot' => 'array',
            'redeemed_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(VoucherCampaign::class, 'voucher_campaign_id');
    }

    public function code(): BelongsTo
    {
        return $this->belongsTo(VoucherCode::class, 'voucher_code_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
