<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperatingExpensesPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_operating_expenses_page_renders(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->get(route('reports.operating-expenses'))
            ->assertOk()
            ->assertSee('Beban Operasional');
    }
}
