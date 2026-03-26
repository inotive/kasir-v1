<?php

namespace Tests\Feature;

use App\Livewire\Roles\RoleIndex;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RoleGuideTabTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_page_has_guide_tab_and_can_switch_tabs(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('admin');
        $this->actingAs($user);

        Livewire::test(RoleIndex::class)
            ->assertSee('Panduan Peran')
            ->assertSee('Panduan Hak Akses')
            ->call('setTab', 'guide')
            ->assertSet('tab', 'guide')
            ->assertSee('Prinsip desain peran')
            ->call('setTab', 'permissions')
            ->assertSet('tab', 'permissions')
            ->assertSee('Panduan Hak Akses')
            ->call('setTab', 'list')
            ->assertSet('tab', 'list')
            ->assertSee('Cari peran...');
    }
}
