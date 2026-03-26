<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Member extends Model
{
    /** @use HasFactory<\Database\Factories\MemberFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'verification_token',
        'phone',
        'member_region_id',
        'member_type',
        'points',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'member_type' => 'string',
        'points' => 'integer',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(MemberRegion::class, 'member_region_id');
    }
}
