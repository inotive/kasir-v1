<?php

namespace App\Livewire\Transaction;

use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Services\Printing\PosPrintPayloadService;
use App\Support\Finance\NetSales;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class TransactionsPage extends Component
{
    use WithPagination;

    public string $title = 'Riwayat Transaksi';

    public string $search = '';

    public ?string $fromDate = null;

    public ?string $toDate = null;

    public string $rangePreset = 'today';

    public string $paymentStatus = '';

    public string $paymentMethod = '';

    public string $orderType = '';

    public string $sortField = 'created_at';

    public bool $sortAsc = false;

    public int $perPage = 15;

    public function mount(): void
    {
        $this->authorize('transactions.view');
        $this->setRange('today');
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

    public function updatedPaymentStatus(): void
    {
        $this->resetPage();
    }

    public function updatedPaymentMethod(): void
    {
        $this->resetPage();
    }

    public function updatedOrderType(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortAsc = ! $this->sortAsc;
        } else {
            $this->sortField = $field;
            $this->sortAsc = true;
        }

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
        $canViewPii = auth()->user()?->can('transactions.pii.view') ?? false;

        $query = Transaction::query()
            ->with(['member', 'diningTable'])
            ->when($this->search !== '', function (Builder $query) use ($canViewPii): void {
                $term = '%'.$this->search.'%';
                $query->where(function (Builder $q) use ($term, $canViewPii): void {
                    $q->where('code', 'like', $term)
                        ->orWhere('name', 'like', $term);

                    if ($canViewPii) {
                        $q->orWhere('phone', 'like', $term)
                            ->orWhere('email', 'like', $term)
                            ->orWhere('external_id', 'like', $term);
                    }
                });
            })
            ->when($this->paymentStatus !== '', fn (Builder $query) => $query->where('payment_status', $this->paymentStatus))
            ->when($this->paymentMethod !== '', fn (Builder $query) => $query->where('payment_method', $this->paymentMethod))
            ->when($this->orderType !== '', fn (Builder $query) => $query->where('order_type', $this->orderType));

        if ($this->fromDate) {
            $query->whereDate('created_at', '>=', $this->fromDate);
        }

        if ($this->toDate) {
            $query->whereDate('created_at', '<=', $this->toDate);
        }

        return $query;
    }

    protected function paymentStatusOptions(): array
    {
        return Transaction::query()
            ->select('payment_status')
            ->distinct()
            ->orderBy('payment_status')
            ->pluck('payment_status')
            ->map(fn ($value) => (string) $value)
            ->filter(fn ($value) => $value !== '')
            ->values()
            ->all();
    }

    protected function paymentMethodOptions(): array
    {
        return Transaction::query()
            ->select('payment_method')
            ->distinct()
            ->orderBy('payment_method')
            ->pluck('payment_method')
            ->map(fn ($value) => (string) $value)
            ->filter(fn ($value) => $value !== '')
            ->values()
            ->all();
    }

    protected function orderTypeOptions(): array
    {
        return ['take_away', 'dine_in'];
    }

    protected function stats(): array
    {
        $base = $this->baseQuery();

        $totalTransactions = (int) (clone $base)->count();

        $paidBase = (clone $base)->whereIn('payment_status', NetSales::postedPaymentStatuses());
        $paidCount = (int) (clone $paidBase)->count();
        $sub = DB::table('transactions as t')
            ->join('transaction_items as ti', 't.id', '=', 'ti.transaction_id')
            ->whereIn('t.id', (clone $paidBase)->select('id'))
            ->selectRaw('t.id as tx_id')
            ->selectRaw('COALESCE(t.refunded_amount, 0) as refunded_amount')
            ->selectRaw('COALESCE(SUM('.NetSales::itemNetExpr('ti').'), 0) as item_net')
            ->groupBy('tx_id', 'refunded_amount');

        $totalRevenue = (int) round((float) (DB::query()
            ->fromSub($sub, 'x')
            ->selectRaw('COALESCE(SUM('.NetSales::netPerTransactionExpr('x.item_net', 'x.refunded_amount').'), 0) as revenue')
            ->value('revenue') ?? 0));
        $avgRevenue = $paidCount > 0 ? (int) round($totalRevenue / $paidCount) : 0;

        $itemsBase = (clone $base)->whereIn('payment_status', ['paid', 'settlement', 'capture', 'success', 'partial_refund']);
        $totalItemsSold = (int) TransactionItem::query()
            ->whereIn('transaction_id', (clone $itemsBase)->select('id'))
            ->sum('quantity');

        return [
            'totalTransactions' => $totalTransactions,
            'totalRevenue' => $totalRevenue,
            'avgRevenue' => $avgRevenue,
            'totalItemsSold' => $totalItemsSold,
        ];
    }

    private function buildPrintPayload(int $transactionId): ?array
    {
        return app(PosPrintPayloadService::class)->build($transactionId);
    }

    public function printTransaction(int $transactionId): void
    {
        $actor = auth()->user();
        if (! $actor || ! $actor->can('transactions.print')) {
            $this->dispatch('toast', type: 'error', message: 'Anda tidak punya akses untuk mencetak struk.');

            return;
        }

        $payload = $this->buildPrintPayload($transactionId);
        if (! $payload) {
            $this->dispatch('toast', type: 'error', message: 'Transaksi tidak ditemukan.');

            return;
        }

        $this->dispatch('pos-print-modal', payload: $payload, context: 'transactions');
    }

    public function render(): View
    {
        $this->authorize('transactions.view');

        $paymentStatusOptions = $this->paymentStatusOptions();
        $paymentMethodOptions = $this->paymentMethodOptions();

        $transactions = $this->baseQuery()
            ->withCount('transactionItems')
            ->withSum('transactionItems as items_quantity_sum', 'quantity')
            ->withSum('transactionItems as hpp_total_sum', 'hpp_total')
            ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
            ->paginate($this->perPage);

        $stats = $this->stats();

        return view('livewire.transactions.transactions-page', [
            'transactions' => $transactions,
            'paymentStatusOptions' => $paymentStatusOptions,
            'paymentMethodOptions' => $paymentMethodOptions,
            'orderTypeOptions' => $this->orderTypeOptions(),
            'stats' => $stats,
        ])->layout('layouts.app', ['title' => $this->title]);
    }
}
