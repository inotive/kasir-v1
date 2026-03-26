<?php

namespace Tests\Feature;

use App\Livewire\Settings\SettingsPage;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PointSettingsValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_point_settings_rejects_out_of_range_values_without_breaking_other_settings(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('admin');
        $this->actingAs($user);

        $setting = Setting::current();
        $setting->pos_default_customer_name = 'Customer Default';
        $setting->save();

        Livewire::test(SettingsPage::class)
            ->call('setSection', 'points')
            ->set('point_earning_rate', 10005466546)
            ->set('point_redemption_value', 1)
            ->set('min_redemption_points', 0)
            ->call('savePointSettings')
            ->assertHasErrors(['point_earning_rate']);

        $fresh = Setting::query()->firstOrFail();
        $this->assertSame('Customer Default', (string) $fresh->pos_default_customer_name);
    }

    public function test_point_settings_can_save_valid_values(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('admin');
        $this->actingAs($user);

        $setting = Setting::current();
        $setting->pos_default_customer_name = 'Customer Default';
        $setting->save();

        Livewire::test(SettingsPage::class)
            ->call('setSection', 'points')
            ->set('point_earning_rate', 10000)
            ->set('point_redemption_value', 1)
            ->set('min_redemption_points', 10)
            ->call('savePointSettings')
            ->assertHasNoErrors();

        $fresh = Setting::query()->firstOrFail();
        $this->assertSame('Customer Default', (string) $fresh->pos_default_customer_name);
        $this->assertSame('10000.0000', (string) $fresh->point_earning_rate);
        $this->assertSame('1.00', (string) $fresh->point_redemption_value);
        $this->assertSame(10, (int) $fresh->min_redemption_points);
    }
}
