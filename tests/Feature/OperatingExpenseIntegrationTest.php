<?php

use App\Livewire\Reports\OperatingExpensesPage;
use App\Livewire\Reports\SalesProfitReportPage;
use App\Models\InventoryMovement;
use App\Models\OperatingExpense;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actor = User::factory()->create();
    $this->actor->assignRole('admin');
    $this->actingAs($this->actor);
});

function assertExpenseReportTotal(int $amount): void
{
    Livewire::test(SalesProfitReportPage::class)
        ->call('setRange', 'today')
        ->assertViewHas('metrics', function ($metrics) use ($amount) {
            expect($metrics['current']['operatingExpenseTotal'])->toEqual($amount);
            expect($metrics['current']['netProfit'])->toEqual(-$amount);

            return true;
        })
        ->assertViewHas('dailyRows', function ($rows) use ($amount) {
            expect($rows->sum('operating_expense_total'))->toEqual($amount);
            expect($rows->sum('net_profit'))->toEqual(-$amount);

            return true;
        });

    Excel::fake();
    test()->get(route('reports.sales-profit.excel', ['from' => today()->toDateString(), 'to' => today()->toDateString()]))->assertOk();
    Excel::assertDownloaded('laporan-penjualan-laba_'.today()->format('Ymd').'-'.today()->format('Ymd').'.xlsx', function ($export) use ($amount) {
        $rows = collect($export->array());
        expect($rows->first(fn ($row) => $row[0] === 'Beban Operasional')[1])->toEqual($amount);
        expect($rows->first(fn ($row) => $row[0] === 'Laba Bersih')[1])->toEqual(-$amount);

        return true;
    });
}

test('quantity expense create edit and delete update profit report and excel without changing inventory', function () {
    $form = Livewire::test(OperatingExpensesPage::class)
        ->call('openCreate')
        ->set('category', 'Bahan kebersihan')
        ->set('quantity', '2,5')
        ->set('unit', 'liter')
        ->set('unitCost', 10000)
        ->call('save')
        ->assertHasNoErrors();
    $expense = OperatingExpense::query()->sole();
    assertExpenseReportTotal(25000);

    $form->call('openEdit', $expense->id)
        ->set('quantity', '3')
        ->call('save')
        ->assertHasNoErrors();
    assertExpenseReportTotal(30000);

    $form->call('openDeleteConfirm', $expense->id)->call('delete');
    assertExpenseReportTotal(0);
    expect(InventoryMovement::query()->count())->toBe(0);
});

test('profit daily table includes an expense date even without sales', function () {
    Livewire::test(OperatingExpensesPage::class)
        ->call('openCreate')
        ->set('category', 'Listrik')
        ->set('unitCost', 20000)
        ->call('save')
        ->assertHasNoErrors();

    Livewire::test(SalesProfitReportPage::class)
        ->call('setRange', 'today')
        ->assertViewHas('dailyRows', function ($rows) {
            expect($rows)->toHaveCount(1);
            expect($rows->first()['day'])->toBe(today()->toDateString());
            expect($rows->first()['tx_count'])->toBe(0);
            expect($rows->first()['operating_expense_total'])->toEqual(20000);
            expect($rows->first()['net_profit'])->toEqual(-20000);

            return true;
        });
});

test('expense reports and export use expense date rather than creation date', function () {
    Livewire::test(OperatingExpensesPage::class)
        ->call('openCreate')
        ->set('expenseDate', today()->subMonths(2)->toDateString())
        ->set('category', 'Tagihan lama')
        ->set('unitCost', 50000)
        ->call('save')
        ->assertHasNoErrors();

    assertExpenseReportTotal(0);
    Livewire::test(SalesProfitReportPage::class)
        ->call('setTransactionsRange', today()->subMonths(2)->toDateString(), today()->subMonths(2)->toDateString())
        ->assertViewHas('metrics', fn ($metrics) => $metrics['current']['operatingExpenseTotal'] == 50000);
});

test('report viewer can read expenses but cannot create edit or delete them', function () {
    $expense = OperatingExpense::query()->create(['expense_date' => today(), 'category' => 'Listrik', 'amount' => 10000]);
    $this->actor->syncRoles([]);
    $this->actor->givePermissionTo('reports.sales');

    Livewire::test(OperatingExpensesPage::class)->assertSee('Listrik')->call('openCreate')->assertForbidden();
    Livewire::test(OperatingExpensesPage::class)->call('openEdit', $expense->id)->assertForbidden();
    Livewire::test(OperatingExpensesPage::class)->set('deletingId', $expense->id)->call('delete')->assertForbidden();
    Livewire::test(OperatingExpensesPage::class)->set('editingId', $expense->id)->call('save')->assertForbidden();
    expect($expense->fresh()->amount)->toBe(10000);
});

test('expense totals export and edit lookup stay isolated by tenant', function () {
    $first = Tenant::query()->create(['name' => 'Expense A', 'slug' => 'expense-a', 'domain' => 'expense-a.test', 'is_active' => true]);
    $second = Tenant::query()->create(['name' => 'Expense B', 'slug' => 'expense-b', 'domain' => 'expense-b.test', 'is_active' => true]);

    try {
        $second->makeCurrent();
        $other = OperatingExpense::query()->create(['expense_date' => today(), 'category' => 'Tenant B expense', 'amount' => 99999]);
        $first->makeCurrent();
        Livewire::test(OperatingExpensesPage::class)
            ->call('openCreate')
            ->set('category', 'Tenant A expense')
            ->set('quantity', '2')
            ->set('unit', 'pcs')
            ->set('unitCost', 5000)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Tenant A expense')
            ->assertDontSee('Tenant B expense')
            ->assertViewHas('total', fn ($total) => $total == 10000);

        assertExpenseReportTotal(10000);
        expect(fn () => Livewire::test(OperatingExpensesPage::class)->call('openEdit', $other->id))
            ->toThrow(ModelNotFoundException::class);
        Livewire::test(OperatingExpensesPage::class)->set('deletingId', $other->id)->call('delete');
        expect(OperatingExpense::withoutGlobalScopes()->findOrFail($other->id)->amount)->toBe(99999);
    } finally {
        Tenant::forgetCurrent();
    }
});
