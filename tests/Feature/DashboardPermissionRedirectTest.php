<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardPermissionRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_redirects_to_pos_when_dashboard_denied_and_pos_allowed(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $role = Role::create(['name' => 'pos_only']);
        $role->givePermissionTo(Permission::findByName('pos.access'));

        $user = User::factory()->create();
        $user->assignRole('pos_only');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('pos.index'));
    }

    public function test_redirects_to_first_allowed_menu_route_when_dashboard_denied(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $role = Role::create(['name' => 'transactions_only']);
        $role->givePermissionTo(Permission::findByName('transactions.view'));

        $user = User::factory()->create();
        $user->assignRole('transactions_only');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('transactions.index'));
    }

    public function test_returns_403_when_no_route_is_allowed(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $role = Role::create(['name' => 'no_access']);
        $user = User::factory()->create();
        $user->assignRole('no_access');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertStatus(403);
    }
}
