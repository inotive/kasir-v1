<?php

use App\Mail\ReceiptMail;
use App\Models\Category;
use App\Models\DiningTable;
use App\Models\Ingredient;
use App\Models\InventoryMovement;
use App\Models\Member;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantRecipe;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\TransactionEvent;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

function seedSelfOrderCart(float $ingredientOnHand): array
{
    $category = Category::query()->create([
        'name' => 'Food',
    ]);

    $table = DiningTable::query()->create([
        'table_number' => 'A1',
        'image' => null,
        'qr_value' => null,
    ]);

    $product = Product::query()->create([
        'name' => 'Nasi Goreng',
        'description' => 'Test',
        'image' => 'test.png',
        'is_available' => true,
        'is_promo' => false,
        'is_favorite' => false,
        'category_id' => $category->id,
        'printer_source_id' => null,
    ]);

    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'name' => 'Normal',
        'price' => 10000,
        'price_afterdiscount' => null,
        'percent' => null,
        'hpp' => 0,
    ]);

    $ingredient = Ingredient::query()->create([
        'name' => 'Beras',
        'sku' => null,
        'unit' => 'pcs',
        'cost_price' => 1000,
        'reorder_level' => 0,
        'is_active' => true,
    ]);

    InventoryMovement::query()->create([
        'ingredient_id' => $ingredient->id,
        'supplier_id' => null,
        'type' => 'opening_balance',
        'quantity' => $ingredientOnHand,
        'input_quantity' => null,
        'input_unit' => null,
        'unit_cost' => null,
        'input_unit_cost' => null,
        'reference_type' => null,
        'reference_id' => null,
        'note' => null,
        'happened_at' => now(),
    ]);

    ProductVariantRecipe::query()->create([
        'product_variant_id' => $variant->id,
        'ingredient_id' => $ingredient->id,
        'quantity' => 1,
    ]);

    $paymentToken = Str::random(32);

    $cart = [
        [
            'id' => (int) $product->id,
            'variant_id' => (int) $variant->id,
            'quantity' => 1,
            'selected' => true,
            'note' => '',
        ],
    ];

    return [
        'table' => $table,
        'product' => $product,
        'variant' => $variant,
        'ingredient' => $ingredient,
        'cart' => $cart,
        'paymentToken' => $paymentToken,
    ];
}

it('rejects invalid self-order payment tokens', function () {
    $seed = seedSelfOrderCart(10);
    $selfOrderToken = Str::random(40);

    $response = $this
        ->withSession([
            'dining_table_id' => $seed['table']->id,
            'self_order_token' => $selfOrderToken,
            'customer_ready' => true,
            'name' => 'Guest',
            'phone' => '628123',
            'cart_items' => $seed['cart'],
            'payment_token' => $seed['paymentToken'],
        ])
        ->post(route('self-order.payment.pay'), [
            'action' => 'pay',
            'method' => 'cashier',
            'token' => Str::random(32),
        ]);

    $response->assertRedirect(route('self-order.payment.failure'));
    expect(Transaction::query()->count())->toBe(0);
});

it('rejects invalid webhook signatures without requiring CSRF', function () {
    $response = $this->postJson(route('self-order.payment.webhook'), [
        'order_id' => (string) Str::uuid(),
        'status_code' => '200',
        'gross_amount' => '10000.00',
        'signature_key' => 'invalid',
        'transaction_status' => 'settlement',
    ]);

    $response->assertStatus(401);
});

