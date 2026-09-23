<?php

use App\Events\SelfOrderPaymentUpdated;
use App\Exports\Reports\TransactionsExport;
use App\Livewire\Pos\PosPage;
use App\Livewire\Transaction\TransactionsPage;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class);

function cashierAttributionTransaction(array $overrides = []): Transaction
{
    return Transaction::query()->create(array_merge([
        'code' => 'TRX-'.str()->upper(str()->random(8)),
        'external_id' => 'EXT-'.str()->uuid(),
        'channel' => 'pos',
        'name' => 'Pelanggan',
        'order_type' => 'take_away',
        'subtotal' => 10000,
        'total' => 10000,
        'checkout_link' => '',
        'payment_method' => 'cash',
        'payment_status' => 'paid',
        'paid_at' => now(),
    ], $overrides));
}

function cashierAttributionVariant(): ProductVariant
{
    $category = Category::query()->create(['name' => 'Makanan']);
    $product = Product::query()->create([
        'name' => 'Produk Kasir',
        'description' => '-',
        'image' => '-',
        'price' => 10000,
        'is_available' => true,
        'category_id' => $category->id,
    ]);

    return ProductVariant::query()->create([
        'product_id' => $product->id,
        'name' => 'Reguler',
        'price' => 10000,
        'stock' => 10,
        'hpp' => 5000,
    ]);
}

test('pos checkout records the authenticated cashier and payment time', function () {
    $this->seed(RolePermissionSeeder::class);
    $cashier = User::factory()->create();
    $cashier->assignRole('cashier');
    $variant = cashierAttributionVariant();

    Livewire::actingAs($cashier)
        ->test(PosPage::class)
        ->call('addVariantToCart', $variant->id)
        ->set('customerName', 'Budi')
        ->set('paymentMethod', 'cash')
        ->set('cashReceived', '20000')
        ->call('checkout')
        ->assertHasNoErrors();

    $transaction = Transaction::query()->latest('id')->firstOrFail();

    expect((string) $transaction->payment_status)->toBe('paid')
        ->and((int) $transaction->cashier_user_id)->toBe((int) $cashier->id)
        ->and($transaction->paid_at)->not->toBeNull()
        ->and($transaction->manual_discount_by_user_id)->toBeNull()
        ->and($transaction->events()->where('action', 'payment.completed')->where('actor_user_id', $cashier->id)->exists())->toBeTrue();
});

test('cashier completing a pending self order is recorded as its cashier', function () {
    $this->seed(RolePermissionSeeder::class);
    Event::fake([SelfOrderPaymentUpdated::class]);
    $cashier = User::factory()->create();
    $cashier->assignRole('cashier');
    $variant = cashierAttributionVariant();

    $transaction = cashierAttributionTransaction([
        'channel' => 'self_order',
        'payment_status' => 'pending',
        'paid_at' => null,
        'self_order_token' => str()->random(40),
    ]);
    TransactionItem::query()->create([
        'transaction_id' => $transaction->id,
        'product_id' => $variant->product_id,
        'product_variant_id' => $variant->id,
        'quantity' => 1,
        'price' => 10000,
        'subtotal' => 10000,
    ]);

    Livewire::actingAs($cashier)
        ->test(PosPage::class)
        ->call('loadPending', $transaction->id)
        ->set('paymentMethod', 'cash')
        ->set('cashReceived', '20000')
        ->call('checkout')
        ->assertHasNoErrors();

    $transaction->refresh();

    expect((string) $transaction->channel)->toBe('self_order')
        ->and((string) $transaction->payment_status)->toBe('paid')
        ->and((int) $transaction->cashier_user_id)->toBe((int) $cashier->id)
        ->and($transaction->paid_at)->not->toBeNull();
});

test('automatic self order and unassigned legacy transactions stay distinct', function () {
    $cashier = User::factory()->create(['name' => 'Kasir Satu']);
    $cashierTransaction = cashierAttributionTransaction(['cashier_user_id' => $cashier->id]);
    $automatic = cashierAttributionTransaction([
        'channel' => 'self_order',
        'payment_method' => 'qris_midtrans',
        'cashier_user_id' => null,
    ]);
    $legacy = cashierAttributionTransaction(['cashier_user_id' => null]);

    expect($cashierTransaction->fresh()->cashierSourceLabel())->toBe('Kasir Satu')
        ->and($automatic->fresh()->cashierSourceLabel())->toBe('Self Order Otomatis')
        ->and($legacy->fresh()->cashierSourceLabel())->toBe('Tidak tercatat')
        ->and(Transaction::query()->forCashierSource((string) $cashier->id)->count())->toBe(1)
        ->and(Transaction::query()->forCashierSource('automatic')->count())->toBe(1)
        ->and(Transaction::query()->forCashierSource('unassigned')->count())->toBe(1);
});

test('transaction page and excel export can filter by cashier', function () {
    $this->seed(RolePermissionSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $cashierA = User::factory()->create(['name' => 'Kasir A']);
    $cashierB = User::factory()->create(['name' => 'Kasir B']);

    cashierAttributionTransaction(['code' => 'TRX-KASIR-A', 'cashier_user_id' => $cashierA->id]);
    cashierAttributionTransaction(['code' => 'TRX-KASIR-B', 'cashier_user_id' => $cashierB->id]);

    Livewire::actingAs($admin)
        ->test(TransactionsPage::class)
        ->set('cashierFilter', (string) $cashierA->id)
        ->assertSee('TRX-KASIR-A')
        ->assertDontSee('TRX-KASIR-B')
        ->assertSee('Kasir A');

    Excel::fake();
    $today = now()->format('Y-m-d');
    $this->actingAs($admin)
        ->get(route('transactions.excel', [
            'fromDate' => $today,
            'toDate' => $today,
            'cashierFilter' => (string) $cashierA->id,
        ]))
        ->assertOk();

    Excel::assertDownloaded("laporan-transaksi_{$today}_{$today}.xlsx", function (TransactionsExport $export): bool {
        $cells = collect($export->array())->flatten()->map(fn ($value) => (string) $value);

        return $cells->contains('Kasir A')
            && $cells->contains('TRX-KASIR-A')
            && ! $cells->contains('TRX-KASIR-B');
    });

    $this->actingAs($admin)
        ->get(route('transactions.pdf', [
            'fromDate' => $today,
            'toDate' => $today,
            'cashierFilter' => (string) $cashierA->id,
        ]))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
});

test('operational date uses paid at and falls back to created at', function () {
    $paidToday = cashierAttributionTransaction([
        'paid_at' => now(),
    ]);
    $paidToday->forceFill([
        'created_at' => now()->subDay(),
        'updated_at' => now()->subDay(),
    ])->saveQuietly();
    $legacyToday = cashierAttributionTransaction([
        'payment_status' => 'pending',
        'paid_at' => null,
        'created_at' => now(),
    ]);

    $ids = Transaction::query()
        ->withinOperationalDates(now()->format('Y-m-d'), now()->format('Y-m-d'))
        ->pluck('id');

    expect($ids)->toContain($paidToday->id, $legacyToday->id);
});
