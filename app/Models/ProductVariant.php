<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'name',
        'price',
        'price_afterdiscount',
        'percent',
        'hpp',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'price_afterdiscount' => 'integer',
            'percent' => 'integer',
            'hpp' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function transactionItems(): HasMany
    {
        return $this->hasMany(TransactionItem::class, 'product_variant_id');
    }

    public function recipes(): HasMany
    {
        return $this->hasMany(ProductVariantRecipe::class, 'product_variant_id');
    }
}