it('queues receipt email when webhook marks a transaction as paid', function () {
    Mail::fake();
    config(['midtrans.server_key' => 'test-server-key']);

    $table = DiningTable::query()->create([
        'table_number' => 'A1',
        'image' => null,
        'qr_value' => null,
    ]);

    $orderId = (string) Str::uuid();
    $trx = Transaction::query()->create([
        'code' => Transaction::generateUniqueCode(),
        'member_id' => null,
        'channel' => 'self_order',
        'name' => 'Guest',
        'phone' => '628123',
        'email' => 'guest@example.test',
        'order_type' => 'dine_in',
        'dining_table_id' => $table->id,
        'subtotal' => 10000,
        'tax_percentage' => 0,
        'tax_amount' => 0,
        'rounding_amount' => 0,
        'total' => 10000,
        'checkout_link' => '-',
        'payment_method' => 'qris_midtrans',
        'payment_status' => 'pending',
        'order_status' => 'new',
        'external_id' => $orderId,
        'self_order_token' => (string) Str::random(40),
    ]);

    $statusCode = '200';
    $grossAmount = '10000.00';
    $signature = hash('sha512', $orderId.$statusCode.$grossAmount.'test-server-key');

    $response = $this->postJson(route('self-order.payment.webhook'), [
        'order_id' => $orderId,
        'status_code' => $statusCode,
        'gross_amount' => $grossAmount,
        'signature_key' => $signature,
        'transaction_status' => 'settlement',
    ]);

    $response
        ->assertOk()
        ->assertJson([
            'code' => 200,
            'status' => 'paid',
        ]);

    $trx->refresh();
    expect((string) $trx->payment_status)->toBe('paid');
    expect($trx->receipt_emailed_at)->not->toBeNull();

    Mail::assertQueued(ReceiptMail::class, function (ReceiptMail $mail) use ($trx) {
        return (int) $mail->transaction->id === (int) $trx->id;
    });
});

it('does not queue receipt email when transaction has no email', function () {
    Mail::fake();
    config(['midtrans.server_key' => 'test-server-key']);

    $orderId = (string) Str::uuid();
    $trx = Transaction::query()->create([
        'code' => Transaction::generateUniqueCode(),
        'member_id' => null,
        'channel' => 'self_order',
        'name' => 'Guest',
        'phone' => '628123',
        'email' => null,
        'order_type' => 'dine_in',
        'dining_table_id' => null,
        'subtotal' => 10000,
        'tax_percentage' => 0,
        'tax_amount' => 0,
        'rounding_amount' => 0,
        'total' => 10000,
        'checkout_link' => '-',
        'payment_method' => 'qris_midtrans',
        'payment_status' => 'pending',
        'order_status' => 'new',
        'external_id' => $orderId,
        'self_order_token' => (string) Str::random(40),
    ]);

    $statusCode = '200';
    $grossAmount = '10000.00';
    $signature = hash('sha512', $orderId.$statusCode.$grossAmount.'test-server-key');

    $this->postJson(route('self-order.payment.webhook'), [
        'order_id' => $orderId,
        'status_code' => $statusCode,
        'gross_amount' => $grossAmount,
        'signature_key' => $signature,
        'transaction_status' => 'settlement',
    ])->assertOk();

    $trx->refresh();
    expect((string) $trx->payment_status)->toBe('paid');
    Mail::assertNotQueued(ReceiptMail::class);
});

it('allows cashier checkout even when ingredient stock is insufficient', function () {
    $seed = seedSelfOrderCart(0);
    $csrf = Str::random(40);

    $response = $this
        ->withSession([
            '_token' => $csrf,
            'dining_table_id' => $seed['table']->id,
            'self_order_token' => Str::random(40),
            'customer_ready' => true,
            'name' => 'Guest',
            'phone' => '628123',
            'cart_items' => $seed['cart'],
            'payment_token' => $seed['paymentToken'],
        ])
        ->post(route('self-order.payment.pay'), [
            '_token' => $csrf,
            'action' => 'pay',
            'method' => 'cashier',
            'token' => $seed['paymentToken'],
        ]);

    $response->assertRedirect();
    $response->assertSessionMissing('error');
    expect(Transaction::query()->count())->toBe(1);
    expect(TransactionEvent::query()->where('action', 'self_order.created')->count())->toBe(1);
});

