<?php

namespace App\Livewire\SelfOrder\Pages;

use App\Livewire\SelfOrder\Components\CartBadge;
use App\Models\Addon;
use App\Models\Category;
use App\Models\DiningTable;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Support\Products\ItemNameFormatter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

class HomePage extends Component
{
    public $setting;

    public $categories;

    public $allFoods;

    public $promoFoods;

    public $favoriteFoods;

    public $tableNumber;

    public $term = '';

    public ?int $activeCategoryId = null;

    public array $variantOptions = [];

    public array $variantQuantities = [];

    public function mount(Product $product)
    {
        $this->setting = Setting::current();
        $this->categories = cache()->remember('self_order.categories', 60, fn () => Category::query()->get());
        $this->allFoods = cache()->remember('self_order.products', 15, fn () => $product->getAllProducts());
        $this->promoFoods = cache()->remember('self_order.products.promo', 15, fn () => $product->getPromoProducts()->take(8));
        $this->favoriteFoods = cache()->remember('self_order.products.favorite', 15, fn () => $product->getFavoriteProducts(8));
        $tableId = session('dining_table_id');
        $this->tableNumber = $tableId ? optional(DiningTable::find($tableId))->table_number : null;
    }

    public function selectCategory(?int $categoryId = null): void
    {
        $this->activeCategoryId = $categoryId;
    }

    #[On('open-product-variants')]
    public function openProductVariants(int $productId): void
    {
        $product = Product::query()
            ->with(['packageItems.componentVariant.product', 'addons.addonCategory'])
            ->where('id', $productId)
            ->where('is_available', true)
            ->first();
        if (! $product) {
            return;
        }

        $variants = ProductVariant::query()
            ->where('product_id', $productId)
            ->orderBy('name')
            ->get(['id', 'name', 'price', 'price_afterdiscount', 'percent']);

        $this->variantOptions = $variants->map(fn ($v) => [
            'id' => (int) $v->id,
            'name' => (string) $v->name,
            'price' => (int) round((float) $v->price),
            'price_afterdiscount' => (int) round((float) ($v->price_afterdiscount ?? 0)),
            'percent' => (int) ($v->percent ?? 0),
        ])->toArray();

        foreach ($this->variantOptions as $opt) {
            if (! isset($this->variantQuantities[$opt['id']])) {
                $this->variantQuantities[$opt['id']] = 1;
            }
        }

        $dispatchParams = [
            'quantities' => $this->variantQuantities,
            'productName' => (string) $product->name,
        ];

        if ((bool) $product->is_package) {
            $dispatchParams['isPackage'] = true;
            $dispatchParams['packageContents'] = $product->packageItems
                ->map(function ($item): array {
                    $variant = $item->componentVariant;
                    $product = $variant?->product;
                    $variantName = ItemNameFormatter::displayVariantName($product?->id === null ? null : (int) $product->id, $variant?->name);

                    return [
                        'quantity' => (int) $item->quantity,
                        'product_name' => (string) ($product?->name ?? ''),
                        'variant_name' => $variantName,
                    ];
                })
                    ->values()
                    ->all();
        }

        if ($product->addons->isNotEmpty()) {
            $dispatchParams['addonGroups'] = $product->addons
                ->groupBy(fn ($a) => $a->addonCategory?->name ?? 'Lainnya')
                ->map(fn ($addons, $categoryName) => [
                    'category' => $categoryName,
                    'items' => $addons->map(fn ($a) => [
                        'id' => (int) $a->id,
                        'name' => (string) $a->name,
                        'price' => (int) round((float) $a->price),
                    ])->values()->all(),
                ])
                ->values()
                ->all();

            $allAddons = $product->addons
                ->sortBy('name')
                ->values()
                ->map(fn ($a) => [
                    'id' => (int) $a->id,
                    'name' => (string) $a->name,
                    'price' => (int) round((float) $a->price),
                    'category' => (string) ($a->addonCategory?->name ?? 'Lainnya'),
                ])
                ->values()
                ->all();

            $dispatchParams['allAddons'] = $allAddons;
        }

        if (count($this->variantOptions) === 1 && $product->addons->isEmpty()) {
            $this->addVariantToCart((int) $this->variantOptions[0]['id']);

            return;
        }

        if (count($this->variantOptions) === 1) {
            $dispatchParams['selectedVariant'] = $this->variantOptions[0];
        }

        $this->dispatch('open-variant-modal', ...$dispatchParams);
    }

