<?php

namespace App\Livewire\DiningTables;

use App\Models\DiningTable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class DiningTablesPage extends Component
{
    use WithPagination;

    public string $title = 'Meja';

    public string $search = '';

    public string $tableNumber = '';

    public ?int $editingTableId = null;

    public string $editingTableNumber = '';

    public string $bulkPrefix = 'T';

    public int $bulkStart = 1;

    public int $bulkEnd = 12;

    public int $bulkPadLength = 2;

    public function mount(): void
    {
        $this->authorize('dining_tables.view');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    protected function tablesQuery(): Builder
    {
        return DiningTable::query()
            ->withCount('transactions')
            ->when($this->search !== '', function (Builder $query): void {
                $term = '%'.$this->search.'%';
                $query->where('table_number', 'like', $term);
            })
            ->orderBy('table_number');
    }

    public function createTable(): void
    {
        $this->authorize('dining_tables.edit');

        $validated = $this->validate([
            'tableNumber' => ['required', 'string', 'max:50', Rule::unique('dining_tables', 'table_number')],
        ]);

        DiningTable::query()->create([
            'table_number' => trim($validated['tableNumber']),
        ]);

        $this->dispatch('toast', type: 'success', message: 'Meja berhasil ditambahkan.');
        $this->reset(['tableNumber']);
        $this->resetValidation();
        $this->resetPage();
    }

    public function startEditTable(int $tableId): void
    {
        $this->authorize('dining_tables.edit');

        $table = DiningTable::query()->findOrFail($tableId);

        $this->editingTableId = (int) $table->id;
        $this->editingTableNumber = (string) $table->table_number;
        $this->resetValidation();
    }

    public function cancelEditTable(): void
    {
        $this->editingTableId = null;
        $this->reset(['editingTableNumber']);
        $this->resetValidation();
    }

    public function updateTable(): void
    {
        $this->authorize('dining_tables.edit');

        if (! $this->editingTableId) {
            return;
        }

        $validated = $this->validate([
            'editingTableNumber' => [
                'required',
                'string',
                'max:50',
                Rule::unique('dining_tables', 'table_number')->ignore($this->editingTableId),
            ],
        ]);

        DiningTable::query()
            ->whereKey($this->editingTableId)
            ->update([
                'table_number' => trim($validated['editingTableNumber']),
            ]);

        $this->dispatch('toast', type: 'success', message: 'Meja berhasil diperbarui.');
        $this->cancelEditTable();
    }

    public function deleteTable(int $tableId): void
    {
        $this->authorize('dining_tables.edit');

        $table = DiningTable::query()->withCount('transactions')->findOrFail($tableId);

        if ((int) $table->transactions_count > 0) {
            $this->addError('tables', 'Meja tidak bisa dihapus karena sudah digunakan pada transaksi.');

            return;
        }

        $table->delete();

        $this->dispatch('toast', type: 'success', message: 'Meja berhasil dihapus.');

        if ($this->editingTableId === $tableId) {
            $this->cancelEditTable();
        }
    }

    public function regenerateQr(int $tableId): void
    {
        $this->authorize('dining_tables.edit');

        $table = DiningTable::query()->findOrFail($tableId);
        $table->touch();
        $this->dispatch('toast', type: 'success', message: 'QR berhasil dibuat ulang.');
    }

    public function generateMissingQr(): void
    {
        $this->authorize('dining_tables.edit');

        $tables = DiningTable::query()
            ->where(function (Builder $q): void {
                $q->whereNull('qr_value')->orWhereNull('image');
            })
            ->get();

        $count = 0;
        foreach ($tables as $table) {
            $table->touch();
            $count++;
        }

        $this->dispatch('toast', type: 'success', message: $count > 0 ? 'QR dibuat untuk '.$count.' meja.' : 'Semua meja sudah punya QR.');
    }

    public function bulkCreate(): void
    {
        $this->authorize('dining_tables.edit');

        $validated = $this->validate([
            'bulkPrefix' => ['required', 'string', 'max:10'],
            'bulkStart' => ['required', 'integer', 'min:0', 'max:99999'],
            'bulkEnd' => ['required', 'integer', 'min:0', 'max:99999', 'gte:bulkStart'],
            'bulkPadLength' => ['required', 'integer', 'min:0', 'max:6'],
        ]);

        $prefix = trim((string) $validated['bulkPrefix']);
        $start = (int) $validated['bulkStart'];
        $end = (int) $validated['bulkEnd'];
        $pad = (int) $validated['bulkPadLength'];

        $created = 0;
        $skipped = 0;

        for ($i = $start; $i <= $end; $i++) {
            $num = $pad > 0 ? str_pad((string) $i, $pad, '0', STR_PAD_LEFT) : (string) $i;
            $name = $prefix.$num;

            $row = DiningTable::query()->firstOrCreate(
                ['table_number' => $name],
                ['image' => null, 'qr_value' => null],
            );

            if ($row->wasRecentlyCreated) {
                $created++;
            } else {
                $skipped++;
            }
        }

        $this->reset(['bulkPrefix', 'bulkStart', 'bulkEnd', 'bulkPadLength']);
        $this->dispatch('toast', type: 'success', message: 'Bulk selesai: '.$created.' dibuat, '.$skipped.' dilewati.');
        $this->resetPage();
    }

    public function render(): View
    {
        $this->authorize('dining_tables.view');

        $tables = $this->tablesQuery()->paginate(15);

        return view('livewire.dining-tables.dining-tables-page', [
            'tables' => $tables,
        ])->layout('layouts.app', ['title' => $this->title]);
    }
}