it('applies point redemption on self-order cashier checkout when requested', function () {
    $seed = seedSelfOrderCart(10);
    $csrf = Str::random(40);

    Setting::current()->update([
        'tax_rate' => 0,
        'rounding_base' => 0,
        'discount_applies_before_tax' => true,
        'point_redemption_value' => 1,
        'min_redemption_points' => 10,
    ]);

    $member = Member::query()->create([
        'name' => 'Member A',
        'email' => 'member@example.com',
        'phone' => '628111',
        'points' => 50,
        'email_verified_at' => now(),
        'verification_token' => null,
    ]);

    $response = $this
        ->withSession([
            '_token' => $csrf,
            'dining_table_id' => $seed['table']->id,
            'self_order_token' => Str::random(40),
            'customer_ready' => true,
            'customer_type' => 'member',
            'member_id' => $member->id,
            'name' => 'Member A',
            'phone' => '628111',
            'email' => 'member@example.com',
            'cart_items' => $seed['cart'],
            'payment_token' => $seed['paymentToken'],
        ])
        ->post(route('self-order.payment.pay'), [
            '_token' => $csrf,
            'action' => 'pay',
            'method' => 'cashier',
            'token' => $seed['paymentToken'],
            'use_points' => true,
            'points_to_redeem' => 20,
        ]);

    $response->assertRedirect();
    $trx = Transaction::query()->latest()->first();
    expect($trx)->not()->toBeNull();
    expect((int) $trx->points_redeemed)->toBe(20);
    expect((int) $trx->point_discount_amount)->toBe(20);

    $member->refresh();
    expect((int) $member->points)->toBe(30);
});

it('creates a single cashier transaction and records an audit event', function () {
    $seed = seedSelfOrderCart(10);
    $csrf = Str::random(40);
    $selfOrderToken = Str::random(40);

    $this
        ->withSession([
            '_token' => $csrf,
            'dining_table_id' => $seed['table']->id,
            'self_order_token' => $selfOrderToken,
            'customer_ready' => true,
            'name' => 'Guest',
            'phone' => '628123',
            'cart_items' => $seed['cart'],
            'payment_token' => $seed['paymentToken'],
        ])
        ->post(route('self-order.payment.pay'), [
            '_token' => $csrf,
            'action' => 'pay',
            'method' => 'cashier',
            'token' => $seed['paymentToken'],
        ])
        ->assertRedirect();

    expect(Transaction::query()->count())->toBe(1);
    expect(TransactionEvent::query()->where('action', 'self_order.created')->count())->toBe(1);

    $this
        ->withSession([
            '_token' => $csrf,
            'dining_table_id' => $seed['table']->id,
            'self_order_token' => $selfOrderToken,
            'customer_ready' => true,
            'name' => 'Guest',
            'phone' => '628123',
            'cart_items' => $seed['cart'],
            'payment_token' => $seed['paymentToken'],
        ])
        ->post(route('self-order.payment.pay'), [
            '_token' => $csrf,
            'action' => 'pay',
            'method' => 'cashier',
            'token' => $seed['paymentToken'],
        ])
        ->assertRedirect();

    expect(Transaction::query()->count())->toBe(1);
});

it('includes payment method in checkout submit form', function () {
    $table = DiningTable::query()->create([
        'table_number' => 'A1',
        'image' => null,
        'qr_value' => null,
    ]);

    $category = Category::query()->create(['name' => 'Food']);
    $product = Product::query()->create([
        'name' => 'Mineral',
        'description' => 'Test',
        'image' => 'test.png',
        'is_available' => true,
        'is_promo' => false,
        'is_favorite' => false,
        'category_id' => $category->id,
        'printer_source_id' => null,
    ]);
    $variant = ProductVariant::query()->create([
        'product_id' => $product->id,
        'name' => 'Normal',
        'price' => 10000,
        'price_afterdiscount' => null,
        'percent' => null,
        'hpp' => 0,
    ]);

    $cart = [[
        'id' => (int) $product->id,
        'variant_id' => (int) $variant->id,
        'quantity' => 1,
        'selected' => true,
        'note' => '',
        'name' => $product->name.' - '.$variant->name,
        'image' => (string) ($product->image ?? ''),
        'price' => (int) $variant->price,
        'price_afterdiscount' => null,
        'percent' => null,
    ]];

    $response = $this
        ->withSession([
            'dining_table_id' => $table->id,
            'customer_ready' => true,
            'name' => 'Guest',
            'phone' => '628123',
            'cart_items' => $cart,
            'has_unpaid_transaction' => false,
        ])
        ->get(route('self-order.payment.page'));

    $response->assertOk();
    $response->assertSee('name="method"', false);
});

