<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\InventoryMovement;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryValuationReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_valuation_report_renders_total_and_rows(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('admin');

        $ingredient = Ingredient::query()->create([
            'name' => 'Beras',
            'sku' => 'SKU-BRS',
            'unit' => 'kg',
            'cost_price' => 12000,
            'is_active' => true,
        ]);

        InventoryMovement::query()->create([
            'ingredient_id' => $ingredient->id,
            'supplier_id' => null,
            'type' => 'purchase',
            'quantity' => 2,
            'unit_cost' => 10000,
            'reference_type' => 'seed',
            'reference_id' => 1,
            'note' => 'Pembelian awal',
            'happened_at' => CarbonImmutable::now(),
        ]);

        $this->actingAs($user)
            ->get(route('inventory-reports.valuation'))
            ->assertOk()
            ->assertSee('Laporan Persediaan')
            ->assertSee('Total Nilai Persediaan')
            ->assertSee('Beras')
            ->assertSee('SKU-BRS')
            ->assertSee('Rp20.000');
    }
}
