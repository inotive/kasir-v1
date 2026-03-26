<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use App\Models\IngredientUnitConversion;
use App\Models\InventoryMovement;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InitialPurchaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // 1. Buat Supplier Dummy (jika belum ada)
            // Kolom 'contact_name' tidak ada di tabel suppliers, jadi dihapus
            $supplier = Supplier::firstOrCreate(
                ['name' => 'Toko Bahan Kue "Suka Rasa"'],
                [
                    'phone' => '08123456789',
                    'email' => 'sukarasa@example.com',
                    'address' => 'Jl. Pasar Baru No. 123',
                    'is_active' => true,
                ]
            );

            // 2. Data Pembelian Awal
            // Format: [Nama Bahan, Unit Beli, Qty Beli, Harga/Unit Beli]
            $shoppingList = [
                // Kopi
                ['Coffee Beans (Robusta/Arabica Mix)', 'kg', 10, 200000], // 10 kg @ 200rb

                // Susu & Liquid
                ['Fresh Milk (UHT)', 'liter', 24, 18000], // 24 liter (2 karton) @ 18rb
                ['Gula Aren Cair', 'liter', 5, 25000], // 5 liter @ 25rb
                ['Simple Syrup (Gula Putih)', 'liter', 5, 10000], // 5 liter @ 10rb
                ['Mineral Water', 'liter', 20, 5000], // 20 liter (galon) @ 5rb

                // Powders
                ['Chocolate Powder', 'kg', 5, 100000], // 5 kg @ 100rb
                ['Matcha Powder', 'kg', 2, 300000], // 2 kg @ 300rb
                ['Red Velvet Powder', 'kg', 2, 250000], // 2 kg @ 250rb
                ['Taro Powder', 'kg', 2, 250000], // 2 kg @ 250rb

                // Sirup
                ['Lychee Syrup', 'liter', 2, 30000], // 2 liter @ 30rb
                ['Lemon Syrup', 'liter', 2, 25000], // 2 liter @ 25rb
                ['Caramel Syrup', 'liter', 2, 40000], // 2 liter @ 40rb
                ['Hazelnut Syrup', 'liter', 2, 40000], // 2 liter @ 40rb

                // Fresh Food
                ['Roti Tawar (Slice)', 'pack', 5, 12000], // 5 pack @ 12rb (asumsi 1 pack = 10-12 slice, cek konversi)
                ['Keju Cheddar (Parut/Slice)', 'kg', 2, 100000], // 2 kg (block besar) @ 100rb
                ['Mises Coklat', 'kg', 2, 60000], // 2 kg @ 60rb
                ['Margarin', 'kg', 2, 40000], // 2 kg @ 40rb
                ['Frozen French Fries', 'kg', 5, 60000], // 5 kg @ 60rb

                // Others
                ['Tea Bag (Black Tea)', 'pack', 5, 25000], // 5 pack @ 25rb (1 pack = 25-50 bags)
                ['Saus Sambal/Tomat (Sachet/Curah)', 'kg', 5, 30000], // 5 kg @ 30rb
                ['Dimsum Ayam (Frozen)', 'pack', 10, 25000], // 10 pack @ 25rb (1 pack = 10 pcs)
                ['Saus Mentai', 'kg', 1, 80000], // 1 kg @ 80rb
                ['Lychee Fruit (Canned)', 'kaleng', 5, 35000], // 5 kaleng @ 35rb (asumsi 1 kaleng = isi banyak, unit 'kaleng' perlu dicek/dibuat)

                // Packaging
                ['Plastic Cup 16oz', 'pack', 10, 25000], // 10 pack @ 25rb (1 pack = 50 pcs)
                ['Plastic Cup 22oz', 'pack', 5, 35000], // 5 pack @ 35rb (1 pack = 50 pcs)
                ['Straw', 'pack', 10, 5000], // 10 pack @ 5rb
                ['Food Box / Paper Tray', 'pack', 5, 40000], // 5 pack @ 40rb
                ['Garpu Plastik Kecil', 'pack', 5, 5000], // 5 pack @ 5rb
            ];

            // Generate Kode Pembelian
            $code = 'PUR-'.CarbonImmutable::now()->format('ymdHis').'-INIT';
            $purchaseDate = CarbonImmutable::now()->subDays(1); // Kemarin

            // 3. Buat Record Purchase
            $purchase = Purchase::create([
                'code' => $code,
                'supplier_id' => $supplier->id,
                'status' => 'received', // Langsung diterima agar masuk stok
                'purchased_at' => $purchaseDate,
                'received_at' => $purchaseDate,
                'total_cost' => 0, // Akan dihitung ulang
                'note' => 'Pembelian Stok Awal (Initial Seeding)',
            ]);

            $totalCost = 0;

            foreach ($shoppingList as $item) {
                [$name, $inputUnit, $inputQty, $inputPrice] = $item;

                $ingredient = Ingredient::where('name', $name)->first();

                if (! $ingredient) {
                    $this->command->warn("Bahan tidak ditemukan: {$name}");

                    continue;
                }

                // Cek Konversi
                $factor = 1;
                $baseUnit = $ingredient->unit;

                // Khusus untuk case 'kaleng' jika belum ada di seeder konversi
                if ($inputUnit === 'kaleng' && ! IngredientUnitConversion::where('ingredient_id', $ingredient->id)->where('unit', 'kaleng')->exists()) {
                    // Asumsi 1 kaleng leci = 20 pcs buah (contoh)
                    if ($name === 'Lychee Fruit (Canned)') {
                        IngredientUnitConversion::create([
                            'ingredient_id' => $ingredient->id,
                            'unit' => 'kaleng',
                            'factor_to_base' => 20, // 1 kaleng = 20 pcs
                        ]);
                    }
                }

                if ($inputUnit !== $baseUnit) {
                    $conversion = IngredientUnitConversion::where('ingredient_id', $ingredient->id)
                        ->where('unit', $inputUnit)
                        ->first();

                    if ($conversion) {
                        $factor = $conversion->factor_to_base;
                    } else {
                        // Fallback logika sederhana jika konversi belum ada (dari seeder sebelumnya)
                        // Seharusnya seeder IngredientConversionSeeder sudah dijalankan.
                        // Jika masih miss, kita skip atau paksa 1 (tapi kasih warning)
                        $this->command->warn("Konversi unit tidak ditemukan untuk {$name}: {$inputUnit} -> {$baseUnit}. Menggunakan faktor 1.");
                    }
                }

                // Hitung Base Qty & Cost
                $baseQty = $inputQty * $factor;

                // Harga per Base Unit = Harga Beli per Input Unit / Factor
                // Contoh: Beli 1 kg (1000 gr) harga 100.000.
                // Factor = 1000.
                // Harga Base (per gram) = 100.000 / 1000 = 100.
                $baseUnitCost = ($factor > 0) ? ($inputPrice / $factor) : 0;

                $subtotal = $inputQty * $inputPrice;
                $totalCost += $subtotal;

                // 4. Buat Purchase Item
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'ingredient_id' => $ingredient->id,
                    'input_quantity' => $inputQty,
                    'input_unit' => $inputUnit,
                    'quantity_base' => $baseQty,
                    'input_unit_cost' => $inputPrice,
                    'unit_cost_base' => $baseUnitCost,
                    'subtotal_cost' => $subtotal,
                ]);

                // 5. Update Stok & Harga Rata-rata Bahan (Inventory Movement)
                // Karena ini initial, kita bisa langsung set atau hitung rata-rata jika ada stok lama
                // Tapi asumsi DB kosong, jadi harga baru = harga beli ini.

                // Logic Moving Average sederhana:
                // New Cost = ((Old Qty * Old Cost) + (New Qty * New Cost)) / (Old Qty + New Qty)
                // Karena Old Qty 0, maka New Cost = New Cost Base.

                $ingredient->update([
                    'cost_price' => $baseUnitCost,
                ]);

                InventoryMovement::create([
                    'ingredient_id' => $ingredient->id,
                    'supplier_id' => $supplier->id,
                    'type' => 'purchase',
                    'quantity' => $baseQty, // Selalu dalam base unit
                    'input_quantity' => $inputQty,
                    'input_unit' => $inputUnit,
                    'unit_cost' => $baseUnitCost,
                    'input_unit_cost' => $inputPrice,
                    'reference_type' => 'purchases',
                    'reference_id' => $purchase->id,
                    'note' => 'Pembelian Awal '.$purchase->code,
                    'happened_at' => $purchaseDate,
                ]);
            }

            // Update Total Purchase
            $purchase->update(['total_cost' => $totalCost]);
        });
    }
}
