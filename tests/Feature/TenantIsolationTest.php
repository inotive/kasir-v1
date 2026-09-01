<?php

namespace Tests\Feature;

use App\Livewire\Inventory\InventoryValuationPage;
use App\Livewire\Members\MembersPage;
use App\Livewire\Reports\SalesProfitReportPage;
use App\Models\Ingredient;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Regression tests for the multi-tenant retrofit: the same data (email,
 * phone, codes, report figures) must be usable by two different tenants
 * without colliding or leaking into each other's view.
 *
 * NOTE: Spatie\Multitenancy\Multitenancy::start() only auto-resolves the
 * current tenant from the request host when the app is not running in
 * console — PHPUnit always runs under the CLI SAPI, so that auto-detection
 * never fires during tests. These tests instead call Tenant::makeCurrent()
 * directly, which is the same static state TenantScope/BelongsToTenant
 * read from either way, so everything downstream of tenant resolution
 * (which is everything this test actually exercises) is genuinely covered.
 */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function makeTenantWithOwner(string $slug): array
    {
        $tenant = Tenant::query()->create([
            'name' => $slug,
            'slug' => $slug,
            'business_name' => $slug,
            'domain' => $slug.'.test',
            'is_active' => true,
        ]);

        $owner = User::query()->create([
            'name' => $slug.' Owner',
            'email' => $slug.'-owner@test.com',
            'password' => bcrypt('password'),
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'is_active' => true,
        ]);
        $owner->assignRole('owner');

        return [$tenant, $owner];
    }

    protected function tearDown(): void
    {
        Tenant::forgetCurrent();
        parent::tearDown();
    }

    public function test_member_email_and_phone_can_be_reused_across_tenants_but_not_within_one(): void
    {
        $this->seed(RolePermissionSeeder::class);

        [$tenant1, $owner1] = $this->makeTenantWithOwner('tenant01');
        [$tenant2, $owner2] = $this->makeTenantWithOwner('tenant02');

        // Tenant 1 registers a member with a given email/phone.
        $tenant1->makeCurrent();
        $this->actingAs($owner1);

        Livewire::test(MembersPage::class)
            ->set('name', 'Budi')
            ->set('email', 'shared@test.com')
            ->set('phone', '081200000001')
            ->call('createMember')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('members', [
            'tenant_id' => $tenant1->id,
            'email' => 'shared@test.com',
        ]);

        // Tenant 2 must be able to register a DIFFERENT member with the
        // exact same email/phone — this is the bug that was fixed.
        $tenant2->makeCurrent();
        $this->actingAs($owner2);

        Livewire::test(MembersPage::class)
            ->set('name', 'Siti')
            ->set('email', 'shared@test.com')
            ->set('phone', '081200000001')
            ->call('createMember')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('members', [
            'tenant_id' => $tenant2->id,
            'email' => 'shared@test.com',
        ]);

        // But a SECOND member with the same email inside tenant 2 itself
        // must still be rejected.
        Livewire::test(MembersPage::class)
            ->set('name', 'Siti Duplikat')
            ->set('email', 'shared@test.com')
            ->set('phone', '081200000002')
            ->call('createMember')
            ->assertHasErrors(['email']);

        $this->assertSame(
            1,
            \App\Models\Member::withoutGlobalScopes()
                ->where('tenant_id', $tenant2->id)
                ->where('email', 'shared@test.com')
                ->count()
        );
    }

    public function test_inventory_valuation_report_does_not_leak_other_tenants_ingredients(): void
    {
        $this->seed(RolePermissionSeeder::class);

        [$tenant1, $owner1] = $this->makeTenantWithOwner('tenant01');
        [$tenant2, $owner2] = $this->makeTenantWithOwner('tenant02');

        $tenant1->makeCurrent();
        Ingredient::query()->create([
            'name' => 'Tenant1-Only-Ingredient',
            'sku' => 'T1-ING',
            'unit' => 'kg',
            'cost_price' => 1000,
            'is_active' => true,
            'reorder_level' => 0,
        ]);

        $tenant2->makeCurrent();
        Ingredient::query()->create([
            'name' => 'Tenant2-Only-Ingredient',
            'sku' => 'T2-ING',
            'unit' => 'kg',
            'cost_price' => 2000,
            'is_active' => true,
            'reorder_level' => 0,
        ]);

        // Viewing the report as tenant 2 must show only tenant 2's ingredient.
        $this->actingAs($owner2);

        Livewire::test(InventoryValuationPage::class)
            ->assertSee('Tenant2-Only-Ingredient')
            ->assertDontSee('Tenant1-Only-Ingredient');

        // And as tenant 1, only tenant 1's.
        $tenant1->makeCurrent();
        $this->actingAs($owner1);

        Livewire::test(InventoryValuationPage::class)
            ->assertSee('Tenant1-Only-Ingredient')
            ->assertDontSee('Tenant2-Only-Ingredient');
    }

    public function test_sales_profit_report_transaction_count_is_scoped_to_current_tenant(): void
    {
        $this->seed(RolePermissionSeeder::class);

        [$tenant1, $owner1] = $this->makeTenantWithOwner('tenant01');
        [$tenant2, $owner2] = $this->makeTenantWithOwner('tenant02');

        $now = now();

        $tenant1->makeCurrent();
        \App\Models\Transaction::unguarded(function () use ($now): void {
            for ($i = 0; $i < 3; $i++) {
                \App\Models\Transaction::query()->create([
                    'code' => 'T1-TRX-'.$i,
                    'external_id' => 'T1-EXT-'.$i,
                    'channel' => 'pos',
                    'name' => 'Customer',
                    'subtotal' => 5000,
                    'total' => 5000,
                    'checkout_link' => '',
                    'payment_method' => 'cash',
                    'payment_status' => 'paid',
                    'refunded_amount' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });

        $tenant2->makeCurrent();
        \App\Models\Transaction::unguarded(function () use ($now): void {
            \App\Models\Transaction::query()->create([
                'code' => 'T2-TRX-0',
                'external_id' => 'T2-EXT-0',
                'channel' => 'pos',
                'name' => 'Customer',
                'subtotal' => 7000,
                'total' => 7000,
                'checkout_link' => '',
                'payment_method' => 'cash',
                'payment_status' => 'paid',
                'refunded_amount' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });

        // Tenant 2 only made 1 transaction; the report's raw-SQL tx_count
        // must not include tenant 1's 3 transactions.
        $this->actingAs($owner2);

        $component = Livewire::test(SalesProfitReportPage::class)
            ->set('fromDate', $now->copy()->subDay()->format('Y-m-d'))
            ->set('toDate', $now->copy()->addDay()->format('Y-m-d'));

        $metrics = $component->viewData('metrics');
        $this->assertSame(1, $metrics['current']['txCount']);
    }

    public function test_subdomain_root_redirects_to_admin_instead_of_landing_page(): void
    {
        $this->seed(RolePermissionSeeder::class);

        [$tenant1] = $this->makeTenantWithOwner('tenant01');

        $tenant1->makeCurrent();

        $this->get('/')->assertRedirect(route('dashboard'));
    }
}
