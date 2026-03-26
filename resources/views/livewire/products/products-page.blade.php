<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Manajemen Produk</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Kelola produk dan kategori dalam satu halaman.</p>
        </div>

        <div class="grid grid-cols-2 justify-center rounded-lg border border-gray-200 bg-white p-1 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
            <button
                type="button"
                wire:click="setTab('products')"
                @class([
                    'rounded-md px-4 py-2 text-sm font-medium transition',
                    'bg-brand-500 text-white' => $tab === 'products',
                    'text-gray-700 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-white/[0.03]' => $tab !== 'products',
                ])
            >
                Produk
            </button>
            @can('categories.view')
                <button
                    type="button"
                    wire:click="setTab('categories')"
                    @class([
                        'rounded-md px-4 py-2 text-sm font-medium transition',
                        'bg-brand-500 text-white' => $tab === 'categories',
                        'text-gray-700 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-white/[0.03]' => $tab !== 'categories',
                    ])
                >
                    Kategori
                </button>
            @endcan
        </div>
    </div>

    @if ($tab === 'products')
        <x-ecommerce.product-table :products="$products" :categories="$categories" :sort-field="$sortField" :sort-asc="$sortAsc" />
    @else
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex flex-col justify-between gap-5 border-b border-gray-200 px-5 py-4 sm:flex-row sm:items-center dark:border-gray-800">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Daftar Kategori</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Tambahkan dan ubah kategori produk.</p>
                </div>

                <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center">
                    <div class="relative min-w-0 sm:w-[300px]">
                        <span class="absolute top-1/2 left-4 -translate-y-1/2 text-gray-500 dark:text-gray-400">
                            <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M3.04199 9.37363C3.04199 5.87693 5.87735 3.04199 9.37533 3.04199C12.8733 3.04199 15.7087 5.87693 15.7087 9.37363C15.7087 12.8703 12.8733 15.7053 9.37533 15.7053C5.87735 15.7053 3.04199 12.8703 3.04199 9.37363ZM9.37533 1.54199C5.04926 1.54199 1.54199 5.04817 1.54199 9.37363C1.54199 13.6991 5.04926 17.2053 9.37533 17.2053C11.2676 17.2053 13.0032 16.5344 14.3572 15.4176L17.1773 18.238C17.4702 18.5309 17.945 18.5309 18.2379 18.238C18.5308 17.9451 18.5309 17.4703 18.238 17.1773L15.4182 14.3573C16.5367 13.0033 17.2087 11.2669 17.2087 9.37363C17.2087 5.04817 13.7014 1.54199 9.37533 1.54199Z" fill="" />
                            </svg>
                        </span>
                        <input wire:model.live.debounce.400ms="categorySearch" type="text" placeholder="Cari kategori..." class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent py-2.5 pr-4 pl-11 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                    </div>
                    @can('categories.create')
                        <button type="button" wire:click="openCreateCategoryModal" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex h-11 items-center justify-center rounded-lg px-4 text-sm font-semibold text-white transition">
                            Tambah Kategori
                        </button>
                    @endcan
                </div>
            </div>

            <div class="custom-scrollbar overflow-x-auto">
                <table class="w-full table-auto">
                    <thead>
                        <tr class="border-b border-gray-200 dark:divide-gray-800 dark:border-gray-800">
                            <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Nama</th>
                            @can(['categories.edit', 'categories.delete'])
                                <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Aksi</th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($categoriesList as $category)
                            <tr>
                                <td class="px-5 py-4">
                                    @if ($editingCategoryId === (int) $category->id)
                                        <div class="max-w-[420px]">
                                            <input
                                                wire:model.live="editingCategoryName"
                                                type="text"
                                                aria-invalid="{{ $errors->has('editingCategoryName') ? 'true' : 'false' }}"
                                                aria-describedby="{{ $errors->has('editingCategoryName') ? 'error-editingCategoryName' : '' }}"
                                                class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                            />
                                            <x-common.input-error for="editingCategoryName" />
                                        </div>
                                    @else
                                        <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $category->name }}</p>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        @if ($editingCategoryId === (int) $category->id)
                                            <button type="button" wire:click="updateCategory" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-3 py-2 text-xs font-medium text-white transition">
                                                Simpan
                                            </button>
                                            <button type="button" wire:click="cancelEditCategory" class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                                                Batal
                                            </button>
                                        @else
                                            @can('categories.edit')
                                                <button type="button" wire:click="startEditCategory({{ (int) $category->id }})" class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                                                    Edit
                                                </button>
                                            @endcan
                                            @can('categories.delete')
                                                <button type="button" x-on:click.prevent="$dispatch('confirm', { message: 'Hapus kategori ini?', method: 'deleteCategory', args: [{{ (int) $category->id }}] })" class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                                                    Hapus
                                                </button>
                                            @endcan
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <x-common.empty-table-row colspan="2" message="Kategori belum ada." />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($createCategoryModalOpen)
            <div class="fixed inset-0 z-[100000] flex items-center justify-center p-4" aria-modal="true" role="dialog">
                <div class="absolute inset-0 bg-black/50" wire:click="closeCreateCategoryModal"></div>
                <div class="relative w-full max-w-xl overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                        <div>
                            <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Tambah Kategori</h3>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Buat kategori produk baru.</p>
                        </div>
                        <button type="button" wire:click="closeCreateCategoryModal" class="text-sm font-medium text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
                            Tutup
                        </button>
                    </div>
                    <form wire:submit="createCategory" class="space-y-4 p-5">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Nama Kategori</label>
                            <input wire:model.live="categoryName" type="text" aria-invalid="{{ $errors->has('categoryName') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('categoryName') ? 'error-categoryName' : '' }}" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" placeholder="Nama kategori" />
                            <x-common.input-error for="categoryName" />
                        </div>
                        <div class="flex items-center justify-end gap-2">
                            <button type="button" wire:click="closeCreateCategoryModal" class="shadow-theme-xs inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                                Batal
                            </button>
                            <button type="submit" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex h-11 items-center justify-center rounded-lg px-4 text-sm font-semibold text-white transition">
                                Simpan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    @endif

    <x-common.confirm-modal confirm-label="Ya, hapus" />
</div>
