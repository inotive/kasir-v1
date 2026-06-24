<?php

namespace Database\Seeders;

use App\Models\Addon;
use App\Models\AddonCategory;
use App\Models\Product;
use Illuminate\Database\Seeder;

class AddonSeeder extends Seeder
{
    /**
     * Seed add-on categories, add-ons, and attach them to existing products.
     *
     * Idempotent: re-running tidak menggandakan data.
     */
    public function run(): void
    {
        // ─── Kategori + add-on ───────────────────────────────
        $structure = [
            'Topping' => [
                ['name' => 'Boba', 'price' => 5000],
                ['name' => 'Keju', 'price' => 4000],
                ['name' => 'Cincau', 'price' => 3000],
                ['name' => 'Oreo Crumble', 'price' => 5000],
            ],
            'Tingkat Gula' => [
                ['name' => 'Normal Sugar', 'price' => 0],
                ['name' => 'Less Sugar', 'price' => 0],
                ['name' => 'No Sugar', 'price' => 0],
            ],
            'Ukuran' => [
                ['name' => 'Regular', 'price' => 0],
                ['name' => 'Large', 'price' => 7000],
            ],
            'Extra' => [
                ['name' => 'Extra Shot Espresso', 'price' => 8000],
                ['name' => 'Extra Ice', 'price' => 0],
                ['name' => 'Hot', 'price' => 0],
            ],
        ];

        $createdAddons = collect();

        foreach ($structure as $categoryName => $addons) {
            $category = AddonCategory::query()->firstOrCreate(['name' => $categoryName]);

            foreach ($addons as $addon) {
                $model = Addon::query()->firstOrCreate(
                    [
                        'addon_category_id' => $category->id,
                        'name' => $addon['name'],
                    ],
                    [
                        'price' => $addon['price'],
                        'is_available' => true,
                    ],
                );

                $createdAddons->push($model);
            }
        }

        // ─── Attach add-on ke produk yang ada ────────────────
        $products = Product::query()->latest('id')->take(8)->get();

        if ($products->isEmpty()) {
            $this->command?->warn('AddonSeeder: tidak ada produk. Jalankan CoffeeShopSeeder dulu untuk attach add-on ke produk.');

            return;
        }

        $addonIds = $createdAddons->pluck('id')->all();

        foreach ($products as $product) {
            // syncWithoutDetaching supaya idempotent & tidak menghapus relasi lain
            $product->addons()->syncWithoutDetaching($addonIds);
        }

        $this->command?->info(sprintf(
            'AddonSeeder: %d add-on dalam %d kategori, di-attach ke %d produk.',
            $createdAddons->count(),
            count($structure),
            $products->count(),
        ));
    }
}
