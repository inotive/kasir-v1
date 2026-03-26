<?php

namespace App\Livewire\SelfOrder\Components;

use App\Models\Product;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class MenuItemList extends Component
{
    public mixed $items;

    public array $packageContentsByProductId = [];

    public function mount(mixed $items): void
    {
        $this->items = $items;

        $productIds = collect(is_array($items) ? $items : [])
            ->map(fn ($row) => (int) ($row['id'] ?? 0))
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $packages = $productIds === []
            ? collect()
            : Product::query()
                ->whereIn('id', $productIds)
                ->where('is_package', true)
                ->with(['packageItems.componentVariant.product'])
                ->get(['id', 'name', 'is_package']);

        $this->packageContentsByProductId = $packages
            ->mapWithKeys(function (Product $product): array {
                $contents = $product->packageItems
                    ->map(function ($item): array {
                        $variant = $item->componentVariant;
                        $p = $variant?->product;

                        return [
                            'quantity' => (int) $item->quantity,
                            'product_name' => (string) ($p?->name ?? ''),
                            'variant_name' => (string) ($variant?->name ?? ''),
                        ];
                    })
                    ->values()
                    ->all();

                return [(int) $product->id => $contents];
            })
            ->all();
    }

    public function render(): View
    {
        return view('livewire.self-order.components.menu-item-list');
    }
}
