<?php

namespace App\Support\Products;

use App\Models\ProductVariant;
use App\Models\TransactionItem;

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

    /**
     * Build a single display line for a transaction item, combining the
     * product name with its variant (when applicable) and add-ons, e.g.
     * "Americano (Large) + Extra Shot, Oat Milk x2".
     */
    public static function itemDisplayLine(TransactionItem $item): string
    {
        $name = (string) ($item->product?->name ?? 'Produk');

        $variantName = self::displayVariantName((int) $item->product_id, $item->variant?->name);
        if ($variantName !== '') {
            $name .= ' ('.$variantName.')';
        }

        $addonLabels = $item->itemAddons->map(function ($addon) {
            $label = trim((string) ($addon->addon?->name ?? $addon->name ?? ''));
            if ($label === '') {
                return null;
            }

            $qty = (int) $addon->quantity;

            return $qty > 1 ? $label.' x'.$qty : $label;
        })->filter()->values()->all();

        if ($addonLabels !== []) {
            $name .= ' + '.implode(', ', $addonLabels);
        }

        return $name;
    }

    /**
     * The amount actually charged for a transaction item line, including
     * its add-ons (TransactionItem::subtotal only covers the base product).
     */
    public static function itemLineSubtotal(TransactionItem $item): int
    {
        $addonTotal = $item->itemAddons->sum(fn ($addon) => (int) $addon->price * (int) $addon->quantity);

        return (int) $item->subtotal + (int) $addonTotal;
    }
}
