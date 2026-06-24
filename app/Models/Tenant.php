<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Multitenancy\Models\Tenant as SpatieTenant;

class Tenant extends SpatieTenant
{
    protected $fillable = [
        'name',
        'slug',
        'business_name',
        'domain',
        'database',
        'is_active',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
