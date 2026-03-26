<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use App\Services\Printing\PosPrintPayloadService;
use App\Support\Products\ItemNameFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SingleVariantDisplayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        ItemNameFormatter::resetCache();
    }

    public function test_single_variant_is_hidden(): void
    {
        $category = Category::query()->create(['name' => 'Minuman']);

        $product = Product::query()->create([
            'name' => 'Es Teh',
            'description' => '-',
            'image' => '-',
            'is_available' => true,
            'is_promo' => false,
            'is_favorite' => false,
            'is_package' => false,
            'package_type' => null,
            'category_id' => (int) $category->id,
            'printer_source_id' => null,
        ]);

        ProductVariant::query()->create([
            'product_id' => (int) $product->id,
            'name' => 'Regular',
            'price' => 10000,
            'price_afterdiscount' => null,
            'percent' => null,
            'hpp' => 0,
        ]);

        expect(ItemNameFormatter::displayVariantName((int) $product->id, 'Regular'))->toBe('');
    }

    public function test_multi_variant_is_shown(): void
    {
        $category = Category::query()->create(['name' => 'Minuman']);

        $product = Product::query()->create([
            'name' => 'Kopi',
            'description' => '-',
            'image' => '-',
            'is_available' => true,
            'is_promo' => false,
            'is_favorite' => false,
            'is_package' => false,
            'package_type' => null,
            'category_id' => (int) $category->id,
            'printer_source_id' => null,
        ]);

        ProductVariant::query()->create([
            'product_id' => (int) $product->id,
            'name' => 'Hot',
            'price' => 15000,
            'price_afterdiscount' => null,
            'percent' => null,
            'hpp' => 0,
        ]);

        ProductVariant::query()->create([
            'product_id' => (int) $product->id,
            'name' => 'Ice',
            'price' => 17000,
            'price_afterdiscount' => null,
            'percent' => null,
            'hpp' => 0,
        ]);

        expect(ItemNameFormatter::displayVariantName((int) $product->id, 'Ice'))->toBe('Ice');
    }

    public function test_print_payload_hides_single_variant_name(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $category = Category::query()->create(['name' => 'Minuman']);

        $product = Product::query()->create([
            'name' => 'Air Mineral',
            'description' => '-',
            'image' => '-',
            'is_available' => true,
            'is_promo' => false,
            'is_favorite' => false,
            'is_package' => false,
            'package_type' => null,
            'category_id' => (int) $category->id,
            'printer_source_id' => null,
        ]);

        $variant = ProductVariant::query()->create([
            'product_id' => (int) $product->id,
            'name' => 'Default',
            'price' => 5000,
            'price_afterdiscount' => null,
            'percent' => null,
            'hpp' => 0,
        ]);

        $transaction = Transaction::query()->create([
            'code' => 'A1B2C3D4',
            'channel' => 'pos',
            'name' => 'Walk-in',
            'order_type' => 'take_away',
            'subtotal' => 5000,
            'total' => 5000,
            'checkout_link' => '',
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'external_id' => 'ext_1',
            'order_status' => 'new',
            'voucher_discount_amount' => 0,
            'manual_discount_amount' => 0,
            'discount_total_amount' => 0,
            'point_discount_amount' => 0,
            'points_redeemed' => 0,
            'points_earned' => 0,
            'payment_fee_amount' => 0,
            'rounding_amount' => 0,
            'refunded_amount' => 0,
        ]);

        TransactionItem::query()->create([
            'transaction_id' => (int) $transaction->id,
            'parent_transaction_item_id' => null,
            'product_id' => (int) $product->id,
            'product_variant_id' => (int) $variant->id,
            'quantity' => 1,
            'price' => 5000,
            'hpp_unit' => null,
            'hpp_total' => 0,
            'subtotal' => 5000,
            'voucher_discount_amount' => 0,
            'manual_discount_amount' => 0,
            'note' => null,
        ]);

        $payload = app(PosPrintPayloadService::class)->build((int) $transaction->id, $user->name);

        expect($payload)->not()->toBeNull();
        expect((string) ($payload['items'][0]['variant_name'] ?? ''))->toBe('');
    }
}
