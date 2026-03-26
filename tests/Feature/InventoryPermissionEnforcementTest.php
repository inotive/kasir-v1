<?php

use App\Livewire\Inventory\IngredientsPage;
use App\Livewire\Inventory\LowStockPage;
use App\Livewire\Inventory\PurchasesPage;
use App\Livewire\Inventory\StockCardPage;
use App\Livewire\Inventory\StockOpnamesPage;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('inventory pages deny access for user without inventory permissions', function () {
    $this->seed(RolePermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('waiter');

    Livewire::actingAs($user)->test(LowStockPage::class)->assertStatus(403);
    Livewire::actingAs($user)->test(StockCardPage::class)->assertStatus(403);
    Livewire::actingAs($user)->test(PurchasesPage::class)->assertStatus(403);
    Livewire::actingAs($user)->test(StockOpnamesPage::class)->assertStatus(403);
});

test('inventory pages allow access for inventory role', function () {
    $this->seed(RolePermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('inventory');

    Livewire::actingAs($user)->test(LowStockPage::class)->assertStatus(200);
    Livewire::actingAs($user)->test(StockCardPage::class)->assertStatus(200);
    Livewire::actingAs($user)->test(PurchasesPage::class)->assertStatus(200);
    Livewire::actingAs($user)->test(StockOpnamesPage::class)->assertStatus(200);
});

test('inventory viewer cannot see manage buttons in ingredients page', function () {
    $this->seed(RolePermissionSeeder::class);

    $guard = (string) config('auth.defaults.guard', 'web');
    $role = Role::findOrCreate('ingredients_viewer', $guard);
    $role->syncPermissions(['inventory.ingredients.view']);

    $user = User::factory()->create();
    $user->assignRole('ingredients_viewer');

    Livewire::actingAs($user)
        ->test(IngredientsPage::class)
        ->assertStatus(200)
        ->assertDontSee('Tambah Bahan');
});
