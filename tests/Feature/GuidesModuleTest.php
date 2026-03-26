<?php

namespace Tests\Feature;

use App\Livewire\Guides\GuidesIndexPage;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GuidesModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guides_index_page_is_accessible_for_logged_in_user(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->get(route('guides.index'))
            ->assertOk()
            ->assertSee('Buku Panduan');
    }

    public function test_guides_search_finds_refund_article(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('admin');
        $this->actingAs($user);

        Livewire::test(GuidesIndexPage::class)
            ->set('q', 'refund')
            ->assertSee('Refund & Void');
    }

    public function test_can_open_article_page(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->get(route('guides.show', ['slug' => 'mulai-cepat-hari-pertama']))
            ->assertOk()
            ->assertSee('Mulai Cepat');
    }
}
