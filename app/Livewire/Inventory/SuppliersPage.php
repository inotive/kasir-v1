<?php

namespace App\Livewire\Inventory;

use App\Models\Supplier;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class SuppliersPage extends Component
{
    use WithPagination;

    public string $title = 'Supplier';

    public string $search = '';

    public bool $createSupplierModalOpen = false;

    public string $name = '';

    public ?string $phone = null;

    public ?string $email = null;

    public ?string $address = null;

    public ?string $note = null;

    public ?int $editingSupplierId = null;

    public string $editingName = '';

    public ?string $editingPhone = null;

    public ?string $editingEmail = null;

    public ?string $editingAddress = null;

    public ?string $editingNote = null;

    public function mount(): void
    {
        $this->authorizeAny(['inventory.suppliers.view', 'inventory.suppliers.manage', 'inventory.manage', 'inventory.view']);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openCreateSupplierModal(): void
    {
        $this->authorizeAny(['inventory.suppliers.manage', 'inventory.manage']);

        $this->reset(['name', 'phone', 'email', 'address', 'note']);
        $this->resetValidation();
        $this->createSupplierModalOpen = true;
    }

    public function closeCreateSupplierModal(): void
    {
        $this->createSupplierModalOpen = false;
        $this->resetValidation();
    }

    public function createSupplier(): void
    {
        $this->authorizeAny(['inventory.suppliers.manage', 'inventory.manage']);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'note' => ['nullable', 'string'],
        ]);

        Supplier::query()->create($validated);

        $this->reset(['name', 'phone', 'email', 'address', 'note']);
        $this->createSupplierModalOpen = false;
        $this->dispatch('toast', type: 'success', message: 'Supplier berhasil ditambahkan.');
    }

    public function startEditSupplier(int $supplierId): void
    {
        $this->authorizeAny(['inventory.suppliers.manage', 'inventory.manage']);

        $supplier = Supplier::query()->findOrFail($supplierId);

        $this->editingSupplierId = (int) $supplier->id;
        $this->editingName = (string) $supplier->name;
        $this->editingPhone = $supplier->phone;
        $this->editingEmail = $supplier->email;
        $this->editingAddress = $supplier->address;
        $this->editingNote = $supplier->note;

        $this->resetValidation();
    }

    public function cancelEditSupplier(): void
    {
        $this->editingSupplierId = null;
        $this->reset(['editingName', 'editingPhone', 'editingEmail', 'editingAddress', 'editingNote']);
        $this->resetValidation();
    }

    public function updateSupplier(): void
    {
        $this->authorizeAny(['inventory.suppliers.manage', 'inventory.manage']);

        if (! $this->editingSupplierId) {
            return;
        }

        $validated = $this->validate([
            'editingName' => ['required', 'string', 'max:255'],
            'editingPhone' => ['nullable', 'string', 'max:255'],
            'editingEmail' => ['nullable', 'string', 'max:255'],
            'editingAddress' => ['nullable', 'string'],
            'editingNote' => ['nullable', 'string'],
        ]);

        Supplier::query()
            ->whereKey($this->editingSupplierId)
            ->update([
                'name' => $validated['editingName'],
                'phone' => $validated['editingPhone'],
                'email' => $validated['editingEmail'],
                'address' => $validated['editingAddress'],
                'note' => $validated['editingNote'],
            ]);

        $this->cancelEditSupplier();
    }

    public function toggleActive(int $supplierId): void
    {
        $this->authorizeAny(['inventory.suppliers.manage', 'inventory.manage']);

        $supplier = Supplier::query()->findOrFail($supplierId);
        $supplier->update(['is_active' => ! (bool) $supplier->is_active]);
    }

    public function deleteSupplier(int $supplierId): void
    {
        $this->authorizeAny(['inventory.suppliers.manage', 'inventory.manage']);

        $supplier = Supplier::query()->findOrFail($supplierId);
        $supplier->delete();

        if ($this->editingSupplierId === $supplierId) {
            $this->cancelEditSupplier();
        }
    }

    protected function suppliersQuery(): Builder
    {
        return Supplier::query()
            ->when($this->search !== '', function (Builder $query): void {
                $term = '%'.$this->search.'%';
                $query->where(function (Builder $q) use ($term): void {
                    $q->where('name', 'like', $term)
                        ->orWhere('phone', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
            })
            ->orderBy('name');
    }

    public function render(): View
    {
        $suppliers = $this->suppliersQuery()->paginate(15);

        $user = auth()->user();
        $canManage = (bool) (($user && method_exists($user, 'can')) ? ($user->can('inventory.suppliers.manage') || $user->can('inventory.manage')) : false);

        return view('livewire.inventory.suppliers-page', [
            'suppliers' => $suppliers,
            'canManage' => $canManage,
        ])->layout('layouts.app', ['title' => $this->title]);
    }

    private function authorizeAny(array $permissions): void
    {
        $user = auth()->user();
        if (! $user || ! method_exists($user, 'can')) {
            abort(403);
        }

        foreach ($permissions as $permission) {
            $permission = (string) $permission;
            if ($permission !== '' && $user->can($permission)) {
                return;
            }
        }

        abort(403);
    }
}
