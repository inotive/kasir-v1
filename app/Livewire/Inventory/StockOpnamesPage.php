<?php

namespace App\Livewire\Inventory;

use App\Models\StockOpname;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class StockOpnamesPage extends Component
{
    use WithPagination;

    public string $title = 'Stock Opname';

    public string $search = '';

    public string $status = '';

    public function mount(): void
    {
        $user = auth()->user();
        if (
            ! $user
            || ! method_exists($user, 'can')
            || ! (
                $user->can('inventory.opnames.view')
                || $user->can('inventory.opnames.manage')
                || $user->can('inventory.manage')
                || $user->can('inventory.view')
            )
        ) {
            abort(403);
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    protected function opnamesQuery(): Builder
    {
        return StockOpname::query()
            ->withCount('items')
            ->when($this->search !== '', function (Builder $query): void {
                $term = '%'.$this->search.'%';
                $query->where('code', 'like', $term);
            })
            ->when($this->status !== '', fn (Builder $query) => $query->where('status', $this->status))
            ->orderByDesc('counted_at')
            ->orderByDesc('id');
    }

    public function render(): View
    {
        $opnames = $this->opnamesQuery()->paginate(15);

        $user = auth()->user();
        $canCreate = (bool) (($user && method_exists($user, 'can')) ? ($user->can('inventory.opnames.create') || $user->can('inventory.opnames.manage') || $user->can('inventory.manage')) : false);

        return view('livewire.inventory.stock-opnames-page', [
            'opnames' => $opnames,
            'canCreate' => $canCreate,
        ])->layout('layouts.app', ['title' => $this->title]);
    }
}
