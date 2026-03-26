<?php

namespace App\Support\Products;

use App\Models\ProductVariant;

class ItemNameFormatter
{
    private static array $variantCountCache = [];

    public static function resetCache(): void
    {
        self::$variantCountCache = [];
    }

    public static function variantCountForProduct(int $productId): int
    {
        $productId = (int) $productId;
        if ($productId <= 0) {
            return 0;
        }

        if (array_key_exists($productId, self::$variantCountCache)) {
            return (int) self::$variantCountCache[$productId];
        }

        $count = (int) ProductVariant::query()
            ->where('product_id', $productId)
            ->count();

        self::$variantCountCache[$productId] = $count;

        return $count;
    }

    public static function shouldShowVariantName(int $productId): bool
    {
        return self::variantCountForProduct($productId) > 1;
    }

    public static function displayVariantName(?int $productId, ?string $variantName): string
    {
        $productId = $productId === null ? 0 : (int) $productId;
        $variantName = trim((string) $variantName);

        if ($productId <= 0 || $variantName === '') {
            return '';
        }

        if (! self::shouldShowVariantName($productId)) {
            return '';
        }

        return $variantName;
    }
}
