<?php

namespace App\Livewire\Inventory;

use App\Models\Ingredient;
use App\Models\InventoryMovement;
use App\Models\Purchase;
use App\Models\StockOpname;
use App\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class StockCardPage extends Component
{
    use WithPagination;

    public string $title = 'Kartu Stok';

    #[Url]
    public ?int $ingredientId = null;

    public ?string $fromDate = null;

    public ?string $toDate = null;

    public string $rangePreset = 'today';

    public function mount(?int $ingredientId = null): void
    {
        $user = auth()->user();
        if (
            ! $user
            || ! method_exists($user, 'can')
            || ! (
                $user->can('inventory.reports.view')
                || $user->can('inventory.view')
                || $user->can('inventory.manage')
            )
        ) {
            abort(403);
        }

        if ($ingredientId === null) {
            $fromQuery = request()->query('ingredientId');
            if ($fromQuery !== null && $fromQuery !== '') {
                $ingredientId = (int) $fromQuery;
            }
        }

        $this->ingredientId = $ingredientId;
        $this->setRange('today');
    }

    public function updatedIngredientId(): void
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

    protected function movementsQuery(): Builder
    {
        return InventoryMovement::query()
            ->with(['supplier'])
            ->when(
                ! empty($this->ingredientId),
                fn (Builder $q) => $q->where('ingredient_id', $this->ingredientId),
                fn (Builder $q) => $q->whereRaw('1 = 0'),
            )
            ->when($this->fromDate, function (Builder $q): void {
                $from = $this->fromDate;
                $q->where(function (Builder $w) use ($from): void {
                    $w->where(function (Builder $x) use ($from): void {
                        $x->whereNotNull('happened_at')->whereDate('happened_at', '>=', $from);
                    })->orWhere(function (Builder $x) use ($from): void {
                        $x->whereNull('happened_at')->whereDate('created_at', '>=', $from);
                    });
                });
            })
            ->when($this->toDate, function (Builder $q): void {
                $to = $this->toDate;
                $q->where(function (Builder $w) use ($to): void {
                    $w->where(function (Builder $x) use ($to): void {
                        $x->whereNotNull('happened_at')->whereDate('happened_at', '<=', $to);
                    })->orWhere(function (Builder $x) use ($to): void {
                        $x->whereNull('happened_at')->whereDate('created_at', '<=', $to);
                    });
                });
            })
            ->orderByRaw('COALESCE(happened_at, created_at) asc')
            ->orderBy('id');
    }

    protected function startingBalance(): float
    {
        if (! $this->ingredientId || ! $this->fromDate) {
            return 0.0;
        }

        $from = $this->fromDate;

        return (float) InventoryMovement::query()
            ->where('ingredient_id', $this->ingredientId)
            ->where(function (Builder $q) use ($from): void {
                $q->where(function (Builder $x) use ($from): void {
                    $x->whereNotNull('happened_at')->whereDate('happened_at', '<', $from);
                })->orWhere(function (Builder $x) use ($from): void {
                    $x->whereNull('happened_at')->whereDate('created_at', '<', $from);
                });
            })
            ->sum('quantity');
    }

    protected function startingValue(): float
    {
        if (! $this->ingredientId || ! $this->fromDate) {
            return 0.0;
        }

        $from = $this->fromDate;

        return (float) InventoryMovement::query()
            ->where('ingredient_id', $this->ingredientId)
            ->where(function (Builder $q) use ($from): void {
                $q->where(function (Builder $x) use ($from): void {
                    $x->whereNotNull('happened_at')->whereDate('happened_at', '<', $from);
                })->orWhere(function (Builder $x) use ($from): void {
                    $x->whereNull('happened_at')->whereDate('created_at', '<', $from);
                });
            })
            ->sum(DB::raw('quantity * COALESCE(unit_cost, 0)'));
    }

    public function render(): View
    {
        $ingredients = Ingredient::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'unit']);

        $selectedIngredient = null;
        if (! empty($this->ingredientId)) {
            $selectedIngredient = Ingredient::query()
                ->whereKey($this->ingredientId)
                ->first(['id', 'name', 'unit']);
        }

        $query = $this->movementsQuery();
        $movements = (clone $query)->paginate(30);

        $startingBalance = $this->startingBalance();
        $startingValue = $this->startingValue();

        $running = $startingBalance;
        $runningValue = $startingValue;
        $pagePrefix = 0.0;
        $pagePrefixValue = 0.0;

        $offset = ((int) $movements->currentPage() - 1) * (int) $movements->perPage();
        if ($offset > 0) {
            $firstRow = (clone $query)
                ->skip($offset)
                ->take(1)
                ->first(['id', 'happened_at', 'created_at']);

            if ($firstRow) {
                $firstWhen = $firstRow->happened_at ?? $firstRow->created_at;
                $firstId = (int) $firstRow->id;

                $pagePrefix = (float) (clone $query)
                    ->where(function (Builder $q) use ($firstWhen, $firstId): void {
                        $q->whereRaw('COALESCE(happened_at, created_at) < ?', [$firstWhen])
                            ->orWhere(function (Builder $w) use ($firstWhen, $firstId): void {
                                $w->whereRaw('COALESCE(happened_at, created_at) = ?', [$firstWhen])
                                    ->where('id', '<', $firstId);
                            });
                    })
                    ->sum('quantity');

                $pagePrefixValue = (float) (clone $query)
                    ->where(function (Builder $q) use ($firstWhen, $firstId): void {
                        $q->whereRaw('COALESCE(happened_at, created_at) < ?', [$firstWhen])
                            ->orWhere(function (Builder $w) use ($firstWhen, $firstId): void {
                                $w->whereRaw('COALESCE(happened_at, created_at) = ?', [$firstWhen])
                                    ->where('id', '<', $firstId);
                            });
                    })
                    ->sum(DB::raw('quantity * COALESCE(unit_cost, 0)'));
            }
        }

        $running += $pagePrefix;
        $runningValue += $pagePrefixValue;
        $balances = [];
        $values = [];
        $runningValues = [];

        foreach ($movements as $movement) {
            $running += (float) $movement->quantity;
            $balances[$movement->id] = $running;

            $deltaValue = (float) $movement->quantity * (float) ($movement->unit_cost ?? 0);
            $runningValue += $deltaValue;
            $values[$movement->id] = $deltaValue;
            $runningValues[$movement->id] = $runningValue;
        }

        $refGroups = [];
        foreach ($movements as $movement) {
            $type = (string) ($movement->reference_type ?? '');
            $id = (int) ($movement->reference_id ?? 0);
            if ($type === '' || $id <= 0) {
                continue;
            }
            $refGroups[$type][] = $id;
        }

        $refCodes = [
            'purchases' => [],
            'stock_opnames' => [],
            'transactions' => [],
        ];

        if (! empty($refGroups['purchases'])) {
            $ids = array_values(array_unique(array_map('intval', $refGroups['purchases'])));
            $refCodes['purchases'] = Purchase::query()
                ->whereIn('id', $ids)
                ->pluck('code', 'id')
                ->map(fn ($value) => (string) $value)
                ->all();
        }

        if (! empty($refGroups['stock_opnames'])) {
            $ids = array_values(array_unique(array_map('intval', $refGroups['stock_opnames'])));
            $refCodes['stock_opnames'] = StockOpname::query()
                ->whereIn('id', $ids)
                ->pluck('code', 'id')
                ->map(fn ($value) => (string) $value)
                ->all();
        }

        if (! empty($refGroups['transactions'])) {
            $ids = array_values(array_unique(array_map('intval', $refGroups['transactions'])));
            $refCodes['transactions'] = Transaction::query()
                ->whereIn('id', $ids)
                ->pluck('code', 'id')
                ->map(fn ($value) => (string) $value)
                ->all();
        }

        return view('livewire.inventory.stock-card-page', [
            'ingredients' => $ingredients,
            'selectedIngredient' => $selectedIngredient,
            'movements' => $movements,
            'startingBalance' => $startingBalance,
            'startingValue' => $startingValue,
            'balances' => $balances,
            'values' => $values,
            'runningValues' => $runningValues,
            'refCodes' => $refCodes,
        ])->layout('layouts.app', ['title' => $this->title]);
    }
}
