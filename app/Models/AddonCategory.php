<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AddonCategory extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
    ];

    public function addons(): HasMany
    {
        return $this->hasMany(Addon::class);
    }
}
