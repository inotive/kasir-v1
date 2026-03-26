<?php

namespace App\Livewire\Product;

use App\Models\Category;
use App\Models\Product;
use App\Models\TransactionItem;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class ProductsPage extends Component
{
    use WithPagination;

    public string $title = 'Produk';

    public string $tab = 'products';

    public string $search = '';

    public ?int $categoryId = null;

    public string $sortField = 'created_at';

    public bool $sortAsc = false;

    public int $perPage = 10;

    public bool $createCategoryModalOpen = false;

    public string $categoryName = '';

    public string $categorySearch = '';

    public ?int $editingCategoryId = null;

    public string $editingCategoryName = '';

    public function setTab(string $tab): void
    {
        if (! in_array($tab, ['products', 'categories'], true)) {
            return;
        }

        if ($tab === 'categories') {
            $this->authorize('categories.view');
        }

        $this->tab = $tab;
        $this->resetValidation();

        if ($tab === 'products') {
            return;
        }

        $this->resetProductFilters();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryId(): void
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

    public function deleteProduct(int $productId): void
    {
        $this->authorize('products.delete');

        $product = Product::query()->findOrFail($productId);
        $image = (string) ($product->image ?? '');

        $used = TransactionItem::query()
            ->where('product_id', $productId)
            ->exists();

        if ($used) {
            $this->dispatch('toast', type: 'error', message: 'Produk tidak bisa dihapus karena sudah digunakan pada transaksi. Nonaktifkan produk jika ingin disembunyikan.');

            return;
        }

        $product->delete();

        if ($image !== '' && ! str_starts_with($image, '/') && ! str_starts_with($image, 'http')) {
            Storage::disk('public')->delete($image);
        }
    }

    public function createCategory(): void
    {
        $this->authorize('categories.create');

        $validated = $this->validate([
            'categoryName' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->whereNull('deleted_at'),
            ],
        ]);

        Category::query()->create([
            'name' => $validated['categoryName'],
        ]);

        $this->categoryName = '';
        $this->createCategoryModalOpen = false;
        $this->dispatch('toast', type: 'success', message: 'Kategori berhasil ditambahkan.');
    }

    public function openCreateCategoryModal(): void
    {
        $this->authorize('categories.create');

        $this->categoryName = '';
        $this->resetValidation();
        $this->createCategoryModalOpen = true;
    }

    public function closeCreateCategoryModal(): void
    {
        $this->createCategoryModalOpen = false;
        $this->resetValidation();
    }

    public function startEditCategory(int $categoryId): void
    {
        $this->authorize('categories.edit');

        $category = Category::query()->findOrFail($categoryId);

        $this->editingCategoryId = (int) $category->id;
        $this->editingCategoryName = (string) $category->name;
        $this->resetValidation();
    }

    public function cancelEditCategory(): void
    {
        $this->editingCategoryId = null;
        $this->editingCategoryName = '';
        $this->resetValidation();
    }

    public function updateCategory(): void
    {
        $this->authorize('categories.edit');

        if (! $this->editingCategoryId) {
            return;
        }

        $validated = $this->validate([
            'editingCategoryName' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')
                    ->whereNull('deleted_at')
                    ->ignore($this->editingCategoryId),
            ],
        ]);

        Category::query()
            ->whereKey($this->editingCategoryId)
            ->update([
                'name' => $validated['editingCategoryName'],
            ]);

        $this->cancelEditCategory();
    }

    public function deleteCategory(int $categoryId): void
    {
        $this->authorize('categories.delete');

        $category = Category::query()->findOrFail($categoryId);

        $used = Product::query()
            ->where('category_id', $categoryId)
            ->exists();

        if ($used) {
            $this->addError('categoryName', 'Kategori tidak bisa dihapus karena masih digunakan oleh produk.');

            return;
        }

        $category->delete();

        if ($this->editingCategoryId === $categoryId) {
            $this->cancelEditCategory();
        }
    }

    protected function productsQuery(): Builder
    {
        return Product::query()
            ->with(['category'])
            ->with(['variants:id,product_id,price,price_afterdiscount,percent'])
            ->withCount('variants')
            ->withCount(['variants as variants_with_recipes_count' => fn (Builder $q) => $q->whereHas('recipes')])
            ->withMin('variants', 'price')
            ->withMax('variants', 'price')
            ->withMin('variants', 'hpp')
            ->withMax('variants', 'hpp')
            ->when($this->search !== '', function (Builder $query): void {
                $term = '%'.$this->search.'%';
                $query->where(function (Builder $q) use ($term): void {
                    $q->where('name', 'like', $term)
                        ->orWhere('description', 'like', $term);
                });
            })
            ->when(! empty($this->categoryId), fn (Builder $query) => $query->where('category_id', $this->categoryId));
    }

    private function resetProductFilters(): void
    {
        $this->search = '';
        $this->categoryId = null;
        $this->sortField = 'created_at';
        $this->sortAsc = false;
        $this->resetPage();
    }

    public function render(): View
    {
        $categories = Category::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $categoriesList = Category::query()
            ->when($this->categorySearch !== '', function (Builder $q): void {
                $term = '%'.$this->categorySearch.'%';
                $q->where('name', 'like', $term);
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $products = $this->tab === 'products'
            ? $this->productsQuery()
                ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
                ->paginate($this->perPage)
            : Product::query()->paginate($this->perPage);

        return view('livewire.products.products-page', [
            'categories' => $categories,
            'categoriesList' => $categoriesList,
            'products' => $products,
        ])->layout('layouts.app', ['title' => $this->title]);
    }
}
