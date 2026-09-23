<?php

namespace Tests\Feature;

use App\Livewire\Reports\OperatingExpensesPage;
use App\Models\OperatingExpense;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OperatingExpensesPageTest extends TestCase
{
    use RefreshDatabase;

    private function signInAsAdmin(): User
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('admin');
        $this->actingAs($user);

        return $user;
    }

    public function test_quantity_breakdown_is_saved_displayed_and_loaded_for_editing(): void
    {
        $user = $this->signInAsAdmin();

        Livewire::test(OperatingExpensesPage::class)
            ->call('openCreate')
            ->assertSet('quantity', '1')
            ->assertDontSee('Hitung dari qty')
            ->set('category', 'Beras')
            ->set('quantity', '2,5')
            ->set('unit', 'kg')
            ->set('unitCost', 12000)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('formModalOpen', false)
            ->call('openCreate')
            ->assertSet('quantity', '1');

        $this->assertDatabaseHas('operating_expenses', [
            'category' => 'Beras',
            'quantity' => 2.5,
            'unit' => 'kg',
            'unit_cost' => 12000,
            'amount' => 30000,
            'created_by_user_id' => $user->id,
        ]);

        $expense = OperatingExpense::query()->sole();

        Livewire::test(OperatingExpensesPage::class)
            ->assertSeeInOrder(['Kategori', 'Qty', 'Satuan', 'Harga / Satuan', 'Nilai'])
            ->assertSee('2,5')
            ->assertSee('kg')
            ->assertSee('Rp12.000')
            ->assertSee('Rp30.000')
            ->call('openEdit', $expense->id)
            ->assertSet('quantity', '2,5')
            ->assertSet('unit', 'kg')
            ->assertSet('unitCost', 12000)
            ->set('quantity', '3,75')
            ->set('unitCost', 14000)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('operating_expenses', [
            'id' => $expense->id,
            'quantity' => 3.75,
            'unit' => 'kg',
            'unit_cost' => 14000,
            'amount' => 52500,
        ]);
    }

    public function test_existing_expense_without_quantity_can_be_given_a_breakdown(): void
    {
        $this->signInAsAdmin();
        $expense = OperatingExpense::query()->create([
            'expense_date' => today(),
            'category' => 'Air',
            'amount' => 25000,
        ]);

        Livewire::test(OperatingExpensesPage::class)
            ->assertSee('Air')
            ->assertSee('Rp25.000')
            ->call('openEdit', $expense->id)
            ->assertSet('quantity', '1')
            ->assertSet('unitCost', 25000)
            ->set('quantity', '5')
            ->set('unit', 'galon')
            ->set('unitCost', 5000)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('operating_expenses', [
            'id' => $expense->id,
            'quantity' => 5,
            'unit' => 'galon',
            'unit_cost' => 5000,
            'amount' => 25000,
        ]);
    }

    public function test_new_expense_defaults_to_one_and_saves_without_a_unit(): void
    {
        $this->signInAsAdmin();

        Livewire::test(OperatingExpensesPage::class)
            ->call('openCreate')
            ->assertSet('quantity', '1')
            ->assertDontSee('Hitung dari qty')
            ->set('category', 'Listrik')
            ->set('unitCost', 20000)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('operating_expenses', [
            'category' => 'Listrik',
            'quantity' => 1,
            'unit' => null,
            'unit_cost' => 20000,
            'amount' => 20000,
        ]);
    }

    public function test_operating_expenses_page_renders(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->get(route('reports.operating-expenses'))
            ->assertOk()
            ->assertSee('Beban Operasional');
    }

    public function test_saving_a_legacy_expense_preserves_its_total(): void
    {
        $this->signInAsAdmin();
        $expense = OperatingExpense::query()->create([
            'expense_date' => today(),
            'category' => 'Listrik',
            'amount' => 25000,
        ]);

        Livewire::test(OperatingExpensesPage::class)
            ->call('openEdit', $expense->id)
            ->assertSet('quantity', '1')
            ->assertSet('unitCost', 25000)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('operating_expenses', [
            'id' => $expense->id,
            'quantity' => 1,
            'unit_cost' => 25000,
            'amount' => 25000,
        ]);
    }
}
