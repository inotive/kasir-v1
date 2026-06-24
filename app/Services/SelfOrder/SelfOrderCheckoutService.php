<?php

namespace App\Services\SelfOrder;

use App\Models\Addon;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;

class SelfOrderCheckoutService
{
    public function validateAndHydrateCartItems(array $cartItems): array
    {
        $validItems = [];
        $itemsChanged = false;

        $variantIds = collect($cartItems)
            ->pluck('variant_id')
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $variants = ProductVariant::query()
            ->with([
                'product:id,name,image,is_available',
                'recipes.ingredient:id,name',
                'product.recipes.ingredient:id,name',
            ])
            ->whereIn('id', $variantIds)
            ->get()
            ->keyBy('id');

        foreach ($cartItems as $item) {
            $productId = isset($item['id']) ? (int) $item['id'] : null;
            $variantId = isset($item['variant_id']) ? (int) $item['variant_id'] : null;
            if (! $productId || ! $variantId) {
                $itemsChanged = true;

                continue;
            }

            $variant = $variants->get($variantId);
            if (! $variant || (int) $variant->product_id !== $productId) {
                $itemsChanged = true;

                continue;
            }

            if (! $variant->product || ! (bool) $variant->product->is_available) {
                $itemsChanged = true;

                continue;
            }

            $basePrice = (int) round((float) $variant->price);
            $discounted = null;
            $percent = (int) ($variant->percent ?? 0);
            if ($percent > 0) {
                $discounted = (int) max(0, round($basePrice - ($basePrice * ($percent / 100))));
            } else {
                $after = (int) round((float) ($variant->price_afterdiscount ?? 0));
                if ($after > 0 && $after < $basePrice) {
                    $discounted = $after;
                }
            }

            $qty = max(1, (int) ($item['quantity'] ?? 1));
            $note = trim((string) ($item['note'] ?? ''));
            if (mb_strlen($note) > 200) {
                $note = mb_substr($note, 0, 200);
                $itemsChanged = true;
            }

            $validItems[] = [
                'id' => (int) $variant->product_id,
                'name' => (string) ($variant->product->name.' - '.$variant->name),
                'image' => (string) ($variant->product->image ?? ''),
                'price' => $basePrice,
                'price_afterdiscount' => $discounted,
                'percent' => $percent ?: null,
                'quantity' => $qty,
                'selected' => (bool) ($item['selected'] ?? true),
                'note' => $note,
                'variant_id' => (int) $variant->id,
                'addons' => $this->validateAddons($item['addons'] ?? []),
            ];
        }

        return [
            'items' => $validItems,
            'changed' => $itemsChanged,
            'variants' => $variants,
        ];
    }

    public function cartHash(array $items): string
    {
        $payload = collect($items)
            ->map(fn (array $i) => [
                'variant_id' => (int) ($i['variant_id'] ?? 0),
                'quantity' => (int) ($i['quantity'] ?? 0),
                'note' => (string) ($i['note'] ?? ''),
                'addon_ids' => collect($i['addons'] ?? [])->pluck('id')->sort()->values()->all(),
            ])
            ->sortBy('variant_id')
            ->values()
            ->toJson();

        return hash('sha256', (string) $payload);
    }

    private function validateAddons(array $rawAddons): array
    {
        if (empty($rawAddons)) {
            return [];
        }

        $addonIds = collect($rawAddons)
            ->pluck('id')
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (empty($addonIds)) {
            return [];
        }

        $addons = Addon::query()
            ->where('is_available', true)
            ->whereIn('id', $addonIds)
            ->get()
            ->keyBy('id');

        $validated = [];
        foreach ($rawAddons as $raw) {
            $addonId = (int) ($raw['id'] ?? 0);
            $addon = $addons->get($addonId);
            if (! $addon) {
                continue;
            }

            $qty = max(1, (int) ($raw['quantity'] ?? 1));
            $validated[] = [
                'id' => (int) $addon->id,
                'name' => (string) $addon->name,
                'price' => (int) round((float) $addon->price),
                'quantity' => $qty,
            ];
        }

        return $validated;
    }

    public function assertSufficientIngredientStock(array $items, Collection $variants): void {}
}
