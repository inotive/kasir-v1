<?php

namespace App\Livewire\Reports;

use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class ManualDiscountReportPage extends Component
{
    use WithPagination;

    public string $title = 'Laporan Diskon Manual';

    public ?string $from = null;

    public ?string $to = null;

    public string $rangePreset = '30d';

    public ?int $cashierId = null;

    public function mount(): void
    {
        $this->authorizePermission('reports.sales');

        $this->setRange('30d');
    }

    public function updatedFrom(): void
    {
        $this->resetPage();
    }

    public function updatedTo(): void
    {
        $this->resetPage();
    }

    public function updatedCashierId(): void
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

        $this->from = $from;
        $this->to = $to;
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

        $this->from = $from->format('Y-m-d');
        $this->to = $to->format('Y-m-d');
        $this->rangePreset = $preset;
        $this->resetPage();
    }

    protected function baseQuery(): Builder
    {
        return Transaction::query()
            ->with(['manualDiscountByUser'])
            ->where('manual_discount_amount', '>', 0)
            ->when($this->from, fn (Builder $q) => $q->whereDate('created_at', '>=', (string) $this->from))
            ->when($this->to, fn (Builder $q) => $q->whereDate('created_at', '<=', (string) $this->to))
            ->when($this->cashierId, fn (Builder $q) => $q->where('manual_discount_by_user_id', (int) $this->cashierId));
    }

    public function render(): View
    {
        $this->authorizePermission('reports.sales');

        $rows = $this->baseQuery()->orderByDesc('created_at')->paginate(25);
        $totalDiscount = (float) $this->baseQuery()->sum('manual_discount_amount');

        return view('livewire.reports.manual-discount-report-page', [
            'rows' => $rows,
            'totalDiscount' => $totalDiscount,
            'cashiers' => User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ])->layout('layouts.app', ['title' => $this->title]);
    }

    private function authorizePermission(string $permission): void
    {
        $permission = trim($permission);
        if ($permission === '') {
            abort(403);
        }

        $user = auth()->user();
        if (! $user || ! method_exists($user, 'can') || ! $user->can($permission)) {
            abort(403);
        }
    }
}
