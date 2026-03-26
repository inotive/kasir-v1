<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantRecipe;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CoffeeShopSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // 1. Create Categories
            $categories = [
                'Coffee',
                'Non-Coffee',
                'Snack',
                'Food',
            ];

            $categoryModels = [];
            foreach ($categories as $categoryName) {
                $categoryModels[$categoryName] = Category::firstOrCreate(['name' => $categoryName]);
            }

            // 2. Create Ingredients
            $ingredientsData = [
                // Base Ingredients (Already existed)
                [
                    'name' => 'Coffee Beans (Robusta/Arabica Mix)',
                    'unit' => 'gram',
                    'cost_price' => 200, // Rp 200/gr
                    'sku' => 'ING-COF-001',
                    'reorder_level' => 1000,
                ],
                [
                    'name' => 'Fresh Milk (UHT)',
                    'unit' => 'ml',
                    'cost_price' => 18, // Rp 18/ml
                    'sku' => 'ING-MIL-001',
                    'reorder_level' => 5000,
                ],
                [
                    'name' => 'Gula Aren Cair',
                    'unit' => 'ml',
                    'cost_price' => 25, // Rp 25/ml
                    'sku' => 'ING-AREN-001',
                    'reorder_level' => 2000,
                ],
                [
                    'name' => 'Simple Syrup (Gula Putih)',
                    'unit' => 'ml',
                    'cost_price' => 10, // Rp 10/ml
                    'sku' => 'ING-SYR-001',
                    'reorder_level' => 2000,
                ],
                [
                    'name' => 'Mineral Water',
                    'unit' => 'ml',
                    'cost_price' => 5, // Rp 5/ml
                    'sku' => 'ING-WAT-001',
                    'reorder_level' => 10000,
                ],
                [
                    'name' => 'Chocolate Powder',
                    'unit' => 'gram',
                    'cost_price' => 100, // Rp 100/gr
                    'sku' => 'ING-CHO-001',
                    'reorder_level' => 1000,
                ],
                [
                    'name' => 'Matcha Powder',
                    'unit' => 'gram',
                    'cost_price' => 300, // Rp 300/gr
                    'sku' => 'ING-MAT-001',
                    'reorder_level' => 500,
                ],

                // New Ingredients
                [
                    'name' => 'Red Velvet Powder',
                    'unit' => 'gram',
                    'cost_price' => 250, // Rp 250/gr
                    'sku' => 'ING-RED-001',
                    'reorder_level' => 500,
                ],
                [
                    'name' => 'Taro Powder',
                    'unit' => 'gram',
                    'cost_price' => 250, // Rp 250/gr
                    'sku' => 'ING-TAR-001',
                    'reorder_level' => 500,
                ],
                [
                    'name' => 'Tea Bag (Black Tea)',
                    'unit' => 'pcs',
                    'cost_price' => 500, // Rp 500/bag
                    'sku' => 'ING-TEA-001',
                    'reorder_level' => 100,
                ],
                [
                    'name' => 'Lychee Syrup',
                    'unit' => 'ml',
                    'cost_price' => 30, // Rp 30/ml
                    'sku' => 'ING-SYR-LYC',
                    'reorder_level' => 1000,
                ],
                [
                    'name' => 'Lemon Syrup',
                    'unit' => 'ml',
                    'cost_price' => 25, // Rp 25/ml
                    'sku' => 'ING-SYR-LEM',
                    'reorder_level' => 1000,
                ],
                [
                    'name' => 'Caramel Syrup',
                    'unit' => 'ml',
                    'cost_price' => 40, // Rp 40/ml
                    'sku' => 'ING-SYR-CAR',
                    'reorder_level' => 1000,
                ],
                [
                    'name' => 'Hazelnut Syrup',
                    'unit' => 'ml',
                    'cost_price' => 40, // Rp 40/ml
                    'sku' => 'ING-SYR-HAZ',
                    'reorder_level' => 1000,
                ],
                [
                    'name' => 'Roti Tawar (Slice)',
                    'unit' => 'pcs',
                    'cost_price' => 1000, // Rp 1000/slice
                    'sku' => 'ING-BRE-001',
                    'reorder_level' => 50,
                ],
                [
                    'name' => 'Keju Cheddar (Parut/Slice)',
                    'unit' => 'gram',
                    'cost_price' => 100, // Rp 100/gr
                    'sku' => 'ING-CHE-001',
                    'reorder_level' => 1000,
                ],
                [
                    'name' => 'Mises Coklat',
                    'unit' => 'gram',
                    'cost_price' => 60, // Rp 60/gr
                    'sku' => 'ING-MIS-001',
                    'reorder_level' => 1000,
                ],
                [
                    'name' => 'Margarin',
                    'unit' => 'gram',
                    'cost_price' => 40, // Rp 40/gr
                    'sku' => 'ING-MAR-001',
                    'reorder_level' => 1000,
                ],
                [
                    'name' => 'Frozen French Fries',
                    'unit' => 'gram',
                    'cost_price' => 60, // Rp 60/gr
                    'sku' => 'ING-FRI-001',
                    'reorder_level' => 2000,
                ],
                [
                    'name' => 'Saus Sambal/Tomat (Sachet/Curah)',
                    'unit' => 'gram',
                    'cost_price' => 30, // Rp 30/gr
                    'sku' => 'ING-SAU-001',
                    'reorder_level' => 1000,
                ],
                [
                    'name' => 'Dimsum Ayam (Frozen)',
                    'unit' => 'pcs',
                    'cost_price' => 2500, // Rp 2500/pcs
                    'sku' => 'ING-DIM-001',
                    'reorder_level' => 100,
                ],
                [
                    'name' => 'Saus Mentai',
                    'unit' => 'gram',
                    'cost_price' => 80, // Rp 80/gr
                    'sku' => 'ING-SAU-MEN',
                    'reorder_level' => 500,
                ],
                [
                    'name' => 'Lychee Fruit (Canned)',
                    'unit' => 'pcs',
                    'cost_price' => 1000, // Rp 1000/biji
                    'sku' => 'ING-FRU-LYC',
                    'reorder_level' => 100,
                ],

                // Packaging
                [
                    'name' => 'Plastic Cup 16oz',
                    'unit' => 'pcs',
                    'cost_price' => 500,
                    'sku' => 'PKG-CUP-16',
                    'reorder_level' => 500,
                ],
                [
                    'name' => 'Plastic Cup 22oz',
                    'unit' => 'pcs',
                    'cost_price' => 700,
                    'sku' => 'PKG-CUP-22',
                    'reorder_level' => 500,
                ],
                [
                    'name' => 'Straw',
                    'unit' => 'pcs',
                    'cost_price' => 50,
                    'sku' => 'PKG-STR-001',
                    'reorder_level' => 1000,
                ],
                [
                    'name' => 'Food Box / Paper Tray',
                    'unit' => 'pcs',
                    'cost_price' => 800, // Rp 800/pcs
                    'sku' => 'PKG-BOX-001',
                    'reorder_level' => 500,
                ],
                [
                    'name' => 'Garpu Plastik Kecil',
                    'unit' => 'pcs',
                    'cost_price' => 100, // Rp 100/pcs
                    'sku' => 'PKG-FORK-001',
                    'reorder_level' => 1000,
                ],
            ];

            $ingredientModels = [];
            foreach ($ingredientsData as $data) {
                $ingredientModels[$data['name']] = Ingredient::updateOrCreate(
                    ['name' => $data['name']],
                    $data
                );
            }

            // 3. Create Products, Variants, and Recipes
            $productsData = [
                // === COFFEE ===
                [
                    'name' => 'Kopi Susu Gula Aren',
                    'category' => 'Coffee',
                    'description' => 'Kopi susu kekinian dengan gula aren asli',
                    'image' => 'kopi_susu_gula_aren.jpg',
                    'variants' => [
                        [
                            'name' => 'Regular',
                            'price' => 18000,
                            'recipe' => [
                                'Coffee Beans (Robusta/Arabica Mix)' => 18,
                                'Fresh Milk (UHT)' => 150,
                                'Gula Aren Cair' => 20,
                                'Plastic Cup 16oz' => 1,
                                'Straw' => 1,
                            ],
                        ],
                        [
                            'name' => 'Large',
                            'price' => 22000,
                            'recipe' => [
                                'Coffee Beans (Robusta/Arabica Mix)' => 25,
                                'Fresh Milk (UHT)' => 200,
                                'Gula Aren Cair' => 30,
                                'Plastic Cup 22oz' => 1,
                                'Straw' => 1,
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'Americano',
                    'category' => 'Coffee',
                    'description' => 'Espresso dengan tambahan air mineral',
                    'image' => 'americano.jpg',
                    'variants' => [
                        [
                            'name' => 'Hot',
                            'price' => 15000,
                            'recipe' => [
                                'Coffee Beans (Robusta/Arabica Mix)' => 18,
                                'Mineral Water' => 200,
                                'Plastic Cup 16oz' => 1,
                                'Straw' => 0, // No straw for hot
                            ],
                        ],
                        [
                            'name' => 'Ice',
                            'price' => 15000,
                            'recipe' => [
                                'Coffee Beans (Robusta/Arabica Mix)' => 18,
                                'Mineral Water' => 150,
                                'Plastic Cup 16oz' => 1,
                                'Straw' => 1,
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'Cappuccino',
                    'category' => 'Coffee',
                    'description' => 'Espresso dengan susu steamed dan foam tebal',
                    'image' => 'cappuccino.jpg',
                    'variants' => [
                        [
                            'name' => 'Hot',
                            'price' => 20000,
                            'recipe' => [
                                'Coffee Beans (Robusta/Arabica Mix)' => 18,
                                'Fresh Milk (UHT)' => 180, // More foam implies milk usage
                                'Plastic Cup 16oz' => 1,
                                'Straw' => 0,
                            ],
                        ],
                        [
                            'name' => 'Ice',
                            'price' => 20000,
                            'recipe' => [
                                'Coffee Beans (Robusta/Arabica Mix)' => 18,
                                'Fresh Milk (UHT)' => 150,
                                'Plastic Cup 16oz' => 1,
                                'Straw' => 1,
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'Cafe Latte',
                    'category' => 'Coffee',
                    'description' => 'Espresso dengan susu steamed yang creamy',
                    'image' => 'cafe_latte.jpg',
                    'variants' => [
                        [
                            'name' => 'Hot',
                            'price' => 20000,
                            'recipe' => [
                                'Coffee Beans (Robusta/Arabica Mix)' => 18,
                                'Fresh Milk (UHT)' => 200,
                                'Plastic Cup 16oz' => 1,
                                'Straw' => 0,
                            ],
                        ],
                        [
                            'name' => 'Ice',
                            'price' => 20000,
                            'recipe' => [
                                'Coffee Beans (Robusta/Arabica Mix)' => 18,
                                'Fresh Milk (UHT)' => 180,
                                'Plastic Cup 16oz' => 1,
                                'Straw' => 1,
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'Caramel Macchiato',
                    'category' => 'Coffee',
                    'description' => 'Espresso dengan susu, sirup vanila dan saus karamel',
                    'image' => 'caramel_macchiato.jpg',
                    'variants' => [
                        [
                            'name' => 'Ice Regular',
                            'price' => 25000,
                            'recipe' => [
                                'Coffee Beans (Robusta/Arabica Mix)' => 18,
                                'Fresh Milk (UHT)' => 150,
                                'Caramel Syrup' => 20,
                                'Plastic Cup 16oz' => 1,
                                'Straw' => 1,
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'Hazelnut Latte',
                    'category' => 'Coffee',
                    'description' => 'Cafe latte dengan aroma kacang hazelnut yang gurih',
                    'image' => 'hazelnut_latte.jpg',
                    'variants' => [
                        [
                            'name' => 'Ice Regular',
                            'price' => 24000,
                            'recipe' => [
                                'Coffee Beans (Robusta/Arabica Mix)' => 18,
                                'Fresh Milk (UHT)' => 150,
                                'Hazelnut Syrup' => 20,
                                'Plastic Cup 16oz' => 1,
                                'Straw' => 1,
                            ],
                        ],
                    ],
                ],

                // === NON-COFFEE ===
                [
                    'name' => 'Es Coklat Klasik',
                    'category' => 'Non-Coffee',
                    'description' => 'Minuman coklat dingin yang nyoklat banget',
                    'image' => 'es_coklat.jpg',
                    'variants' => [
                        [
                            'name' => 'Regular',
                            'price' => 20000,
                            'recipe' => [
                                'Chocolate Powder' => 30,
                                'Fresh Milk (UHT)' => 100,
                                'Mineral Water' => 50,
                                'Simple Syrup (Gula Putih)' => 10,
                                'Plastic Cup 16oz' => 1,
                                'Straw' => 1,
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'Matcha Latte',
                    'category' => 'Non-Coffee',
                    'description' => 'Perpaduan matcha premium dengan susu segar',
                    'image' => 'matcha_latte.jpg',
                    'variants' => [
                        [
                            'name' => 'Regular',
                            'price' => 23000,
                            'recipe' => [
                                'Matcha Powder' => 20,
                                'Fresh Milk (UHT)' => 150,
                                'Simple Syrup (Gula Putih)' => 10,
                                'Plastic Cup 16oz' => 1,
                                'Straw' => 1,
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'Red Velvet Latte',
                    'category' => 'Non-Coffee',
                    'description' => 'Minuman red velvet yang creamy dan manis pas',
                    'image' => 'red_velvet.jpg',
                    'variants' => [
                        [
                            'name' => 'Regular',
                            'price' => 23000,
                            'recipe' => [
                                'Red Velvet Powder' => 20,
                                'Fresh Milk (UHT)' => 150,
                                'Simple Syrup (Gula Putih)' => 10,
                                'Plastic Cup 16oz' => 1,
                                'Straw' => 1,
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'Taro Latte',
                    'category' => 'Non-Coffee',
                    'description' => 'Minuman rasa ubi ungu yang unik dan creamy',
                    'image' => 'taro_latte.jpg',
                    'variants' => [
                        [
                            'name' => 'Regular',
                            'price' => 23000,
                            'recipe' => [
                                'Taro Powder' => 20,
                                'Fresh Milk (UHT)' => 150,
                                'Simple Syrup (Gula Putih)' => 10,
                                'Plastic Cup 16oz' => 1,
                                'Straw' => 1,
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'Lychee Tea',
                    'category' => 'Non-Coffee',
                    'description' => 'Teh segar dengan rasa leci dan buah leci asli',
                    'image' => 'lychee_tea.jpg',
                    'variants' => [
                        [
                            'name' => 'Ice',
                            'price' => 18000,
                            'recipe' => [
                                'Tea Bag (Black Tea)' => 1,
                                'Mineral Water' => 200,
                                'Lychee Syrup' => 20,
                                'Lychee Fruit (Canned)' => 2,
                                'Plastic Cup 16oz' => 1,
                                'Straw' => 1,
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'Lemon Tea',
                    'category' => 'Non-Coffee',
                    'description' => 'Teh segar dengan perasan lemon asli',
                    'image' => 'lemon_tea.jpg',
                    'variants' => [
                        [
                            'name' => 'Ice',
                            'price' => 15000,
                            'recipe' => [
                                'Tea Bag (Black Tea)' => 1,
                                'Mineral Water' => 200,
                                'Lemon Syrup' => 20,
                                'Plastic Cup 16oz' => 1,
                                'Straw' => 1,
                            ],
                        ],
                    ],
                ],

                // === SNACK ===
                [
                    'name' => 'Kentang Goreng',
                    'category' => 'Snack',
                    'description' => 'Kentang goreng renyah dengan saus sambal',
                    'image' => 'french_fries.jpg',
                    'variants' => [
                        [
                            'name' => 'Regular',
                            'price' => 15000,
                            'recipe' => [
                                'Frozen French Fries' => 150,
                                'Saus Sambal/Tomat (Sachet/Curah)' => 20,
                                'Food Box / Paper Tray' => 1,
                                'Garpu Plastik Kecil' => 1,
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'Roti Bakar Coklat Keju',
                    'category' => 'Snack',
                    'description' => 'Roti bakar dengan topping coklat dan keju melimpah',
                    'image' => 'roti_bakar.jpg',
                    'variants' => [
                        [
                            'name' => 'Porsi',
                            'price' => 18000,
                            'recipe' => [
                                'Roti Tawar (Slice)' => 2,
                                'Margarin' => 10,
                                'Mises Coklat' => 15,
                                'Keju Cheddar (Parut/Slice)' => 15,
                                'Food Box / Paper Tray' => 1,
                                'Garpu Plastik Kecil' => 1,
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'Dimsum Mentai',
                    'category' => 'Snack',
                    'description' => 'Dimsum ayam dengan saus mentai yang dibakar',
                    'image' => 'dimsum_mentai.jpg',
                    'variants' => [
                        [
                            'name' => 'Isi 4',
                            'price' => 20000,
                            'recipe' => [
                                'Dimsum Ayam (Frozen)' => 4,
                                'Saus Mentai' => 10,
                                'Food Box / Paper Tray' => 1,
                                'Garpu Plastik Kecil' => 1,
                            ],
                        ],
                    ],
                ],
            ];

            foreach ($productsData as $pData) {
                // Find existing product by name to avoid duplicates
                $product = Product::firstOrCreate(
                    ['name' => $pData['name']],
                    [
                        'category_id' => $categoryModels[$pData['category']]->id,
                        'description' => $pData['description'],
                        'image' => $pData['image'],
                        'is_available' => true,
                    ]
                );

                foreach ($pData['variants'] as $vData) {
                    $variant = ProductVariant::updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'name' => $vData['name'],
                        ],
                        [
                            'price' => $vData['price'],
                            // HPP will be updated below
                        ]
                    );

                    // Create Recipe and Calculate HPP
                    $hpp = 0;
                    foreach ($vData['recipe'] as $ingName => $qty) {
                        if (! isset($ingredientModels[$ingName])) {
                            // Skip if ingredient not found (should not happen if data is consistent)
                            continue;
                        }
                        $ingredient = $ingredientModels[$ingName];

                        ProductVariantRecipe::updateOrCreate(
                            [
                                'product_variant_id' => $variant->id,
                                'ingredient_id' => $ingredient->id,
                            ],
                            [
                                'quantity' => $qty,
                            ]
                        );

                        $hpp += $ingredient->cost_price * $qty;
                    }

                    // Update Variant HPP
                    $variant->update(['hpp' => $hpp]);
                }
            }
        });
    }
}
