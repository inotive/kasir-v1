<?php

use App\Livewire\Pos\PosPage;
use App\Livewire\Transaction\TransactionsPage;
use App\Models\Member;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use Barryvdh\DomPDF\PDF;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->user = User::factory()->create();
    $this->user->assignRole('admin');
    $this->actingAs($this->user);
});

function memberHistoryTransaction(?Member $member, string $code, array $attributes = []): Transaction
{
    $transaction = Transaction::query()->create(array_merge([
        'member_id' => $member?->id,
        'code' => $code,
        'external_id' => $code,
        'name' => 'Nama saat transaksi',
        'checkout_link' => '',
        'subtotal' => 10000,
        'total' => 10000,
        'payment_method' => 'cash',
        'payment_status' => 'paid',
        'order_type' => 'take_away',
    ], $attributes));

    if (isset($attributes['created_at'])) {
        $transaction->forceFill(['created_at' => $attributes['created_at']])->save();
    }

    return $transaction;
}

test('transaction member options render distinct labels for duplicate names', function () {
    Member::factory()->create(['name' => 'Budi', 'phone' => '081111111111']);
    Member::factory()->create(['name' => 'Budi', 'phone' => '082222222222']);
    $withoutPhone = Member::factory()->create(['name' => 'Budi', 'phone' => null]);

    $this->get(route('transactions.index'))
        ->assertOk()
        ->assertSee('Budi (081111111111)')
        ->assertSee('Budi (082222222222)')
        ->assertSee('Budi (Member #'.$withoutPhone->id.')');
});

test('member history links include older transactions and isolate duplicate names', function (string $parameter) {
    $member = Member::factory()->create(['name' => 'Nama Sama']);
    $other = Member::factory()->create(['name' => 'Nama Sama']);
    memberHistoryTransaction($member, 'HISTORY-OLD', ['created_at' => now()->subMonths(3)]);
    memberHistoryTransaction($other, 'HISTORY-OTHER');
    memberHistoryTransaction(null, 'HISTORY-WALKIN');
    $params = [$parameter => $parameter === 'memberId' ? $member->id : [$member->id]];

    $this->get(route('transactions.index', $params))
        ->assertOk()
        ->assertSee('HISTORY-OLD')
        ->assertDontSee('HISTORY-OTHER')
        ->assertDontSee('HISTORY-WALKIN');

    $this->get(route('members.index'))
        ->assertOk()
        ->assertSee(route('transactions.index', ['memberIds' => [$member->id]]));
})->with(['memberId', 'memberIds']);

test('multiple member selection updates rows summaries pagination and clear action', function () {
    $members = Member::factory()->count(3)->create();
    foreach ($members as $index => $member) {
        memberHistoryTransaction($member, 'MULTI-'.$index);
    }
    $ids = [$members[0]->id, $members[1]->id];

    Livewire::test(TransactionsPage::class)
        ->call('setPage', 2)
        ->set('memberIds', array_map('strval', $ids))
        ->assertSet('memberIds', $ids)
        ->assertSet('paginators.page', 1)
        ->assertSee('MULTI-0')
        ->assertSee('MULTI-1')
        ->assertDontSee('MULTI-2')
        ->assertViewHas('stats', fn ($stats) => $stats['totalTransactions'] === 2)
        ->assertViewHas('filteredMemberLabels', fn ($labels) => count($labels) === 2)
        ->call('clearMemberFilter')
        ->assertSet('memberIds', [])
        ->assertSee('MULTI-2')
        ->assertViewHas('stats', fn ($stats) => $stats['totalTransactions'] === 3);
});

test('member history respects date filters and members without transactions', function () {
    $member = Member::factory()->create();
    $empty = Member::factory()->create();
    memberHistoryTransaction($member, 'DATE-OLD', ['created_at' => now()->subMonths(3)]);
    memberHistoryTransaction($member, 'DATE-TODAY');

    Livewire::withQueryParams(['memberIds' => [$member->id]])
        ->test(TransactionsPage::class)
        ->assertSet('fromDate', null)
        ->assertSee('DATE-OLD')
        ->call('setRange', 'today')
        ->assertDontSee('DATE-OLD')
        ->assertSee('DATE-TODAY')
        ->set('memberIds', [$empty->id])
        ->assertDontSee('DATE-TODAY')
        ->assertViewHas('stats', fn ($stats) => $stats['totalTransactions'] === 0);
});

test('customer labels and search follow the current member name and phone', function () {
    $member = Member::factory()->create(['name' => 'Nama Baru', 'phone' => '083333333333']);
    $transaction = memberHistoryTransaction($member, 'CURRENT-MEMBER');

    Livewire::test(TransactionsPage::class)
        ->set('search', 'Nama Baru')
        ->assertSee('CURRENT-MEMBER')
        ->set('search', '083333333333')
        ->assertSee('CURRENT-MEMBER');

    $this->get(route('transactions.show', $transaction))
        ->assertOk()
        ->assertSee('Nama Baru (083333333333)');
});

test('transaction member identifiers do not expose phone numbers without transaction PII permission', function () {
    $this->user->syncRoles([]);
    $this->user->givePermissionTo(['dashboard.access', 'transactions.view', 'transactions.details', 'members.pii.view']);
    $member = Member::factory()->create(['name' => 'Budi Privat', 'phone' => '084444444444']);
    $transaction = memberHistoryTransaction($member, 'PRIVATE-MEMBER');

    Livewire::test(TransactionsPage::class)
        ->assertSee('Budi Privat (Member #'.$member->id.')')
        ->assertDontSee('084444444444')
        ->set('search', '084444444444')
        ->assertDontSee('PRIVATE-MEMBER');

    $this->get(route('transactions.show', $transaction))
        ->assertOk()
        ->assertSee('Budi Privat (Member #'.$member->id.')')
        ->assertDontSee('084444444444');
});

