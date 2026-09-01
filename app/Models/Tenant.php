<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
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

    /**
     * Same as Rule::unique(), but scoped to the current tenant so the
     * same value can still be used by a different tenant.
     */
    public static function uniqueRule(string $table, string $column): Unique
    {
        return Rule::unique($table, $column)->where(function ($query) {
            if (static::checkCurrent()) {
                $query->where('tenant_id', static::current()->id);
            } else {
                $query->whereNull('tenant_id');
            }
        });
    }
}
