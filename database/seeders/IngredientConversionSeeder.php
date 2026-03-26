<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use App\Models\IngredientUnitConversion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IngredientConversionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Definisikan mapping unit umum ke unit lain
        // Format: 'base_unit' => [ ['target_unit', factor], ... ]
        $commonConversions = [
            'gram' => [
                ['unit' => 'kg', 'factor' => 1000],      // 1 kg = 1000 gram
                ['unit' => 'ons', 'factor' => 100],      // 1 ons = 100 gram
                ['unit' => 'sdt', 'factor' => 5],        // 1 sdt ~ 5 gram (estimasi umum)
                ['unit' => 'sdm', 'factor' => 15],       // 1 sdm ~ 15 gram (estimasi umum)
                ['unit' => 'mg', 'factor' => 0.001],     // 1 mg = 0.001 gram
            ],
            'ml' => [
                ['unit' => 'liter', 'factor' => 1000],   // 1 liter = 1000 ml
                ['unit' => 'sdt', 'factor' => 5],        // 1 sdt = 5 ml
                ['unit' => 'sdm', 'factor' => 15],       // 1 sdm = 15 ml
                ['unit' => 'cup', 'factor' => 240],      // 1 cup ~ 240 ml
                ['unit' => 'oz', 'factor' => 29.57],     // 1 fl oz ~ 29.57 ml
            ],
            'pcs' => [
                ['unit' => 'lusin', 'factor' => 12],     // 1 lusin = 12 pcs
                ['unit' => 'kodi', 'factor' => 20],      // 1 kodi = 20 pcs
                ['unit' => 'gross', 'factor' => 144],    // 1 gross = 144 pcs
                ['unit' => 'pack', 'factor' => 10],      // Asumsi 1 pack = 10 pcs (bisa beda per item, ini default)
            ],
        ];

        // 2. Ambil semua ingredients
        $ingredients = Ingredient::all();

        DB::transaction(function () use ($ingredients, $commonConversions) {
            foreach ($ingredients as $ingredient) {
                $baseUnit = strtolower($ingredient->unit);

                // Cek apakah base unit ada di mapping umum
                if (isset($commonConversions[$baseUnit])) {
                    foreach ($commonConversions[$baseUnit] as $conversion) {
                        // Cek apakah konversi sudah ada biar tidak duplikat
                        $exists = IngredientUnitConversion::where('ingredient_id', $ingredient->id)
                            ->where('unit', $conversion['unit'])
                            ->exists();

                        if (! $exists) {
                            // Perhatikan: di tabel ingredient_unit_conversions, kolom 'factor_to_base'
                            // artinya 1 Unit Konversi = Sekian Base Unit.
                            // Contoh: 1 kg = 1000 gram. Jadi factor_to_base untuk 'kg' adalah 1000.
                            // Data di $commonConversions sudah diset sebagai factor_to_base.

                            IngredientUnitConversion::create([
                                'ingredient_id' => $ingredient->id,
                                'unit' => $conversion['unit'],
                                'factor_to_base' => $conversion['factor'],
                            ]);
                        }
                    }
                }

                // Tambahan khusus berdasarkan nama bahan (jika perlu)
                // Contoh: 'Fresh Milk' (ml) bisa punya konversi ke 'galon' jika relevan
                if (str_contains(strtolower($ingredient->name), 'milk') && $baseUnit === 'ml') {
                    IngredientUnitConversion::firstOrCreate(
                        ['ingredient_id' => $ingredient->id, 'unit' => 'karton'],
                        ['factor_to_base' => 10000] // Misal 1 karton = 10 liter = 10000 ml
                    );
                }

                // Contoh: 'Coffee Beans' (gram)
                if (str_contains(strtolower($ingredient->name), 'coffee') && $baseUnit === 'gram') {
                    IngredientUnitConversion::firstOrCreate(
                        ['ingredient_id' => $ingredient->id, 'unit' => 'sack'],
                        ['factor_to_base' => 60000] // Misal 1 karung = 60 kg = 60000 gram
                    );
                }
            }
        });
    }
}
