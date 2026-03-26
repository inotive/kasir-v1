<?php

use App\Livewire\Inventory\PurchaseFormPage;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductVariantRecipe;
use App\Models\Purchase;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

it('updates ingredient cost_price using moving average when purchase is received', function () {
    $this->seed(RolePermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('admin');

    $ingredient = Ingredient::query()->create([
        'name' => 'Gula',
        'unit' => 'kg',
        'cost_price' => 1000,
        'is_active' => true,
    ]);

    $category = Category::query()->create(['name' => 'Food']);
    $product = Product::query()->create([
        'name' => 'Teh Manis',
        'description' => 'Desc',
        'image' => '',
        'is_available' => true,
        'is_promo' => false,
        'is_favorite' => false,
        'is_package' => false,
        'category_id' => $category->id,
        'printer_source_id' => null,
    ]);
    $variant = $product->variants()->create([
        'name' => 'Reg',
        'price' => 10000,
        'hpp' => 0,
    ]);
    ProductVariantRecipe::query()->create([
        'product_variant_id' => $variant->id,
        'ingredient_id' => $ingredient->id,
        'quantity' => 2,
    ]);

    InventoryMovement::query()->create([
        'ingredient_id' => $ingredient->id,
        'supplier_id' => null,
        'type' => 'purchase',
        'quantity' => 10,
        'unit_cost' => 1000,
        'reference_type' => 'seed',
        'reference_id' => 1,
        'note' => 'Stok awal',
        'happened_at' => now(),
    ]);

    $purchase = Purchase::query()->create([
        'code' => 'PUR-WAC-001',
        'status' => 'draft',
        'purchased_at' => now()->toDateString(),
    ]);

    $purchase->items()->create([
        'ingredient_id' => $ingredient->id,
        'input_quantity' => 10,
        'input_unit' => 'kg',
        'quantity_base' => 10,
        'input_unit_cost' => 2000,
        'unit_cost_base' => 2000,
        'subtotal_cost' => 20000,
    ]);

    Livewire::actingAs($user)
        ->test(PurchaseFormPage::class, ['purchase' => $purchase])
        ->call('receive')
        ->assertHasNoErrors();

    $ingredient->refresh();
    expect((float) $ingredient->cost_price)->toBe(1500.0);

    $variant->refresh();
    expect((float) $variant->hpp)->toBe(3000.0);
});
