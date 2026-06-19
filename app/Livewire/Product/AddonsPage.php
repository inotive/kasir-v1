<?php

namespace App\Livewire\Product;

use App\Models\Addon;
use App\Models\AddonCategory;
use App\Models\TransactionItemAddon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class AddonsPage extends Component
{
    use WithPagination;

    public string $title = 'Add-on';

    public string $tab = 'addons';

    public string $search = '';

    public ?int $filterCategoryId = null;

    public string $sortField = 'created_at';

    public bool $sortAsc = false;

    public int $perPage = 10;

    // Addon create/edit modal
    public bool $addonModalOpen = false;

    public ?int $editingAddonId = null;

    public string $formName = '';

    public ?int $formAddonCategoryId = null;

    public string $formPrice = '';

    public bool $formIsAvailable = true;

    // Category tab
    public string $categorySearch = '';

    public bool $createCategoryModalOpen = false;

    public string $categoryName = '';

    public ?int $editingCategoryId = null;

    public string $editingCategoryName = '';

    public function setTab(string $tab): void
    {
        if (! in_array($tab, ['addons', 'addon_categories'], true)) {
            return;
        }

        $this->tab = $tab;
        $this->resetValidation();

        if ($tab === 'addons') {
            return;
        }

        $this->search = '';
        $this->filterCategoryId = null;
        $this->sortField = 'created_at';
        $this->sortAsc = false;
        $this->resetPage();
    }

    // ─── Add-on Category Methods ───────────────────────────

    public function openCreateCategoryModal(): void
    {
        $this->categoryName = '';
        $this->resetValidation();
        $this->createCategoryModalOpen = true;
    }

    public function closeCreateCategoryModal(): void
    {
        $this->createCategoryModalOpen = false;
        $this->resetValidation();
    }

    public function createCategory(): void
    {
        $validated = $this->validate([
            'categoryName' => [
                'required',
                'string',
                'max:255',
                Rule::unique('addon_categories', 'name')->whereNull('deleted_at'),
            ],
        ]);

        AddonCategory::query()->create([
            'name' => $validated['categoryName'],
        ]);

        $this->categoryName = '';
        $this->createCategoryModalOpen = false;
        $this->dispatch('toast', type: 'success', message: 'Kategori add-on berhasil ditambahkan.');
    }

    public function startEditCategory(int $categoryId): void
    {
        $category = AddonCategory::query()->findOrFail($categoryId);

        $this->editingCategoryId = (int) $category->id;
        $this->editingCategoryName = (string) $category->name;
        $this->resetValidation();
    }

    public function cancelEditCategory(): void
    {
        $this->editingCategoryId = null;
        $this->editingCategoryName = '';
    }

    public function updateCategory(): void
    {
        if (! $this->editingCategoryId) {
            return;
        }

        $validated = $this->validate([
            'editingCategoryName' => [
                'required',
                'string',
                'max:255',
                Rule::unique('addon_categories', 'name')
                    ->whereNull('deleted_at')
                    ->ignore($this->editingCategoryId),
            ],
        ]);

        AddonCategory::query()
            ->whereKey($this->editingCategoryId)
            ->update(['name' => $validated['editingCategoryName']]);

        $this->cancelEditCategory();
        $this->dispatch('toast', type: 'success', message: 'Kategori add-on berhasil diperbarui.');
    }

    public function deleteCategory(int $categoryId): void
    {
        $category = AddonCategory::query()->findOrFail($categoryId);

        $used = Addon::query()
            ->where('addon_category_id', $categoryId)
            ->exists();

        if ($used) {
            $this->dispatch('toast', type: 'error', message: 'Kategori tidak bisa dihapus karena masih digunakan oleh add-on.');

            return;
        }

        $category->delete();

        if ($this->editingCategoryId === $categoryId) {
            $this->cancelEditCategory();
        }

        $this->dispatch('toast', type: 'success', message: 'Kategori add-on berhasil dihapus.');
    }

    // ─── Add-on Methods ────────────────────────────────────

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterCategoryId(): void
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

    public function openAddonModal(): void
    {
        $this->editingAddonId = null;
        $this->formName = '';
        $this->formAddonCategoryId = null;
        $this->formPrice = '';
        $this->formIsAvailable = true;
        $this->resetValidation();
        $this->addonModalOpen = true;
    }

    public function closeAddonModal(): void
    {
        $this->addonModalOpen = false;
        $this->resetValidation();
    }

    public function startEditAddon(int $addonId): void
    {
        $addon = Addon::query()->findOrFail($addonId);

        $this->editingAddonId = (int) $addon->id;
        $this->formName = (string) $addon->name;
        $this->formAddonCategoryId = (int) $addon->addon_category_id;
        $this->formPrice = (string) $addon->price;
        $this->formIsAvailable = (bool) $addon->is_available;
        $this->resetValidation();
        $this->addonModalOpen = true;
    }

    public function storeAddon(): void
    {
        $validated = $this->validate([
            'formName' => [
                'required',
                'string',
                'max:255',
            ],
            'formAddonCategoryId' => [
                'required',
                'exists:addon_categories,id',
            ],
            'formPrice' => [
                'required',
                'integer',
                'min:0',
            ],
        ]);

        Addon::query()->create([
            'name' => $validated['formName'],
            'addon_category_id' => (int) $validated['formAddonCategoryId'],
            'price' => (int) $validated['formPrice'],
            'is_available' => $this->formIsAvailable,
        ]);

        $this->closeAddonModal();
        $this->dispatch('toast', type: 'success', message: 'Add-on berhasil ditambahkan.');
    }

    public function updateAddon(): void
    {
        if (! $this->editingAddonId) {
            return;
        }

        $validated = $this->validate([
            'formName' => [
                'required',
                'string',
                'max:255',
            ],
            'formAddonCategoryId' => [
                'required',
                'exists:addon_categories,id',
            ],
            'formPrice' => [
                'required',
                'integer',
                'min:0',
            ],
        ]);

        Addon::query()
            ->whereKey($this->editingAddonId)
            ->update([
                'name' => $validated['formName'],
                'addon_category_id' => (int) $validated['formAddonCategoryId'],
                'price' => (int) $validated['formPrice'],
                'is_available' => $this->formIsAvailable,
            ]);

        $this->closeAddonModal();
        $this->dispatch('toast', type: 'success', message: 'Add-on berhasil diperbarui.');
    }

    public function toggleAvailability(int $addonId): void
    {
        $addon = Addon::query()->findOrFail($addonId);
        $addon->update(['is_available' => ! (bool) $addon->is_available]);
    }

    public function deleteAddon(int $addonId): void
    {
        $addon = Addon::query()->findOrFail($addonId);

        $used = TransactionItemAddon::query()
            ->where('addon_id', $addonId)
            ->exists();

        if ($used) {
            $this->dispatch('toast', type: 'error', message: 'Add-on tidak bisa dihapus karena sudah digunakan pada transaksi. Nonaktifkan jika ingin disembunyikan.');

            return;
        }

        $addon->delete();
        $this->dispatch('toast', type: 'success', message: 'Add-on berhasil dihapus.');
    }

    public function render(): View
    {
        $addonCategories = AddonCategory::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $categoriesList = AddonCategory::query()
            ->when($this->categorySearch !== '', function (Builder $q): void {
                $term = '%'.$this->categorySearch.'%';
                $q->where('name', 'like', $term);
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $addons = Addon::query()
            ->with('addonCategory')
            ->when($this->search !== '', function (Builder $q): void {
                $term = '%'.$this->search.'%';
                $q->where('name', 'like', $term);
            })
            ->when(! empty($this->filterCategoryId), fn (Builder $q) => $q->where('addon_category_id', $this->filterCategoryId))
            ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
            ->paginate($this->perPage);

        return view('livewire.product.addons-page', [
            'addonCategories' => $addonCategories,
            'categoriesList' => $categoriesList,
            'addons' => $addons,
        ])->layout('layouts.app', ['title' => $this->title]);
    }
}
