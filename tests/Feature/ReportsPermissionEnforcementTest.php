<?php

use App\Livewire\Reports\ManualDiscountReportPage;
use App\Livewire\Reports\MemberPerformanceReportPage;
use App\Livewire\Reports\OperatingExpensesPage;
use App\Livewire\Reports\SalesProfitReportPage;
use App\Models\Category;
use App\Models\Member;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('reports pages deny access without report permissions', function () {
    $this->seed(RolePermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('waiter');

    Livewire::actingAs($user)->test(SalesProfitReportPage::class)->assertStatus(403);
    Livewire::actingAs($user)->test(MemberPerformanceReportPage::class)->assertStatus(403);
    Livewire::actingAs($user)->test(ManualDiscountReportPage::class)->assertStatus(403);
    Livewire::actingAs($user)->test(OperatingExpensesPage::class)->assertStatus(403);
});

test('member performance report hides PII without pii permission', function () {
    $this->seed(RolePermissionSeeder::class);

    $guard = (string) config('auth.defaults.guard', 'web');
    $role = Role::findOrCreate('reports_performance_only', $guard);
    $role->syncPermissions(['reports.performance']);

    $user = User::factory()->create();
    $user->assignRole('reports_performance_only');

    $member = Member::query()->create([
        'name' => 'Member PII',
        'email' => 'pii@example.com',
        'phone' => '081234567890',
        'points' => 0,
        'member_region_id' => null,
        'email_verified_at' => now(),
        'verification_token' => null,
    ]);

    $category = Category::query()->create(['name' => 'Food']);
    $product = Product::query()->create([
        'name' => 'Produk A',
        'description' => 'Desc',
        'image' => '',
        'is_available' => true,
        'is_promo' => false,
        'is_favorite' => false,
        'is_package' => false,
        'category_id' => $category->id,
        'printer_source_id' => null,
    ]);
    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'name' => 'Reg',
        'price' => 10000,
        'hpp' => 1000,
    ]);

    $trx = Transaction::query()->create([
        'code' => 'TRX-MEMBER-001',
        'external_id' => 'EXT-MEMBER-001',
        'name' => 'Member',
        'checkout_link' => '',
        'subtotal' => 10000,
        'total' => 10000,
        'payment_method' => 'cash',
        'payment_status' => 'paid',
        'member_id' => $member->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    TransactionItem::query()->create([
        'transaction_id' => $trx->id,
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'quantity' => 1,
        'price' => 10000,
        'subtotal' => 10000,
    ]);

    Livewire::actingAs($user)
        ->test(MemberPerformanceReportPage::class)
        ->assertSee('Member PII')
        ->assertDontSee('081234567890')
        ->assertDontSee('pii@example.com');
});

test('member performance report shows PII with members.pii.view', function () {
    $this->seed(RolePermissionSeeder::class);

    $guard = (string) config('auth.defaults.guard', 'web');
    $role = Role::findOrCreate('reports_performance_pii', $guard);
    $role->syncPermissions(['reports.performance', 'members.pii.view']);

    $user = User::factory()->create();
    $user->assignRole('reports_performance_pii');

    $member = Member::query()->create([
        'name' => 'Member PII 2',
        'email' => 'pii2@example.com',
        'phone' => '081111222333',
        'points' => 0,
        'member_region_id' => null,
        'email_verified_at' => now(),
        'verification_token' => null,
    ]);

    $category = Category::query()->create(['name' => 'Food']);
    $product = Product::query()->create([
        'name' => 'Produk B',
        'description' => 'Desc',
        'image' => '',
        'is_available' => true,
        'is_promo' => false,
        'is_favorite' => false,
        'is_package' => false,
        'category_id' => $category->id,
        'printer_source_id' => null,
    ]);
    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'name' => 'Reg',
        'price' => 12000,
        'hpp' => 1000,
    ]);

    $trx = Transaction::query()->create([
        'code' => 'TRX-MEMBER-002',
        'external_id' => 'EXT-MEMBER-002',
        'name' => 'Member',
        'checkout_link' => '',
        'subtotal' => 12000,
        'total' => 12000,
        'payment_method' => 'cash',
        'payment_status' => 'paid',
        'member_id' => $member->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    TransactionItem::query()->create([
        'transaction_id' => $trx->id,
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'quantity' => 1,
        'price' => 12000,
        'subtotal' => 12000,
    ]);

    Livewire::actingAs($user)
        ->test(MemberPerformanceReportPage::class)
        ->assertSee('Member PII 2')
        ->assertSee('081111222333');
});
