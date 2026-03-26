<?php

namespace App\Livewire\Reports;

use App\Models\OperatingExpense;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class OperatingExpensesPage extends Component
{
    use WithPagination;

    public string $title = 'Beban Operasional';

    public string $search = '';

    public ?string $fromDate = null;

    public ?string $toDate = null;

    public string $rangePreset = '30d';

    public bool $formModalOpen = false;

    public bool $deleteConfirmOpen = false;

    public ?int $editingId = null;

    public ?int $deletingId = null;

    public ?string $expenseDate = null;

    public string $category = '';

    public ?int $amount = null;

    public ?string $note = null;

    public function mount(): void
    {
        $this->authorizeAny(['reports.sales', 'reports.expenses.manage']);

        $this->expenseDate = CarbonImmutable::now()->format('Y-m-d');
        $this->setRange('30d');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFromDate(): void
    {
        $this->resetPage();
    }

    public function updatedToDate(): void
    {
        $this->resetPage();
    }

    public function setTransactionsRange(?string $from, ?string $to): void
    {
        if (! $from || ! $to) {
            return;
        }

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $this->fromDate = $from;
        $this->toDate = $to;
        $this->rangePreset = 'custom';
        $this->resetPage();
    }

    public function setRange(string $preset): void
    {
        $today = CarbonImmutable::now();

        if ($preset === 'today') {
            $from = $today;
            $to = $today;
        } elseif ($preset === '7d') {
            $from = $today->subDays(6);
            $to = $today;
        } elseif ($preset === '30d') {
            $from = $today->subDays(29);
            $to = $today;
        } elseif ($preset === 'custom') {
            return;
        } else {
            return;
        }

        $this->fromDate = $from->format('Y-m-d');
        $this->toDate = $to->format('Y-m-d');
        $this->rangePreset = $preset;
        $this->resetPage();
    }

    protected function baseQuery(): Builder
    {
        return OperatingExpense::query()
            ->when($this->search !== '', function (Builder $q): void {
                $term = '%'.$this->search.'%';
                $q->where(function (Builder $w) use ($term): void {
                    $w->where('category', 'like', $term)
                        ->orWhere('note', 'like', $term);
                });
            })
            ->when($this->fromDate, fn (Builder $q) => $q->whereDate('expense_date', '>=', (string) $this->fromDate))
            ->when($this->toDate, fn (Builder $q) => $q->whereDate('expense_date', '<=', (string) $this->toDate));
    }

    public function openCreate(): void
    {
        $this->authorize('reports.expenses.manage');

        $this->reset(['editingId', 'category', 'amount', 'note']);
        if (! $this->expenseDate) {
            $this->expenseDate = CarbonImmutable::now()->format('Y-m-d');
        }
        $this->resetValidation();
        $this->formModalOpen = true;
    }

    public function openEdit(int $id): void
    {
        $this->authorize('reports.expenses.manage');

        $row = OperatingExpense::query()->findOrFail($id);

        $this->editingId = (int) $row->id;
        $this->expenseDate = optional($row->expense_date)->format('Y-m-d');
        $this->category = (string) $row->category;
        $this->amount = (int) $row->amount;
        $this->note = $row->note;
        $this->resetValidation();
        $this->formModalOpen = true;
    }

    public function closeForm(): void
    {
        $this->formModalOpen = false;
        $this->resetValidation();
    }

    public function save(): void
    {
        $this->authorize('reports.expenses.manage');

        $validated = $this->validate([
            'expenseDate' => ['required', 'date'],
            'category' => ['required', 'string', 'max:100'],
            'amount' => ['required', 'integer', 'min:0'],
            'note' => ['nullable', 'string'],
        ]);

        $payload = [
            'expense_date' => (string) $validated['expenseDate'],
            'category' => trim((string) $validated['category']),
            'amount' => (int) $validated['amount'],
            'note' => $validated['note'],
            'created_by_user_id' => auth()->id(),
        ];

        if ($this->editingId) {
            OperatingExpense::query()->whereKey($this->editingId)->update($payload);
            $this->dispatch('toast', type: 'success', message: 'Beban diperbarui.');
        } else {
            OperatingExpense::query()->create($payload);
            $this->dispatch('toast', type: 'success', message: 'Beban ditambahkan.');
        }

        $this->formModalOpen = false;
        $this->reset(['editingId', 'category', 'amount', 'note']);
    }

    public function openDeleteConfirm(int $id): void
    {
        $this->authorize('reports.expenses.manage');

        $this->deletingId = $id;
        $this->deleteConfirmOpen = true;
    }

    public function closeDeleteConfirm(): void
    {
        $this->deleteConfirmOpen = false;
        $this->deletingId = null;
    }

    public function delete(): void
    {
        $this->authorize('reports.expenses.manage');

        $id = (int) ($this->deletingId ?? 0);
        if ($id <= 0) {
            return;
        }

        OperatingExpense::query()->whereKey($id)->delete();
        $this->dispatch('toast', type: 'success', message: 'Beban dihapus.');
        $this->closeDeleteConfirm();
    }

    public function render(): View
    {
        $this->authorizeAny(['reports.sales', 'reports.expenses.manage']);

        $rows = $this->baseQuery()->orderByDesc('expense_date')->orderByDesc('id')->paginate(25);
        $total = (float) $this->baseQuery()->sum('amount');

        $suggestedCategories = DB::table('operating_expenses')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->select('category')
            ->selectRaw('COUNT(*) as total_rows')
            ->groupBy('category')
            ->orderByDesc('total_rows')
            ->orderBy('category')
            ->limit(12)
            ->pluck('category')
            ->map(fn ($v) => trim((string) $v))
            ->filter(fn ($v) => $v !== '')
            ->values()
            ->all();

        $userIds = $rows->pluck('created_by_user_id')->filter()->unique()->map(fn ($v) => (int) $v)->all();
        $users = $userIds === []
            ? []
            : User::query()->whereIn('id', $userIds)->pluck('name', 'id')->map(fn ($v) => (string) $v)->all();

        return view('livewire.reports.operating-expenses-page', [
            'rows' => $rows,
            'total' => $total,
            'users' => $users,
            'suggestedCategories' => $suggestedCategories,
        ])->layout('layouts.app', ['title' => $this->title]);
    }

    private function authorizeAny(array $permissions): void
    {
        $user = auth()->user();
        if (! $user || ! method_exists($user, 'can')) {
            abort(403);
        }

        foreach ($permissions as $permission) {
            $permission = trim((string) $permission);
            if ($permission !== '' && $user->can($permission)) {
                return;
            }
        }

        abort(403);
    }
}
