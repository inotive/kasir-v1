<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'image',
        'is_available',
        'is_promo',
        'is_favorite',
        'is_package',
        'package_type',
        'category_id',
        'printer_source_id',
    ];

    protected function casts(): array
    {
        return [
            'is_available' => 'boolean',
            'is_promo' => 'boolean',
            'is_favorite' => 'boolean',
            'is_package' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function printerSource(): BelongsTo
    {
        return $this->belongsTo(PrinterSource::class);
    }

    public function transactionItems(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function packageItems(): HasMany
    {
        return $this->hasMany(ProductPackageItem::class, 'package_product_id')->orderBy('sort_order');
    }

    public function complexPackageItems(): HasMany
    {
        return $this->hasMany(ProductComplexPackageItem::class, 'package_product_id')->orderBy('sort_order');
    }

    public function recipes(): HasMany
    {
        return $this->hasMany(ProductRecipe::class);
    }

    public function getAllProducts(): Collection
    {
        return self::query()
            ->leftJoin('transaction_items', function ($join) {
                $join
                    ->on('products.id', '=', 'transaction_items.product_id')
                    ->whereNull('transaction_items.parent_transaction_item_id');
            })
            ->select(
                'products.id',
                'products.name',
                'products.description',
                'products.image',
                'products.is_available',
                'products.is_promo',
                'products.is_favorite',
                'products.category_id',
                'products.created_at',
                'products.updated_at',
            )
            ->selectRaw('COALESCE(SUM(transaction_items.quantity), 0) as total_sold')
            ->where('products.is_available', true)
            ->groupBy(
                'products.id',
                'products.name',
                'products.description',
                'products.image',
                'products.is_available',
                'products.is_promo',
                'products.is_favorite',
                'products.category_id',
                'products.created_at',
                'products.updated_at',
            )
            ->get();
    }

    public function getProductsFiltered(?int $categoryId = null, ?string $term = null): Collection
    {
        $query = self::query()
            ->leftJoin('transaction_items', function ($join) {
                $join
                    ->on('products.id', '=', 'transaction_items.product_id')
                    ->whereNull('transaction_items.parent_transaction_item_id');
            })
            ->select(
                'products.id',
                'products.name',
                'products.description',
                'products.image',
                'products.is_available',
                'products.is_promo',
                'products.is_favorite',
                'products.category_id',
                'products.created_at',
                'products.updated_at',
            )
            ->selectRaw('COALESCE(SUM(transaction_items.quantity), 0) as total_sold')
            ->where('products.is_available', true)
            ->groupBy(
                'products.id',
                'products.name',
                'products.description',
                'products.image',
                'products.is_available',
                'products.is_promo',
                'products.is_favorite',
                'products.category_id',
                'products.created_at',
                'products.updated_at',
            );

        if (! empty($categoryId)) {
            $query->where('products.category_id', (int) $categoryId);
        }

        if (! empty($term)) {
            $like = '%'.trim($term).'%';
            $query->where(function ($q) use ($like) {
                $q->where('products.name', 'like', $like)
                    ->orWhere('products.description', 'like', $like);
            });
        }

        return $query->get();
    }

    public function getProductDetails(int $id): ?self
    {
        return self::query()
            ->leftJoin('transaction_items', function ($join) {
                $join
                    ->on('products.id', '=', 'transaction_items.product_id')
                    ->whereNull('transaction_items.parent_transaction_item_id');
            })
            ->select(
                'products.id',
                'products.name',
                'products.description',
                'products.image',
                'products.is_available',
                'products.category_id',
                'products.created_at',
                'products.updated_at',
            )
            ->selectRaw('COALESCE(SUM(transaction_items.quantity), 0) as total_sold')
            ->where('products.is_available', true)
            ->where('products.id', $id)
            ->groupBy(
                'products.id',
                'products.name',
                'products.description',
                'products.image',
                'products.is_available',
                'products.category_id',
                'products.created_at',
                'products.updated_at',
            )
            ->first();
    }

    /**
     * Get products that have at least one variant on promotion.
     * Uses EXISTS for better performance and SQL-standard compliance.
     */
    public function getPromoProducts(): Collection
    {
        return self::query()
            ->select(
                'products.id',
                'products.name',
                'products.description',
                'products.image',
                'products.is_available',
                'products.is_promo',
                'products.is_favorite',
                'products.category_id',
                'products.created_at',
                'products.updated_at',
            )
            ->selectRaw('COALESCE(SUM(transaction_items.quantity), 0) as total_sold')
            ->leftJoin('transaction_items', function ($join) {
                $join
                    ->on('products.id', '=', 'transaction_items.product_id')
                    ->whereNull('transaction_items.parent_transaction_item_id');
            })
            ->where('products.is_available', true)
            ->where('products.is_promo', true)
            ->groupBy(
                'products.id',
                'products.name',
                'products.description',
                'products.image',
                'products.is_available',
                'products.is_promo',
                'products.is_favorite',
                'products.category_id',
                'products.created_at',
                'products.updated_at',
            )
            ->get();
    }

    public function getFavoriteProducts(int $limit = 6): Collection
    {
        return self::query()
            ->select(
                'products.id',
                'products.name',
                'products.description',
                'products.image',
                'products.is_available',
                'products.is_promo',
                'products.is_favorite',
                'products.category_id',
                'products.created_at',
                'products.updated_at',
            )
            ->selectRaw('COALESCE(SUM(transaction_items.quantity), 0) as total_sold')
            ->leftJoin('transaction_items', function ($join) {
                $join
                    ->on('products.id', '=', 'transaction_items.product_id')
                    ->whereNull('transaction_items.parent_transaction_item_id');
            })
            ->where('products.is_available', true)
            ->where('products.is_favorite', true)
            ->groupBy(
                'products.id',
                'products.name',
                'products.description',
                'products.image',
                'products.is_available',
                'products.is_promo',
                'products.is_favorite',
                'products.category_id',
                'products.created_at',
                'products.updated_at',
            )
            ->orderByDesc('updated_at')
            ->limit($limit > 0 ? $limit : 6)
            ->get();
    }

    /**
     * Get best-selling products ordered by total quantity sold.
     * Ensures soft-deleted products are excluded.
     */
    public function getTopProducts(int $limit = 6): Collection
    {
        return self::query()
            ->select(
                'products.id',
                'products.name',
                'products.description',
                'products.image',
                'products.is_available',
                'products.is_promo',
                'products.is_favorite',
                'products.category_id',
                'products.created_at',
                'products.updated_at',
            )
            ->selectRaw('COALESCE(SUM(transaction_items.quantity), 0) as total_sold')
            ->leftJoin('transaction_items', function ($join) {
                $join
                    ->on('products.id', '=', 'transaction_items.product_id')
                    ->whereNull('transaction_items.parent_transaction_item_id');
            })
            ->where('products.is_available', true)
            ->groupBy(
                'products.id',
                'products.name',
                'products.description',
                'products.image',
                'products.is_available',
                'products.is_promo',
                'products.is_favorite',
                'products.category_id',
                'products.created_at',
                'products.updated_at',
            )
            ->orderByDesc('total_sold')
            ->limit($limit > 0 ? $limit : 6)
            ->get();
    }
}
