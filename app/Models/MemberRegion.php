<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MemberRegion extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'province',
        'regency',
        'district',
        'geojson',
    ];

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }
}
