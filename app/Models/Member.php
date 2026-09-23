<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Database\Factories\MemberFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Member extends Model
{
    /** @use HasFactory<MemberFactory> */
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'verification_token',
        'phone',
        'address',
        'member_region_id',
        'member_type',
        'points',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'member_type' => 'string',
        'points' => 'integer',
    ];

    public function displayLabel(bool $showPhone = false): string
    {
        $phone = trim((string) $this->phone);
        $identifier = $showPhone && $phone !== '' ? $phone : 'Member #'.$this->id;

        return $this->name.' ('.$identifier.')';
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(MemberRegion::class, 'member_region_id');
    }
}
