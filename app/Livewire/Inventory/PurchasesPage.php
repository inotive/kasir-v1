<?php

namespace App\Livewire\Inventory;

use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class PurchasesPage extends Component
{
    use WithPagination;

    public string $title = 'Pembelian';

    public string $search = '';

    public string $status = '';

    public ?int $supplierId = null;

    public function mount(): void
    {
        $user = auth()->user();
        if (
            ! $user
            || ! method_exists($user, 'can')
            || ! (
                $user->can('inventory.purchases.view')
                || $user->can('inventory.purchases.manage')
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

    public function updatedSupplierId(): void
    {
        $this->resetPage();
    }

    protected function purchasesQuery(): Builder
    {
        return Purchase::query()
            ->with(['supplier'])
            ->when($this->search !== '', function (Builder $query): void {
                $term = '%'.$this->search.'%';
                $query->where('code', 'like', $term);
            })
            ->when($this->status !== '', fn (Builder $query) => $query->where('status', $this->status))
            ->when(! empty($this->supplierId), fn (Builder $query) => $query->where('supplier_id', $this->supplierId))
            ->orderByDesc('purchased_at')
            ->orderByDesc('id');
    }

    public function render(): View
    {
        $suppliers = Supplier::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $purchases = $this->purchasesQuery()->paginate(15);

        $user = auth()->user();
        $canCreate = (bool) (($user && method_exists($user, 'can')) ? ($user->can('inventory.purchases.create') || $user->can('inventory.purchases.manage') || $user->can('inventory.manage')) : false);

        return view('livewire.inventory.purchases-page', [
            'suppliers' => $suppliers,
            'purchases' => $purchases,
            'canCreate' => $canCreate,
        ])->layout('layouts.app', ['title' => $this->title]);
    }
}
