<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Addon extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'addon_category_id',
        'name',
        'price',
        'is_available',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'is_available' => 'boolean',
        ];
    }

    public function addonCategory(): BelongsTo
    {
        return $this->belongsTo(AddonCategory::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_addons');
    }
}
