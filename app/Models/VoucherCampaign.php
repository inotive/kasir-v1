<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VoucherCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'discount_type',
        'discount_value',
        'max_discount_amount',
        'min_eligible_subtotal',
        'is_active',
        'starts_at',
        'ends_at',
        'usage_limit_total',
        'usage_limit_per_user',
        'is_member_only',
        'meta',
        'terms',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'integer',
            'max_discount_amount' => 'integer',
            'min_eligible_subtotal' => 'integer',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'usage_limit_total' => 'integer',
            'usage_limit_per_user' => 'integer',
            'is_member_only' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function codes(): HasMany
    {
        return $this->hasMany(VoucherCode::class);
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(VoucherRedemption::class);
    }

    public function eligibleCategories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'voucher_campaign_category')->withTimestamps();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