    public function addVariantToCart(int $variantId, int $quantity = 1, array $addonData = []): void
    {
        $qty = max(1, $quantity);

        $variant = ProductVariant::query()
            ->with('product:id,name,image,is_available')
            ->where('id', $variantId)
            ->first();

        if (! $variant || ! $variant->product || ! (bool) $variant->product->is_available) {
            return;
        }

        $basePrice = (int) round((float) $variant->price);
        $percent = (int) ($variant->percent ?? 0);
        $discounted = null;
        if ($percent > 0) {
            $discounted = (int) max(0, round($basePrice - ($basePrice * ($percent / 100))));
        } else {
            $after = (int) round((float) ($variant->price_afterdiscount ?? 0));
            if ($after > 0 && $after < $basePrice) {
                $discounted = $after;
            }
        }

        // Load selected addons from DB with quantities from frontend
        $selectedAddons = [];
        if (! empty($addonData)) {
            $addonIds = collect($addonData)->pluck('id')->all();
            $addonsById = Addon::query()->whereIn('id', $addonIds)->get()->keyBy('id');
            foreach ($addonData as $data) {
                $a = $addonsById->get((int) ($data['id'] ?? 0));
                if (! $a) {
                    continue;
                }
                $selectedAddons[] = [
                    'id' => (int) $a->id,
                    'name' => (string) $a->name,
                    'price' => (int) round((float) $a->price),
                    'quantity' => max(1, (int) ($data['quantity'] ?? 1)),
                ];
            }
        }

        $variantDisplay = ItemNameFormatter::displayVariantName((int) $variant->product_id, (string) $variant->name);
        $itemName = (string) $variant->product->name.($variantDisplay !== '' ? ' - '.$variantDisplay : '');
        $cartItems = session('cart_items', []);
        if (! is_array($cartItems)) {
            $cartItems = [];
        }

        $existingItemIndex = collect($cartItems)->search(function ($i) use ($variantId, $variant, $selectedAddons) {
            $match = ((int) ($i['id'] ?? 0)) === (int) $variant->product_id && ((int) ($i['variant_id'] ?? 0)) === (int) $variantId;
            if (! $match) {
                return false;
            }
            // Only merge if addons are the same
            $existingAddonIds = array_column($i['addons'] ?? [], 'id');
            $newAddonIds = array_column($selectedAddons, 'id');
            sort($existingAddonIds);
            sort($newAddonIds);
            return $existingAddonIds === $newAddonIds;
        });

        $payload = [
            'id' => (int) $variant->product_id,
            'name' => $itemName,
            'image' => (string) ($variant->product->image ?? ''),
            'price' => $basePrice,
            'price_afterdiscount' => $discounted,
            'percent' => $percent ?: null,
            'quantity' => $qty,
            'selected' => true,
            'note' => '',
            'variant_id' => (int) $variantId,
            'addons' => $selectedAddons,
        ];

        if ($existingItemIndex !== false) {
            $cartItems[$existingItemIndex]['quantity'] = (int) ($cartItems[$existingItemIndex]['quantity'] ?? 1) + $qty;
        } else {
            $cartItems[] = $payload;
        }

        session(['cart_items' => $cartItems]);
        session(['has_unpaid_transaction' => false]);

        $this->dispatch('cart-updated')->to(CartBadge::class);
    }

    #[Layout('layouts.self-order')]
    public function render(Product $product)
    {
        $term = trim((string) $this->term);
        $searchResult = $term !== '' ? $product->getProductsFiltered(null, $term) : collect();

        return view('livewire.self-order.home', [
            'searchResult' => $searchResult,
        ]);
    }
}
