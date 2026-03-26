<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ingredient extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'reorder_level' => 'decimal:3',
            'is_active' => 'boolean',
        ];
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function productRecipes(): HasMany
    {
        return $this->hasMany(ProductRecipe::class);
    }

    public function unitConversions(): HasMany
    {
        return $this->hasMany(IngredientUnitConversion::class);
    }

    public function productVariantRecipes(): HasMany
    {
        return $this->hasMany(ProductVariantRecipe::class);
    }
}
