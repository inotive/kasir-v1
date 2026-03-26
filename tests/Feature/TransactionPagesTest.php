<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_transactions_page_renders_correctly()
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('admin');

        Transaction::create([
            'code' => 'TRX-TEST-001',
            'external_id' => 'EXT-001',
            'name' => 'Budi Santoso',
            'checkout_link' => '',
            'manual_discount_amount' => 10000,
            'manual_discount_type' => 'fixed_amount',
            'manual_discount_value' => 10000,
            'manual_discount_note' => 'Diskon Spesial',
            'manual_discount_by_user_id' => $user->id,
            'total' => 100000,
            'subtotal' => 110000,
            'payment_status' => 'paid',
            'payment_method' => 'cash',
            'order_type' => 'take_away',
            'cash_received' => 110000,
            'cash_change' => 10000,
        ]);

        $this->actingAs($user)
            ->get(route('transactions.index'))
            ->assertStatus(200)
            ->assertSee('Riwayat Transaksi')
            ->assertSee('Cetak Struk')
            ->assertSee('Tunai')
            ->assertSee('Sudah Bayar')
            ->assertSee('Diskon Manual');
    }

    public function test_transaction_show_page_renders_manual_discount_correctly()
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('admin');

        $transaction = Transaction::create([
            'code' => 'TRX-TEST-002',
            'external_id' => 'EXT-002',
            'name' => 'Siti Aminah',
            'checkout_link' => '',
            'manual_discount_amount' => 50000,
            'manual_discount_type' => 'fixed_amount',
            'manual_discount_value' => 50000,
            'manual_discount_note' => 'Diskon Karyawan',
            'manual_discount_by_user_id' => $user->id,
            'total' => 100000,
            'subtotal' => 150000,
            'payment_status' => 'paid',
            'payment_method' => 'cash',
            'order_type' => 'take_away',
            'cash_received' => 150000,
            'cash_change' => 50000,
        ]);

        $this->actingAs($user)
            ->get(route('transactions.show', $transaction))
            ->assertStatus(200)
            ->assertSee('Cetak Struk')
            ->assertSee('Diskon Manual')
            ->assertSee('Tunai')
            ->assertSee('Sudah Bayar')
            ->assertSee('-Rp50.000')
            ->assertSee('Diskon Karyawan')
            ->assertSee('Oleh: '.$user->name);
    }
}
