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
            static::scopeQuery($query, 'tenant_id');
        });
    }

    /**
     * Restrict a raw (non-Eloquent) query builder to the current tenant,
     * matching the behaviour of App\Models\Scopes\TenantScope. Needed
     * anywhere a report/export builds its query with DB::table() instead
     * of an Eloquent model, since that bypasses the model's global scope.
     *
     * @template TQuery of \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder
     *
     * @param  TQuery  $query
     * @return TQuery
     */
    public static function scopeQuery($query, string $column = 'tenant_id')
    {
        if (static::checkCurrent()) {
            $query->where($column, static::current()->id);
        } else {
            $query->whereNull($column);
        }

        return $query;
    }
}
