<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\InventoryMovement;
use App\Models\Purchase;
use App\Models\StockOpname;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InventoryUiUxPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_form_page_renders_summary_and_subtotal_column(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('admin');

        Ingredient::query()->create([
            'name' => 'Gula',
            'unit' => 'kg',
            'is_active' => true,
        ]);

        Supplier::query()->create([
            'name' => 'Supplier A',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('purchases.create'))
            ->assertOk()
            ->assertSee('Estimasi total')
            ->assertSee('Subtotal')
            ->assertSee('Receive');
    }

    public function test_stock_opname_form_page_renders_refresh_and_variance_column(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('admin');

        Ingredient::query()->create([
            'name' => 'Tepung',
            'unit' => 'kg',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('stock-opnames.create'))
            ->assertOk()
            ->assertSee('Refresh Stok Sistem')
            ->assertSee('Selisih')
            ->assertSee('Posting');
    }

    public function test_stock_card_requires_ingredient_selection(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->get(route('inventory-reports.stock-card'))
            ->assertOk()
            ->assertSee('Pilih bahan untuk melihat kartu stok.')
            ->assertDontSee('Waktu');
    }

    public function test_stock_card_shows_unit_and_human_friendly_reference(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('admin');

        $ingredient = Ingredient::query()->create([
            'name' => 'Susu',
            'unit' => 'ltr',
            'is_active' => true,
        ]);

        $purchase = Purchase::query()->create([
            'code' => 'PUR-TEST-001',
            'status' => 'received',
            'purchased_at' => now()->toDateString(),
        ]);

        InventoryMovement::query()->create([
            'ingredient_id' => $ingredient->id,
            'supplier_id' => null,
            'type' => 'purchase',
            'quantity' => 1.5,
            'reference_type' => 'purchases',
            'reference_id' => $purchase->id,
            'note' => 'Pembelian '.$purchase->code,
            'happened_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('inventory-reports.stock-card', ['ingredientId' => $ingredient->id]))
            ->assertOk()
            ->assertSee('Saldo awal')
            ->assertSee('ltr')
            ->assertSee('Pembelian '.$purchase->code);
    }

    public function test_inventory_movements_page_renders_filters_and_reference_column(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('admin');

        Ingredient::query()->create([
            'name' => 'Beras',
            'unit' => 'kg',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('inventory-movements.index'))
            ->assertOk()
            ->assertSee('Cari catatan/ref')
            ->assertSee('Ref')
            ->assertSee('Tambah Pergerakan');
    }

    public function test_inventory_view_role_cannot_manage_master_data(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $role = Role::firstOrCreate(['name' => 'inventory_viewer']);
        $role->givePermissionTo([
            'dashboard.access',
            'inventory.ingredients.view',
            'inventory.suppliers.view',
            'inventory.movements.view',
            'inventory.reports.view',
        ]);

        $user = User::factory()->create();
        $user->assignRole('inventory_viewer');

        $ingredient = Ingredient::query()->create([
            'name' => 'Garam',
            'unit' => 'kg',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('ingredients.index'))
            ->assertOk()
            ->assertDontSee('Tambah Bahan')
            ->assertDontSee('Edit')
            ->assertDontSee('Hapus');

        $this->actingAs($user)
            ->get(route('suppliers.index'))
            ->assertOk()
            ->assertDontSee('Tambah Supplier')
            ->assertDontSee('Edit')
            ->assertDontSee('Hapus');

        $this->actingAs($user)
            ->get(route('inventory-movements.index'))
            ->assertOk()
            ->assertDontSee('Tambah Pergerakan');

        $this->actingAs($user)
            ->get(route('ingredients.conversions', ['ingredient' => $ingredient->id]))
            ->assertForbidden();
    }

    public function test_purchase_viewer_can_open_purchase_but_cannot_edit_or_receive(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $role = Role::firstOrCreate(['name' => 'purchase_viewer']);
        $role->givePermissionTo([
            'dashboard.access',
            'inventory.purchases.view',
        ]);

        $user = User::factory()->create();
        $user->assignRole('purchase_viewer');

        $purchase = Purchase::query()->create([
            'code' => 'PUR-VIEW-001',
            'status' => 'draft',
            'purchased_at' => now()->toDateString(),
        ]);

        $this->actingAs($user)
            ->get(route('purchases.index'))
            ->assertOk()
            ->assertDontSee('Buat Pembelian');

        $this->actingAs($user)
            ->get(route('purchases.edit', ['purchase' => $purchase->id]))
            ->assertOk()
            ->assertDontSee('Simpan Draft')
            ->assertDontSee('Receive')
            ->assertDontSee('Batalkan');
    }

    public function test_purchase_creator_can_open_create_page_and_save_draft_but_cannot_receive(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $role = Role::firstOrCreate(['name' => 'purchase_creator']);
        $role->givePermissionTo([
            'dashboard.access',
            'inventory.purchases.view',
            'inventory.purchases.create',
        ]);

        $user = User::factory()->create();
        $user->assignRole('purchase_creator');

        $this->actingAs($user)
            ->get(route('purchases.index'))
            ->assertOk()
            ->assertSee('Buat Pembelian');

        $this->actingAs($user)
            ->get(route('purchases.create'))
            ->assertOk()
            ->assertSee('Simpan Draft')
            ->assertSee('Tambah Item')
            ->assertDontSee('wire:click=\"openReceiveConfirm\"');
    }

    public function test_purchase_receiver_can_receive_draft_purchase(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $role = Role::firstOrCreate(['name' => 'purchase_receiver']);
        $role->givePermissionTo([
            'dashboard.access',
            'inventory.purchases.view',
            'inventory.purchases.receive',
        ]);

        $user = User::factory()->create();
        $user->assignRole('purchase_receiver');

        $ingredient = Ingredient::query()->create([
            'name' => 'Santan',
            'unit' => 'ltr',
            'is_active' => true,
        ]);

        $purchase = Purchase::query()->create([
            'code' => 'PUR-RCV-001',
            'status' => 'draft',
            'purchased_at' => now()->toDateString(),
        ]);

        $purchase->items()->create([
            'ingredient_id' => $ingredient->id,
            'input_quantity' => 1,
            'input_unit' => 'ltr',
            'quantity_base' => 1,
            'input_unit_cost' => 10000,
            'unit_cost_base' => 10000,
            'subtotal_cost' => 10000,
        ]);

        $this->actingAs($user)
            ->get(route('purchases.edit', ['purchase' => $purchase->id]))
            ->assertOk()
            ->assertSee('Receive');
    }

    public function test_opname_creator_can_open_create_page_and_save_draft_but_cannot_post(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $role = Role::firstOrCreate(['name' => 'opname_creator']);
        $role->givePermissionTo([
            'dashboard.access',
            'inventory.opnames.view',
            'inventory.opnames.create',
        ]);

        $user = User::factory()->create();
        $user->assignRole('opname_creator');

        $this->actingAs($user)
            ->get(route('stock-opnames.index'))
            ->assertOk()
            ->assertSee('Buat Opname');

        $this->actingAs($user)
            ->get(route('stock-opnames.create'))
            ->assertOk()
            ->assertSee('Simpan Draft')
            ->assertSee('Tambah Item')
            ->assertDontSee('wire:click=\"openPostConfirm\"');
    }

    public function test_opname_viewer_can_open_opname_but_cannot_edit_or_post(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $role = Role::firstOrCreate(['name' => 'opname_viewer']);
        $role->givePermissionTo([
            'dashboard.access',
            'inventory.opnames.view',
        ]);

        $user = User::factory()->create();
        $user->assignRole('opname_viewer');

        $opname = StockOpname::query()->create([
            'code' => 'OPN-VIEW-001',
            'status' => 'draft',
            'counted_at' => now()->toDateString(),
        ]);

        $this->actingAs($user)
            ->get(route('stock-opnames.index'))
            ->assertOk()
            ->assertDontSee('Buat Opname');

        $this->actingAs($user)
            ->get(route('stock-opnames.edit', ['stockOpname' => $opname->id]))
            ->assertOk()
            ->assertDontSee('Simpan Draft')
            ->assertDontSee('Posting')
            ->assertDontSee('Batalkan');
    }

    public function test_movement_creator_sees_add_button_but_no_delete(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $role = Role::firstOrCreate(['name' => 'movement_creator']);
        $role->givePermissionTo([
            'dashboard.access',
            'inventory.movements.view',
            'inventory.movements.create',
        ]);

        $user = User::factory()->create();
        $user->assignRole('movement_creator');

        Ingredient::query()->create([
            'name' => 'Minyak',
            'unit' => 'ltr',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('inventory-movements.index'))
            ->assertOk()
            ->assertSee('Tambah Pergerakan')
            ->assertDontSee('Hapus');
    }
}
