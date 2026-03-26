<?php

namespace Tests\Feature;

use App\Livewire\Pos\PosPage;
use App\Models\Category;
use App\Models\Member;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PosSelfOrderPointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_pos_checkout_preserves_self_order_point_discount_on_pending_transaction(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('cashier');
        $this->actingAs($user);

        $category = Category::query()->create([
            'name' => 'Makanan',
        ]);

        $product = Product::query()->create([
            'name' => 'Nasi Goreng',
            'description' => '-',
            'image' => '-',
            'category_id' => $category->id,
            'printer_source_id' => null,
        ]);

        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => 'Regular',
            'price' => 10000,
            'price_afterdiscount' => null,
            'percent' => null,
            'hpp' => 0,
        ]);

        $member = Member::query()->create([
            'name' => 'Member A',
            'email' => 'member@example.com',
            'phone' => '628111',
            'points' => 0,
            'email_verified_at' => now(),
            'verification_token' => null,
        ]);

        $trx = Transaction::query()->create([
            'code' => 'TRX-SO-POINT',
            'member_id' => $member->id,
            'channel' => 'self_order',
            'name' => 'Member A',
            'phone' => '628111',
            'email' => 'member@example.com',
            'order_type' => 'take_away',
            'dining_table_id' => null,
            'subtotal' => 10000,
            'voucher_discount_amount' => 0,
            'manual_discount_amount' => 0,
            'discount_total_amount' => 1000,
            'points_redeemed' => 10,
            'point_discount_amount' => 1000,
            'tax_percentage' => 0,
            'tax_amount' => 0,
            'rounding_amount' => 0,
            'total' => 9000,
            'checkout_link' => '-',
            'payment_method' => 'cash',
            'payment_status' => 'pending',
            'external_id' => 'ext-TRX-SO-POINT',
            'is_midtrans_processed' => false,
        ]);

        TransactionItem::query()->create([
            'transaction_id' => $trx->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'price' => 10000,
            'hpp_unit' => null,
            'hpp_total' => 0,
            'subtotal' => 10000,
            'note' => null,
        ]);

        Livewire::test(PosPage::class)
            ->call('loadPending', $trx->id)
            ->set('paymentMethod', 'cash')
            ->set('cashReceived', '20000')
            ->call('checkout');

        $trx->refresh();
        $this->assertSame(10, (int) $trx->points_redeemed);
        $this->assertSame(1000, (int) $trx->point_discount_amount);
        $this->assertSame('paid', (string) $trx->payment_status);
    }
}
