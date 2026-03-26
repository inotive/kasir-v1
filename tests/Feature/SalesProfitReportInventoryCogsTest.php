<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantRecipe;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesProfitReportInventoryCogsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_profit_report_shows_inventory_cogs_metrics(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('admin');

        $category = Category::query()->create(['name' => 'Food']);

        $ingredient = Ingredient::query()->create([
            'name' => 'Garam',
            'unit' => 'gr',
            'cost_price' => 500,
            'is_active' => true,
        ]);

        InventoryMovement::query()->create([
            'ingredient_id' => $ingredient->id,
            'supplier_id' => null,
            'type' => 'opening_balance',
            'quantity' => 100,
            'unit_cost' => 500,
            'reference_type' => null,
            'reference_id' => null,
            'note' => null,
            'happened_at' => now(),
        ]);

        $product = Product::query()->create([
            'name' => 'Sup',
            'description' => 'Desc',
            'image' => '',
            'is_available' => true,
            'is_promo' => false,
            'is_favorite' => false,
            'is_package' => false,
            'category_id' => $category->id,
            'printer_source_id' => null,
        ]);

        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => 'Reg',
            'price' => 10000,
            'hpp' => 0,
        ]);

        ProductVariantRecipe::query()->create([
            'product_variant_id' => $variant->id,
            'ingredient_id' => $ingredient->id,
            'quantity' => 2,
        ]);

        $today = CarbonImmutable::now();

        $transaction = Transaction::query()->create([
            'code' => 'TRX-COGS-001',
            'external_id' => 'EXT-COGS-001',
            'name' => 'Walk-in',
            'checkout_link' => '',
            'subtotal' => 10000,
            'total' => 10000,
            'payment_method' => 'cash',
            'payment_status' => 'pending',
            'created_at' => $today,
            'updated_at' => $today,
        ]);

        TransactionItem::query()->create([
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'price' => 10000,
            'subtotal' => 10000,
        ]);

        $transaction->update(['payment_status' => 'paid']);

        $this->actingAs($user)
            ->get(route('reports.sales-profit'))
            ->assertOk()
            ->assertSee('COGS Penjualan')
            ->assertSee('Loss Stok (Net)')
            ->assertSee('Total COGS + Loss')
            ->assertSee('Laba Kotor (Setelah Loss)')
            ->assertSee('Rp1.000')
            ->assertSee('Rp9.000');
    }
}
