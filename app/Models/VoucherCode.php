<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VoucherCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'voucher_campaign_id',
        'code',
        'is_active',
        'usage_limit_total',
        'usage_limit_per_user',
        'times_redeemed',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'usage_limit_total' => 'integer',
            'usage_limit_per_user' => 'integer',
            'times_redeemed' => 'integer',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(VoucherCampaign::class, 'voucher_campaign_id');
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(VoucherRedemption::class);
    }
}
