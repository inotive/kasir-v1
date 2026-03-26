<?php

namespace Tests\Feature;

use App\Livewire\Users\UsersPage;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UsersManagerPinUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_user_form_only_shows_manager_pin_input_for_roles_that_need_approval_pin(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('admin');
        $this->actingAs($user);

        Livewire::test(UsersPage::class)
            ->call('openCreateModal')
            ->assertSee('PIN akan diminta jika peran yang dipilih memiliki akses penyetujuan pembatalan/refund.')
            ->assertDontSee('Wajib untuk peran yang dapat menyetujui pembatalan/refund.')
            ->set('role', 'manager')
            ->assertSee('PIN Manager')
            ->assertSee('Wajib untuk peran yang dapat menyetujui pembatalan/refund.')
            ->set('role', 'cashier')
            ->assertSee('PIN akan diminta jika peran yang dipilih memiliki akses penyetujuan pembatalan/refund.')
            ->assertDontSee('Wajib untuk peran yang dapat menyetujui pembatalan/refund.');
    }
}
