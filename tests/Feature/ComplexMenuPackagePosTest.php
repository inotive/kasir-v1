<?php

namespace Tests\Feature;

use App\Livewire\Pos\PosPage;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\InventoryMovement;
use App\Models\PrinterSource;
use App\Models\Product;
use App\Models\ProductComplexPackageItem;
use App\Models\ProductVariant;
use App\Models\ProductVariantRecipe;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Inventory\InventoryService;
use App\Services\Printing\PosPrintPayloadService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ComplexMenuPackagePosTest extends TestCase
{
    use RefreshDatabase;

    public function test_pos_can_configure_complex_package_and_persist_parent_child_items(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('cashier');
        $this->actingAs($user);

        $dapur = PrinterSource::query()->create([
            'name' => 'Dapur',
            'type' => 'dapur',
        ]);

        $category = Category::query()->create(['name' => 'Food']);

        $ingredient = Ingredient::query()->create([
            'name' => 'Beras',
            'sku' => null,
            'unit' => 'pcs',
            'cost_price' => 1000,
            'reorder_level' => 0,
            'is_active' => true,
        ]);

        InventoryMovement::query()->create([
            'ingredient_id' => $ingredient->id,
            'supplier_id' => null,
            'type' => 'opening_balance',
            'quantity' => 100,
            'input_quantity' => null,
            'input_unit' => null,
            'unit_cost' => null,
            'input_unit_cost' => null,
            'reference_type' => null,
            'reference_id' => null,
            'note' => null,
            'happened_at' => now(),
        ]);

        $componentA = Product::query()->create([
            'name' => 'Ayam',
            'description' => '-',
            'image' => 'test.png',
            'is_available' => true,
            'is_promo' => false,
            'is_favorite' => false,
            'is_package' => false,
            'category_id' => $category->id,
            'printer_source_id' => $dapur->id,
        ]);

        $componentAVariant = ProductVariant::query()->create([
            'product_id' => $componentA->id,
            'name' => 'Bakar',
            'price' => 15000,
            'price_afterdiscount' => null,
            'percent' => null,
            'hpp' => 0,
        ]);

        $componentAVariant2 = ProductVariant::query()->create([
            'product_id' => $componentA->id,
            'name' => 'Goreng',
            'price' => 15000,
            'price_afterdiscount' => null,
            'percent' => null,
            'hpp' => 0,
        ]);

        ProductVariantRecipe::query()->create([
            'product_variant_id' => $componentAVariant->id,
            'ingredient_id' => $ingredient->id,
            'quantity' => 1,
        ]);

        ProductVariantRecipe::query()->create([
            'product_variant_id' => $componentAVariant2->id,
            'ingredient_id' => $ingredient->id,
            'quantity' => 1,
        ]);

        $componentB = Product::query()->create([
            'name' => 'Nasi',
            'description' => '-',
            'image' => 'test.png',
            'is_available' => true,
            'is_promo' => false,
            'is_favorite' => false,
            'is_package' => false,
            'category_id' => $category->id,
            'printer_source_id' => $dapur->id,
        ]);

        $componentBVariant = ProductVariant::query()->create([
            'product_id' => $componentB->id,
            'name' => 'Putih',
            'price' => 5000,
            'price_afterdiscount' => null,
            'percent' => null,
            'hpp' => 0,
        ]);

        ProductVariantRecipe::query()->create([
            'product_variant_id' => $componentBVariant->id,
            'ingredient_id' => $ingredient->id,
            'quantity' => 1,
        ]);

        $package = Product::query()->create([
            'name' => 'Paket Kompleks',
            'description' => '-',
            'image' => 'test.png',
            'is_available' => true,
            'is_promo' => false,
            'is_favorite' => false,
            'is_package' => true,
            'package_type' => 'complex',
            'category_id' => $category->id,
            'printer_source_id' => null,
        ]);

        $packageVariant = ProductVariant::query()->create([
            'product_id' => $package->id,
            'name' => 'Normal',
            'price' => 40000,
            'price_afterdiscount' => null,
            'percent' => null,
            'hpp' => 0,
        ]);

        ProductComplexPackageItem::query()->create([
            'package_product_id' => $package->id,
            'component_product_id' => $componentA->id,
            'quantity' => 3,
            'is_splitable' => true,
            'sort_order' => 0,
        ]);

        ProductComplexPackageItem::query()->create([
            'package_product_id' => $package->id,
            'component_product_id' => $componentB->id,
            'quantity' => 1,
            'is_splitable' => false,
            'sort_order' => 1,
        ]);

        Livewire::test(PosPage::class)
            ->call('addVariantToCart', $packageVariant->id)
            ->assertSet('complexPackageModalOpen', true)
            ->set('complexPackageComponents.0.allocations', [
                ['key' => 'a1', 'quantity' => 1, 'variant_id' => $componentAVariant->id, 'note' => 'pedas'],
                ['key' => 'a2', 'quantity' => 1, 'variant_id' => $componentAVariant->id, 'note' => 'pedas'],
                ['key' => 'a3', 'quantity' => 1, 'variant_id' => $componentAVariant2->id, 'note' => null],
            ])
            ->set('complexPackageComponents.1.allocations', [
                ['key' => 'b1', 'quantity' => 1, 'variant_id' => $componentBVariant->id, 'note' => 'Nasi lebih'],
            ])
            ->call('confirmComplexPackageToCart')
            ->assertSet('complexPackageModalOpen', false)
            ->assertCount('cartItems', 1)
            ->set('cartItems.0.quantity', 2)
            ->set('customerName', 'Walk-in')
            ->call('saveAsPending');

        $trx = Transaction::query()->with('transactionItems')->firstOrFail();

        $parents = $trx->transactionItems->whereNull('parent_transaction_item_id')->values();
        $children = $trx->transactionItems->whereNotNull('parent_transaction_item_id')->values();

        $this->assertCount(1, $parents);
        $this->assertCount(3, $children);

        $parent = $parents->first();
        $this->assertSame($package->id, (int) $parent->product_id);
        $this->assertSame($packageVariant->id, (int) $parent->product_variant_id);
        $this->assertSame(2, (int) $parent->quantity);

        $childA = $children->firstWhere('product_id', $componentA->id);
        $this->assertNotNull($childA);
        $this->assertSame($componentAVariant->id, (int) $childA->product_variant_id);
        $this->assertSame(4, (int) $childA->quantity);
        $this->assertSame(0.0, (float) $childA->price);
        $this->assertSame('pedas', $childA->note);

        $childA2 = $children->firstWhere('product_variant_id', $componentAVariant2->id);
        $this->assertNotNull($childA2);
        $this->assertSame($componentA->id, (int) $childA2->product_id);
        $this->assertSame(2, (int) $childA2->quantity);
        $this->assertSame(0.0, (float) $childA2->price);
        $this->assertNull($childA2->note);

        $childB = $children->firstWhere('product_id', $componentB->id);
        $this->assertNotNull($childB);
        $this->assertSame($componentBVariant->id, (int) $childB->product_variant_id);
        $this->assertSame(2, (int) $childB->quantity);
        $this->assertSame(0.0, (float) $childB->price);
        $this->assertSame('Nasi lebih', $childB->note);

        app(InventoryService::class)->applyTransaction($trx->fresh(['transactionItems']));

        $consumed = (float) InventoryMovement::query()
            ->where('reference_type', 'transactions')
            ->where('reference_id', $trx->id)
            ->where('type', 'sale_consumption')
            ->where('ingredient_id', $ingredient->id)
            ->sum('quantity');
        $this->assertSame(-8.0, $consumed);

        $trx = Transaction::query()->with('transactionItems')->whereKey($trx->id)->firstOrFail();
        $parent = $trx->transactionItems->firstWhere('parent_transaction_item_id', null);
        $this->assertSame(8000.0, (float) $parent->hpp_total);
        $this->assertSame(4000.0, (float) $parent->hpp_unit);

        $payload = app(PosPrintPayloadService::class)->build((int) $trx->id, 'Kasir');
        $this->assertNotNull($payload);
        $this->assertCount(1, (array) ($payload['items'] ?? []));

        $bySource = (array) ($payload['items_by_source'] ?? []);
        $this->assertArrayHasKey((string) $dapur->id, $bySource);
        $this->assertCount(3, (array) $bySource[(string) $dapur->id]);

        Livewire::test(PosPage::class)
            ->call('loadPending', (int) $trx->id)
            ->call('editComplexPackageInCart', 0)
            ->assertSet('complexPackageModalOpen', true)
            ->set('complexPackageComponents.0.allocations', [
                ['key' => 'a1', 'quantity' => 2, 'variant_id' => $componentAVariant2->id, 'note' => null],
                ['key' => 'a2', 'quantity' => 1, 'variant_id' => $componentAVariant->id, 'note' => 'pedas'],
            ])
            ->set('complexPackageComponents.1.allocations', [
                ['key' => 'b1', 'quantity' => 1, 'variant_id' => $componentBVariant->id, 'note' => null],
            ])
            ->call('confirmComplexPackageToCart')
            ->set('customerName', 'Walk-in')
            ->call('saveAsPending');

        $trx = Transaction::query()->with('transactionItems')->whereKey($trx->id)->firstOrFail();
        $children = $trx->transactionItems->whereNotNull('parent_transaction_item_id')->values();

        $childA = $children->firstWhere('product_variant_id', $componentAVariant->id);
        $this->assertNotNull($childA);
        $this->assertSame(2, (int) $childA->quantity);

        $childA2 = $children->firstWhere('product_variant_id', $componentAVariant2->id);
        $this->assertNotNull($childA2);
        $this->assertSame(4, (int) $childA2->quantity);
    }
}
