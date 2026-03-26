<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\InventoryMovement;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockCardValuationUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_card_shows_starting_value_and_unit_cost_columns(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('admin');

        $ingredient = Ingredient::query()->create([
            'name' => 'Bawang',
            'unit' => 'kg',
            'cost_price' => 1000,
            'is_active' => true,
        ]);

        InventoryMovement::query()->create([
            'ingredient_id' => $ingredient->id,
            'supplier_id' => null,
            'type' => 'purchase',
            'quantity' => 2,
            'unit_cost' => 1000,
            'reference_type' => 'seed',
            'reference_id' => 1,
            'note' => 'Pembelian awal',
            'happened_at' => CarbonImmutable::now()->subDay(),
        ]);

        $this->actingAs($user)
            ->get(route('inventory-reports.stock-card', ['ingredientId' => $ingredient->id]))
            ->assertOk()
            ->assertSee('Nilai saldo awal')
            ->assertSee('Rp2.000')
            ->assertSee('HPP/Unit')
            ->assertSee('Nilai Saldo');
    }
}
