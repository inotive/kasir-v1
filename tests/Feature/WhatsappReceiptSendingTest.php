<?php

use App\Jobs\SendWaReceiptJob;
use App\Livewire\Pos\PosPage;
use App\Livewire\Transaction\TransactionShowPage;
use App\Models\Category;
use App\Models\Member;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WhatsappSetting;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $category = Category::create(['name' => 'Food']);
    $this->product = Product::create([
        'name' => 'Nasi Goreng',
        'description' => 'Test',
        'image' => 'test.png',
        'is_available' => true,
        'category_id' => $category->id,
    ]);
    $this->variant = ProductVariant::create([
        'product_id' => $this->product->id,
        'name' => 'Normal',
        'price' => 100000,
    ]);

    $this->member = Member::create([
        'name' => 'Budi',
        'phone' => '081234567890',
    ]);
});

function enableWhatsappForTests(): void
{
    WhatsappSetting::current()->update([
        'is_enabled' => true,
        'openwa_session_id' => 'test-session-id',
        'status' => 'ready',
    ]);
}

test('checkout dispatches SendWaReceiptJob when the checkbox is checked and whatsapp is enabled', function () {
    enableWhatsappForTests();
    Queue::fake();

    $user = User::create([
        'name' => 'Cashier',
        'email' => 'wa-checkout@example.com',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);
    $user->givePermissionTo(['pos.access']);

    Livewire::actingAs($user)
        ->test(PosPage::class)
        ->assertSet('waEnabledForTenant', true)
        ->call('addVariantToCart', $this->variant->id)
        ->set('customerType', 'member')
        ->set('memberId', $this->member->id)
        ->set('customerPhone', $this->member->phone)
        ->set('customerName', $this->member->name)
        ->set('sendReceiptWhatsApp', true)
        ->set('paymentMethod', 'cash')
        ->set('cashReceived', 100000)
        ->call('checkout')
        ->assertHasNoErrors();

    $trx = Transaction::latest('id')->first();
    expect($trx)->not->toBeNull();

    Queue::assertPushed(SendWaReceiptJob::class, fn (SendWaReceiptJob $job) => $job->transactionId === $trx->id && $job->isResend === false);
});

test('checkout does not dispatch SendWaReceiptJob when the checkbox is unchecked', function () {
    enableWhatsappForTests();
    Queue::fake();

    $user = User::create([
        'name' => 'Cashier 2',
        'email' => 'wa-checkout2@example.com',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);
    $user->givePermissionTo(['pos.access']);

    Livewire::actingAs($user)
        ->test(PosPage::class)
        ->call('addVariantToCart', $this->variant->id)
        ->set('customerType', 'member')
        ->set('memberId', $this->member->id)
        ->set('customerPhone', $this->member->phone)
        ->set('customerName', $this->member->name)
        ->set('sendReceiptWhatsApp', false)
        ->set('paymentMethod', 'cash')
        ->set('cashReceived', 100000)
        ->call('checkout')
        ->assertHasNoErrors();

    Queue::assertNotPushed(SendWaReceiptJob::class);
});

test('checkout does not dispatch SendWaReceiptJob when whatsapp is not enabled for the tenant', function () {
    Queue::fake();

    $user = User::create([
        'name' => 'Cashier 3',
        'email' => 'wa-checkout3@example.com',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);
    $user->givePermissionTo(['pos.access']);

    Livewire::actingAs($user)
        ->test(PosPage::class)
        ->assertSet('waEnabledForTenant', false)
        ->call('addVariantToCart', $this->variant->id)
        ->set('customerType', 'member')
        ->set('memberId', $this->member->id)
        ->set('customerPhone', $this->member->phone)
        ->set('customerName', $this->member->name)
        ->set('sendReceiptWhatsApp', true)
        ->set('paymentMethod', 'cash')
        ->set('cashReceived', 100000)
        ->call('checkout')
        ->assertHasNoErrors();

    Queue::assertNotPushed(SendWaReceiptJob::class);
});

test('resend from transaction detail dispatches SendWaReceiptJob even if already sent', function () {
    enableWhatsappForTests();
    Queue::fake();

    $trx = Transaction::create([
        'code' => Transaction::generateUniqueCode(),
        'member_id' => $this->member->id,
        'channel' => 'pos',
        'name' => $this->member->name,
        'phone' => $this->member->phone,
        'checkout_link' => 'http://example.com',
        'external_id' => (string) \Illuminate\Support\Str::uuid(),
        'subtotal' => 100000,
        'total' => 100000,
        'payment_method' => 'cash',
        'payment_status' => 'paid',
        'order_status' => 'completed',
        'wa_receipt_sent_at' => now(),
        'wa_receipt_status' => 'sent',
    ]);

    $user = User::create([
        'name' => 'Owner',
        'email' => 'wa-resend@example.com',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);
    $user->assignRole('owner');

    Livewire::actingAs($user)
        ->test(TransactionShowPage::class, ['transaction' => $trx])
        ->call('sendReceiptWhatsApp');

    Queue::assertPushed(SendWaReceiptJob::class, fn (SendWaReceiptJob $job) => $job->transactionId === $trx->id && $job->isResend === true);
});

test('resend is blocked without the transactions.whatsapp.send permission', function () {
    enableWhatsappForTests();
    Queue::fake();

    $trx = Transaction::create([
        'code' => Transaction::generateUniqueCode(),
        'member_id' => $this->member->id,
        'channel' => 'pos',
        'name' => $this->member->name,
        'phone' => $this->member->phone,
        'checkout_link' => 'http://example.com',
        'external_id' => (string) \Illuminate\Support\Str::uuid(),
        'subtotal' => 100000,
        'total' => 100000,
        'payment_method' => 'cash',
        'payment_status' => 'paid',
        'order_status' => 'completed',
    ]);

    $user = User::create([
        'name' => 'Waiter',
        'email' => 'wa-resend-denied@example.com',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);
    $user->assignRole('waiter');
    $user->givePermissionTo(['transactions.details']);

    Livewire::actingAs($user)
        ->test(TransactionShowPage::class, ['transaction' => $trx])
        ->call('sendReceiptWhatsApp');

    Queue::assertNotPushed(SendWaReceiptJob::class);
});

test('the receipt link in the WA message uses the tenant\'s own domain, not the global APP_URL host', function () {
    $tenant = \App\Models\Tenant::create([
        'name' => 'Tenant Test',
        'slug' => 'tenant-test-wa',
        'domain' => 'tenant-test-wa.localhost',
        'is_active' => true,
    ]);
    $tenant->makeCurrent();

    enableWhatsappForTests();

    $trx = Transaction::create([
        'code' => Transaction::generateUniqueCode(),
        'member_id' => $this->member->id,
        'channel' => 'pos',
        'name' => $this->member->name,
        'phone' => $this->member->phone,
        'checkout_link' => 'http://example.com',
        'external_id' => (string) \Illuminate\Support\Str::uuid(),
        'subtotal' => 100000,
        'total' => 100000,
        'payment_method' => 'cash',
        'payment_status' => 'paid',
        'order_status' => 'completed',
        'wa_receipt_token' => 'test-token-123',
    ]);

    $captured = null;
    $this->mock(\App\Services\Whatsapp\OpenwaService::class, function ($mock) use (&$captured) {
        $mock->shouldReceive('sendText')
            ->once()
            ->andReturnUsing(function (string $sessionId, string $chatId, string $text) use (&$captured) {
                $captured = $text;

                return ['messageId' => 'x', 'timestamp' => time()];
            });
    });

    (new SendWaReceiptJob($trx->id, $tenant->id))->handle(app(\App\Services\Whatsapp\OpenwaService::class));

    expect($captured)->not->toBeNull();
    expect($captured)->toContain('://tenant-test-wa.localhost');
    expect($captured)->toContain('/order/payment/receipt/'.$trx->code);
    expect($captured)->toContain('wa_token=test-token-123');
    expect($captured)->not->toContain('://'.parse_url((string) config('app.url'), PHP_URL_HOST).'/order');
});

test('the WA message includes items, discounts, tax, payment method, and cash/change like the webpage', function () {
    $addonCategory = \App\Models\AddonCategory::create(['name' => 'Toppings']);
    $addon = \App\Models\Addon::create([
        'addon_category_id' => $addonCategory->id,
        'name' => 'Telur',
        'price' => 5000,
        'is_available' => true,
    ]);

    $trx = Transaction::create([
        'code' => Transaction::generateUniqueCode(),
        'channel' => 'pos',
        'name' => 'Budi Santoso',
        'phone' => '081234567890',
        'checkout_link' => '-',
        'external_id' => (string) \Illuminate\Support\Str::uuid(),
        'subtotal' => 45000,
        'manual_discount_amount' => 5000,
        'discount_total_amount' => 5000,
        'tax_percentage' => 10,
        'tax_amount' => 4000,
        'rounding_amount' => -500,
        'total' => 43500,
        'payment_method' => 'cash',
        'cash_received' => 50000,
        'cash_change' => 6500,
        'payment_status' => 'paid',
        'order_status' => 'completed',
        'points_earned' => 43,
        'wa_receipt_token' => 'abc123',
    ]);

    $item = \App\Models\TransactionItem::create([
        'transaction_id' => $trx->id,
        'product_id' => $this->product->id,
        'product_variant_id' => $this->variant->id,
        'quantity' => 2,
        'price' => 20000,
        'subtotal' => 45000,
    ]);

    \App\Models\TransactionItemAddon::create([
        'transaction_item_id' => $item->id,
        'addon_id' => $addon->id,
        'name' => 'Telur',
        'price' => 5000,
        'quantity' => 1,
    ]);

    $job = new SendWaReceiptJob($trx->id, null, false);
    $ref = new ReflectionMethod($job, 'buildMessage');
    $ref->setAccessible(true);

    $trxFull = Transaction::query()
        ->with([
            'member', 'diningTable',
            'transactionItems.product', 'transactionItems.variant', 'transactionItems.itemAddons.addon',
            'transactionItems.childTransactionItems.product', 'transactionItems.childTransactionItems.variant',
            'transactionItems.childTransactionItems.itemAddons.addon',
        ])
        ->find($trx->id);

    $text = $ref->invoke($job, $trxFull, null);

    expect($text)->toContain('2x Nasi Goreng');
    expect($text)->toContain('+ Telur 1x (Rp5.000)');
    expect($text)->toContain('Subtotal: Rp45.000');
    expect($text)->toContain('Diskon Manual: -Rp5.000');
    expect($text)->toContain('Pajak PB1 (10.00%): Rp4.000');
    expect($text)->toContain('Pembulatan: -Rp500');
    expect($text)->toContain('Total: Rp43.500');
    expect($text)->toContain('Metode Pembayaran: Tunai');
    expect($text)->toContain('Tunai: Rp50.000');
    expect($text)->toContain('Kembalian: Rp6.500');
    expect($text)->not->toContain('Poin dari transaksi ini');
});

test('resend from the transactions list page dispatches SendWaReceiptJob', function () {
    enableWhatsappForTests();
    Queue::fake();

    $trx = Transaction::create([
        'code' => Transaction::generateUniqueCode(),
        'member_id' => $this->member->id,
        'channel' => 'pos',
        'name' => $this->member->name,
        'phone' => $this->member->phone,
        'checkout_link' => 'http://example.com',
        'external_id' => (string) \Illuminate\Support\Str::uuid(),
        'subtotal' => 100000,
        'total' => 100000,
        'payment_method' => 'cash',
        'payment_status' => 'paid',
        'order_status' => 'completed',
    ]);

    $user = User::create([
        'name' => 'Owner 2',
        'email' => 'wa-list-resend@example.com',
        'password' => bcrypt('password'),
        'is_active' => true,
    ]);
    $user->assignRole('owner');

    Livewire::actingAs($user)
        ->test(\App\Livewire\Transaction\TransactionsPage::class)
        ->call('sendReceiptWhatsApp', $trx->id);

    Queue::assertPushed(SendWaReceiptJob::class, fn (SendWaReceiptJob $job) => $job->transactionId === $trx->id);
});