it('renders payment failure page', function () {
    $this->get(route('self-order.payment.failure'))
        ->assertOk()
        ->assertSee('Pembayaran Gagal');
});

it('shows cashier instructions on status page for cash pending transactions', function () {
    $table = DiningTable::query()->create([
        'table_number' => 'A1',
        'image' => null,
        'qr_value' => null,
    ]);

    $trx = Transaction::query()->create([
        'code' => Transaction::generateUniqueCode(),
        'member_id' => null,
        'channel' => 'self_order',
        'name' => 'Guest',
        'phone' => '628123',
        'email' => null,
        'order_type' => 'dine_in',
        'dining_table_id' => $table->id,
        'subtotal' => 10000,
        'tax_percentage' => 0,
        'tax_amount' => 0,
        'rounding_amount' => 0,
        'cash_received' => null,
        'cash_change' => null,
        'total' => 10000,
        'checkout_link' => '-',
        'payment_method' => 'cash',
        'payment_status' => 'pending',
        'order_status' => 'new',
        'external_id' => (string) Str::uuid(),
        'self_order_token' => (string) Str::random(40),
    ]);

    $this->get(route('self-order.payment.status', ['code' => $trx->code]))
        ->assertOk()
        ->assertSee('Silakan Bayar di Kasir')
        ->assertSee('Instruksi Pembayaran di Kasir')
        ->assertDontSee('Cek Status Pembayaran');
});

it('shows online payment actions on status page for online pending transactions', function () {
    $table = DiningTable::query()->create([
        'table_number' => 'A1',
        'image' => null,
        'qr_value' => null,
    ]);

    $trx = Transaction::query()->create([
        'code' => Transaction::generateUniqueCode(),
        'member_id' => null,
        'channel' => 'self_order',
        'name' => 'Guest',
        'phone' => '628123',
        'email' => null,
        'order_type' => 'dine_in',
        'dining_table_id' => $table->id,
        'subtotal' => 10000,
        'tax_percentage' => 0,
        'tax_amount' => 0,
        'rounding_amount' => 0,
        'cash_received' => null,
        'cash_change' => null,
        'total' => 10000,
        'checkout_link' => 'https://example.test/pay',
        'payment_method' => 'qris_midtrans',
        'payment_status' => 'pending',
        'order_status' => 'new',
        'external_id' => (string) Str::uuid(),
        'self_order_token' => (string) Str::random(40),
    ]);

    $this->get(route('self-order.payment.status', ['code' => $trx->code]))
        ->assertOk()
        ->assertSee('Pembayaran Online')
        ->assertSee('Lanjutkan Pembayaran')
        ->assertDontSee('Cek Status Sekarang')
        ->assertSee('Ganti Metode Pembayaran');
});

it('shows voucher discount on self-order status page', function () {
    $table = DiningTable::query()->create([
        'table_number' => 'A1',
        'image' => null,
        'qr_value' => null,
    ]);

    $trx = Transaction::query()->create([
        'code' => Transaction::generateUniqueCode(),
        'member_id' => null,
        'channel' => 'self_order',
        'name' => 'Guest',
        'phone' => '628123',
        'email' => null,
        'order_type' => 'dine_in',
        'dining_table_id' => $table->id,
        'subtotal' => 10000,
        'voucher_campaign_id' => null,
        'voucher_code_id' => null,
        'voucher_code' => 'TEST10',
        'voucher_discount_amount' => 1000,
        'tax_percentage' => 0,
        'tax_amount' => 0,
        'rounding_amount' => 0,
        'total' => 9000,
        'checkout_link' => '-',
        'payment_method' => 'cash',
        'payment_status' => 'pending',
        'order_status' => 'new',
        'external_id' => (string) Str::uuid(),
        'self_order_token' => (string) Str::random(40),
    ]);

    $this->get(route('self-order.payment.status', ['code' => $trx->code]))
        ->assertOk()
        ->assertSee('Diskon Voucher')
        ->assertSee('TEST10')
        ->assertSee('-Rp');
});