test('member history export matches multiple members and all dates', function (string $format) {
    $members = Member::factory()->count(3)->create();
    memberHistoryTransaction($members[0], 'EXPORT-OLD', ['created_at' => now()->subMonths(3)]);
    memberHistoryTransaction($members[1], 'EXPORT-TODAY');
    memberHistoryTransaction($members[2], 'EXPORT-OTHER');
    $params = ['memberIds' => [$members[0]->id, $members[1]->id], 'fromDate' => '', 'toDate' => ''];

    if ($format === 'excel') {
        Excel::fake();
        $this->get(route('transactions.excel', $params))->assertOk();
        Excel::assertDownloaded('laporan-transaksi_awal_akhir.xlsx', function ($export) use ($members) {
            $data = $export->array();
            expect(array_column(array_slice($data, 13), 0))->toBe(['EXPORT-OLD', 'EXPORT-TODAY']);
            expect($data[2][1])->toBe('Semua tanggal');
            expect($data[6][1])->toBe(2);
            expect($data[13][2])->toBe($members[0]->displayLabel(true));

            return true;
        });
    } else {
        $pdf = Mockery::mock(PDF::class);
        Barryvdh\DomPDF\Facade\Pdf::shouldReceive('loadView')->once()
            ->withArgs(function ($view, $data) {
                expect($view)->toBe('exports.reports.transactions-pdf');
                expect(array_column($data['rows'], 'code'))->toBe(['EXPORT-OLD', 'EXPORT-TODAY']);
                expect($data['summary']['txCount'])->toBe(2);
                expect($data['meta']['periodLabel'])->toBe('Semua tanggal');
                expect(view($view, $data)->render())->toContain('EXPORT-OLD', 'EXPORT-TODAY');

                return true;
            })->andReturn($pdf);
        $pdf->shouldReceive('setPaper')->once()->with('a4', 'landscape')->andReturnSelf();
        $pdf->shouldReceive('download')->once()->with('laporan-transaksi_awal_akhir.pdf')->andReturn(response('pdf'));
        $this->get(route('transactions.pdf', $params))->assertOk();
    }
})->with(['excel', 'pdf']);

test('export respects transaction PII permission and current member phone search', function (bool $showPii) {
    $this->user->syncRoles([]);
    $this->user->givePermissionTo(['dashboard.access', 'transactions.view']);
    $this->user->givePermissionTo($showPii ? 'transactions.pii.view' : 'members.pii.view');
    $member = Member::factory()->create(['name' => 'Ekspor Privat', 'phone' => '085555555555']);
    memberHistoryTransaction($member, 'EXPORT-PRIVATE');
    Excel::fake();

    $this->get(route('transactions.excel', ['memberIds' => [$member->id]]))->assertOk();
    Excel::assertDownloaded('laporan-transaksi_awal_akhir.xlsx', function ($export) use ($member, $showPii) {
        $row = $export->array()[13];
        expect($row[2])->toBe($member->displayLabel($showPii));
        expect($row[3])->toBe($showPii ? $member->phone : '');

        return true;
    });

    $this->get(route('transactions.excel', ['memberIds' => [$member->id], 'search' => $member->phone]))->assertOk();
    Excel::assertDownloaded('laporan-transaksi_awal_akhir.xlsx', function ($export) use ($showPii) {
        expect(array_slice($export->array(), 13))->toHaveCount($showPii ? 1 : 0);

        return true;
    });
})->with([true, false]);

test('member history cannot include another tenants members or transactions', function () {
    $firstTenant = Tenant::query()->create(['name' => 'First', 'slug' => 'first', 'domain' => 'first.test', 'is_active' => true]);
    $secondTenant = Tenant::query()->create(['name' => 'Second', 'slug' => 'second', 'domain' => 'second.test', 'is_active' => true]);

    try {
        $secondTenant->makeCurrent();
        $other = Member::factory()->create(['name' => 'Other Tenant Member']);
        memberHistoryTransaction($other, 'OTHER-TENANT-TX');
        $firstTenant->makeCurrent();
        $own = Member::factory()->create(['name' => 'Own Tenant Member']);
        memberHistoryTransaction($own, 'OWN-TENANT-TX');

        Livewire::withQueryParams(['memberIds' => [$own->id, $other->id]])
            ->test(TransactionsPage::class)
            ->assertSee('OWN-TENANT-TX')
            ->assertDontSee('OTHER-TENANT-TX')
            ->assertDontSee('Other Tenant Member')
            ->assertViewHas('stats', fn ($stats) => $stats['totalTransactions'] === 1);
    } finally {
        Tenant::forgetCurrent();
    }
});

test('POS labels distinguish members and selecting one fills the original customer fields', function () {
    Member::factory()->create(['name' => 'Sama POS', 'phone' => '086666666666']);
    $second = Member::factory()->create(['name' => 'Sama POS', 'phone' => '087777777777']);

    Livewire::test(PosPage::class)
        ->set('checkoutModalOpen', true)
        ->assertSee('Sama POS (086666666666)')
        ->assertSee('Sama POS (087777777777)')
        ->set('memberId', $second->id)
        ->assertSet('customerName', 'Sama POS')
        ->assertSet('customerPhone', '087777777777');
});

test('member history exports generate downloadable files', function (string $format, string $mime) {
    $member = Member::factory()->create();
    memberHistoryTransaction($member, 'DOWNLOAD-MEMBER');

    $this->get(route('transactions.'.$format, ['memberIds' => [$member->id]]))
        ->assertOk()
        ->assertHeader('Content-Type', $mime)
        ->assertHeader('Content-Disposition');
})->with([
    ['excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
    ['pdf', 'application/pdf'],
]);
