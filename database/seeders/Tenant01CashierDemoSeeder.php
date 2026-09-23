<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class Tenant01CashierDemoSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::query()->where('slug', 'tenant01')->first();

        if (! $tenant) {
            throw new RuntimeException('Tenant tenant01 belum tersedia. Jalankan TestTenantSeeder terlebih dahulu.');
        }

        $tenant->execute(function () use ($tenant): void {
            DB::transaction(function () use ($tenant): void {
                // Clean up demo rows from an older/failed run that missed tenant attribution.
                DB::table('transactions')
                    ->whereNull('tenant_id')
                    ->where('code', 'like', 'T01-DEMO-%')
                    ->delete();

                $cashierA = $this->cashier($tenant, 'Kasir Andi', 'kasir.andi@tenant01.test');
                $cashierB = $this->cashier($tenant, 'Kasir Siti', 'kasir.siti@tenant01.test');
                $variants = $this->catalogue();

                $today = CarbonImmutable::now()->startOfDay();
                $yesterday = $today->subDay();

                $this->transaction(
                    code: 'T01-DEMO-001',
                    paidAt: $today->addHours(8)->addMinutes(15),
                    cashier: $cashierA,
                    channel: 'pos',
                    paymentMethod: 'cash',
                    customer: 'Budi',
                    lines: [[$variants['nasi_goreng'], 2], [$variants['es_teh'], 2]],
                );
                $this->transaction(
                    code: 'T01-DEMO-002',
                    paidAt: $today->addHours(9)->addMinutes(5),
                    cashier: $cashierA,
                    channel: 'pos',
                    paymentMethod: 'qris',
                    customer: 'Rina',
                    lines: [[$variants['mie_goreng'], 1], [$variants['kopi_susu'], 1]],
                );
                $this->transaction(
                    code: 'T01-DEMO-003',
                    paidAt: $today->addHours(10)->addMinutes(20),
                    cashier: $cashierB,
                    channel: 'pos',
                    paymentMethod: 'cash',
                    customer: 'Dewi',
                    lines: [[$variants['nasi_goreng'], 1], [$variants['kopi_susu'], 2]],
                );
                $this->transaction(
                    code: 'T01-DEMO-004',
                    paidAt: $today->addHours(11)->addMinutes(10),
                    cashier: $cashierB,
                    channel: 'self_order',
                    paymentMethod: 'cash',
                    customer: 'Self Order - Meja 1',
                    lines: [[$variants['mie_goreng'], 2], [$variants['es_teh'], 2]],
                );
                $this->transaction(
                    code: 'T01-DEMO-005',
                    paidAt: $today->addHours(12)->addMinutes(30),
                    cashier: null,
                    channel: 'self_order',
                    paymentMethod: 'qris_midtrans',
                    customer: 'Self Order Otomatis',
                    lines: [[$variants['nasi_goreng'], 1], [$variants['es_teh'], 1]],
                );
                $this->transaction(
                    code: 'T01-DEMO-006',
                    paidAt: $yesterday->addHours(14)->addMinutes(5),
                    cashier: $cashierA,
                    channel: 'pos',
                    paymentMethod: 'cash',
                    customer: 'Pelanggan Kemarin A',
                    lines: [[$variants['kopi_susu'], 2]],
                );
                $this->transaction(
                    code: 'T01-DEMO-007',
                    paidAt: $yesterday->addHours(16)->addMinutes(40),
                    cashier: $cashierB,
                    channel: 'pos',
                    paymentMethod: 'qris',
                    customer: 'Pelanggan Kemarin B',
                    lines: [[$variants['mie_goreng'], 1], [$variants['es_teh'], 1]],
                );
                $this->transaction(
                    code: 'T01-DEMO-008',
                    paidAt: $yesterday->addHours(18)->addMinutes(10),
                    cashier: null,
                    channel: 'self_order',
                    paymentMethod: 'qris_midtrans',
                    customer: 'Self Order Otomatis Kemarin',
                    lines: [[$variants['nasi_goreng'], 1], [$variants['kopi_susu'], 1]],
                );
            });
        });

        $this->command?->info('Demo tenant01 siap: 2 kasir, 2 kategori, 4 produk, dan 8 transaksi.');
        $this->command?->info('Login kasir: kasir.andi@tenant01.test / password');
        $this->command?->info('Login kasir: kasir.siti@tenant01.test / password');
    }

    private function cashier(Tenant $tenant, string $name, string $email): User
    {
        $cashier = User::withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'email' => $email],
            [
                'name' => $name,
                'password' => Hash::make('password'),
                'role' => 'cashier',
                'is_active' => true,
            ],
        );

        $cashier->forceFill(['name' => $name, 'role' => 'cashier', 'is_active' => true])->save();

        if (! $cashier->hasRole('cashier')) {
            $cashier->assignRole('cashier');
        }

        return $cashier;
    }

    /** @return array<string, ProductVariant> */
    private function catalogue(): array
    {
        $food = Category::withTrashed()->firstOrCreate(['name' => 'Makanan']);
        if ($food->trashed()) {
            $food->restore();
        }

        $drink = Category::withTrashed()->firstOrCreate(['name' => 'Minuman']);
        if ($drink->trashed()) {
            $drink->restore();
        }

        return [
            'nasi_goreng' => $this->variant($food, 'Nasi Goreng', 25000, 12000),
            'mie_goreng' => $this->variant($food, 'Mie Goreng', 22000, 10000),
            'es_teh' => $this->variant($drink, 'Es Teh Manis', 8000, 2500),
            'kopi_susu' => $this->variant($drink, 'Kopi Susu', 18000, 7000),
        ];
    }

    private function variant(Category $category, string $name, int $price, int $hpp): ProductVariant
    {
        $product = Product::query()->updateOrCreate(
            ['name' => $name],
            [
                'description' => 'Produk demo tenant01',
                'image' => '',
                'is_available' => true,
                'category_id' => $category->id,
            ],
        );

        return ProductVariant::query()->updateOrCreate(
            ['product_id' => $product->id, 'name' => 'Reguler'],
            ['price' => $price, 'hpp' => $hpp],
        );
    }

    /** @param array<int, array{0: ProductVariant, 1: int}> $lines */
    private function transaction(
        string $code,
        CarbonImmutable $paidAt,
        ?User $cashier,
        string $channel,
        string $paymentMethod,
        string $customer,
        array $lines,
    ): void {
        $subtotal = collect($lines)->sum(
            fn (array $line): int => (int) $line[0]->price * (int) $line[1]
        );

        $transaction = Transaction::withoutEvents(function () use ($cashier, $channel, $code, $customer, $paidAt, $paymentMethod, $subtotal): Transaction {
            $transaction = Transaction::query()->where('code', $code)->first() ?? new Transaction;
            $transaction->fill([
                'cashier_user_id' => $cashier?->id,
                'channel' => $channel,
                'name' => $customer,
                'order_type' => $channel === 'self_order' ? 'dine_in' : 'take_away',
                'subtotal' => $subtotal,
                'tax_percentage' => 0,
                'tax_amount' => 0,
                'rounding_amount' => 0,
                'cash_received' => $paymentMethod === 'cash' ? $subtotal : null,
                'cash_change' => $paymentMethod === 'cash' ? 0 : null,
                'total' => $subtotal,
                'checkout_link' => '',
                'payment_method' => $paymentMethod,
                'payment_status' => 'paid',
                'order_status' => 'new',
                'paid_at' => $paidAt,
                'external_id' => 'DEMO-'.$code,
                'is_midtrans_processed' => $paymentMethod === 'qris_midtrans',
                'self_order_token' => $channel === 'self_order' ? hash('sha256', $code) : null,
            ]);
            $transaction->forceFill([
                'tenant_id' => Tenant::current()->id,
                'code' => $code,
                'created_at' => $paidAt,
                'updated_at' => $paidAt,
            ])->saveQuietly();

            return $transaction;
        });

        // Raw delete also removes incorrectly unscoped items from an earlier demo run.
        DB::table('transaction_items')->where('transaction_id', $transaction->id)->delete();
        foreach ($lines as [$variant, $quantity]) {
            TransactionItem::query()->create([
                'transaction_id' => $transaction->id,
                'product_id' => $variant->product_id,
                'product_variant_id' => $variant->id,
                'quantity' => $quantity,
                'price' => (int) $variant->price,
                'hpp_unit' => (int) $variant->hpp,
                'hpp_total' => (int) $variant->hpp * $quantity,
                'subtotal' => (int) $variant->price * $quantity,
            ]);
        }
    }
}
