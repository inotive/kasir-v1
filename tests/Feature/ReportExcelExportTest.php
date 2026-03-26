<?php

use App\Models\Ingredient;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authorized user can export sales & profit excel', function () {
    $this->seed(RolePermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('admin');

    $res = $this->actingAs($user)->get(route('reports.sales-profit.excel', [
        'from' => now()->subDays(7)->format('Y-m-d'),
        'to' => now()->format('Y-m-d'),
    ]));

    $res->assertOk();
    $res->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    $res->assertHeader('Content-Disposition');
});

test('unauthorized user cannot export sales & profit excel', function () {
    $this->seed(RolePermissionSeeder::class);

    $user = User::factory()->create();

    $res = $this->actingAs($user)->get(route('reports.sales-profit.excel', [
        'from' => now()->subDays(7)->format('Y-m-d'),
        'to' => now()->format('Y-m-d'),
    ]));

    $res->assertForbidden();
});

test('authorized user can export inventory valuation excel', function () {
    $this->seed(RolePermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('admin');

    Ingredient::query()->create([
        'name' => 'Beras',
        'unit' => 'kg',
        'cost_price' => 12000,
        'is_active' => true,
        'reorder_level' => 0,
    ]);

    $res = $this->actingAs($user)->get(route('inventory-reports.valuation.excel', [
        'includeZero' => true,
        'search' => '',
    ]));

    $res->assertOk();
    $res->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    $res->assertHeader('Content-Disposition');
});
