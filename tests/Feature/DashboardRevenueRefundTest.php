<?php

namespace Tests\Feature;

use App\Livewire\DashboardPage;
use App\Models\Category;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardRevenueRefundTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_revenue_matches_sales_report_net_omzet_logic(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('admin');
        $this->actingAs($user);

        $category = Category::query()->create(['name' => 'Kategori']);
        $product = Product::query()->create([
            'name' => 'Produk A',
            'description' => 'Desc',
            'image' => '/images/product/product-01.jpg',
            'is_available' => true,
            'is_promo' => false,
            'is_favorite' => false,
            'category_id' => $category->id,
            'printer_source_id' => null,
        ]);

        $t = now()->startOfDay()->addHours(10);

        Transaction::unguarded(function () use ($t) {
            Transaction::query()->create([
                'code' => 'TRX-R-PAID',
                'external_id' => 'EXT-R-PAID',
                'channel' => 'pos',
                'name' => 'Customer',
                'subtotal' => 5000,
                'total' => 5000,
                'checkout_link' => '',
                'payment_method' => 'cash',
                'payment_status' => 'paid',
                'refunded_amount' => 0,
                'created_at' => $t,
                'updated_at' => $t,
            ]);

            Transaction::query()->create([
                'code' => 'TRX-R-PARTIAL',
                'external_id' => 'EXT-R-PARTIAL',
                'channel' => 'pos',
                'name' => 'Customer',
                'subtotal' => 10000,
                'total' => 10000,
                'checkout_link' => '',
                'payment_method' => 'cash',
                'payment_status' => 'partial_refund',
                'refunded_amount' => 2000,
                'created_at' => $t->copy()->addMinutes(10),
                'updated_at' => $t->copy()->addMinutes(10),
            ]);

            Transaction::query()->create([
                'code' => 'TRX-R-REFUNDED',
                'external_id' => 'EXT-R-REFUNDED',
                'channel' => 'pos',
                'name' => 'Customer',
                'subtotal' => 10000,
                'total' => 10000,
                'checkout_link' => '',
                'payment_method' => 'cash',
                'payment_status' => 'refunded',
                'refunded_amount' => 10000,
                'created_at' => $t->copy()->addMinutes(20),
                'updated_at' => $t->copy()->addMinutes(20),
            ]);
        });

        $paid = Transaction::query()->where('code', 'TRX-R-PAID')->firstOrFail();
        $partial = Transaction::query()->where('code', 'TRX-R-PARTIAL')->firstOrFail();
        $refunded = Transaction::query()->where('code', 'TRX-R-REFUNDED')->firstOrFail();

        TransactionItem::query()->create([
            'transaction_id' => $paid->id,
            'product_id' => $product->id,
            'product_variant_id' => null,
            'quantity' => 1,
            'price' => 5000,
            'hpp_unit' => null,
            'hpp_total' => 0,
            'subtotal' => 5000,
            'voucher_discount_amount' => 0,
            'manual_discount_amount' => 0,
            'note' => null,
        ]);

        TransactionItem::query()->create([
            'transaction_id' => $paid->id,
            'product_id' => $product->id,
            'product_variant_id' => null,
            'quantity' => 1,
            'price' => 10000,
            'hpp_unit' => null,
            'hpp_total' => 0,
            'subtotal' => 10000,
            'voucher_discount_amount' => 1000,
            'manual_discount_amount' => 500,
            'note' => null,
        ]);

        TransactionItem::query()->create([
            'transaction_id' => $partial->id,
            'product_id' => $product->id,
            'product_variant_id' => null,
            'quantity' => 1,
            'price' => 10000,
            'hpp_unit' => null,
            'hpp_total' => 0,
            'subtotal' => 10000,
            'voucher_discount_amount' => 0,
            'manual_discount_amount' => 0,
            'note' => null,
        ]);

        TransactionItem::query()->create([
            'transaction_id' => $refunded->id,
            'product_id' => $product->id,
            'product_variant_id' => null,
            'quantity' => 1,
            'price' => 10000,
            'hpp_unit' => null,
            'hpp_total' => 0,
            'subtotal' => 10000,
            'voucher_discount_amount' => 0,
            'manual_discount_amount' => 0,
            'note' => null,
        ]);

        Livewire::test(DashboardPage::class)
            ->assertSet('todayRevenueAmount', 21500)
            ->assertSet('monthlyRevenueAmount', 21500);
    }
}
