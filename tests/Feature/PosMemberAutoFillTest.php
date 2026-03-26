<?php

namespace Tests\Feature;

use App\Livewire\Pos\PosPage;
use App\Models\Member;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PosMemberAutoFillTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_details_are_auto_filled_when_member_is_selected(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('cashier');
        $this->actingAs($user);

        $member = Member::factory()->create([
            'name' => 'John Doe Member',
            'phone' => '081234567890',
        ]);

        Setting::current()->update(['pos_default_customer_name' => 'Walk-in Customer']);

        $component = Livewire::test(PosPage::class)
            ->assertSet('customerName', 'Walk-in Customer')
            ->set('memberId', $member->id);

        $component->assertSet('customerName', 'John Doe Member')
            ->assertSet('customerPhone', '081234567890');
    }

    public function test_customer_details_are_not_changed_if_member_not_found(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('cashier');
        $this->actingAs($user);

        Setting::current()->update(['pos_default_customer_name' => 'Walk-in Customer']);

        $component = Livewire::test(PosPage::class)
            ->assertSet('customerName', 'Walk-in Customer')
            ->set('memberId', 99999);

        $component->assertSet('customerName', 'Walk-in Customer');
    }
}
