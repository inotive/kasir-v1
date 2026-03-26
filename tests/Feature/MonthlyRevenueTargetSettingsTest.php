<?php

namespace Tests\Feature;

use App\Livewire\Reports\SalesProfitReportPage;
use App\Livewire\Settings\SettingsPage;
use App\Models\MonthlyRevenueTarget;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MonthlyRevenueTargetSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_can_save_monthly_revenue_target(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('admin');
        $this->actingAs($user);

        Livewire::test(SettingsPage::class)
            ->call('setSection', 'targets')
            ->set('monthlyTargetYear', 2026)
            ->set('monthlyTargetMonth', 2)
            ->set('monthlyTargetAmount', 25000000)
            ->call('saveMonthlyTarget');

        $this->assertDatabaseHas('monthly_revenue_targets', [
            'year' => 2026,
            'month' => 2,
            'amount' => 25000000,
        ]);
    }

    public function test_sales_report_displays_target_analysis_when_target_exists(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('admin');
        $this->actingAs($user);

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-02-15 10:00:00'));

        MonthlyRevenueTarget::query()->create([
            'year' => 2026,
            'month' => 2,
            'amount' => 10000000,
        ]);

        Livewire::test(SalesProfitReportPage::class)
            ->call('setRange', 'today')
            ->assertSee('Target Pendapatan')
            ->assertSee('Pencapaian')
            ->assertDontSee('Target belum diatur');
    }
}
