<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Manajemen Add-on</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Kelola add-on dan kategori add-on dalam satu halaman.</p>
        </div>

        <div class="grid grid-cols-2 justify-center rounded-lg border border-gray-200 bg-white p-1 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
            <button
                type="button"
                wire:click="setTab('addons')"
                @class([
                    'rounded-md px-4 py-2 text-sm font-medium transition',
                    'bg-brand-500 text-white' => $tab === 'addons',
                    'text-gray-700 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-white/[0.03]' => $tab !== 'addons',
                ])
            >
                Add-on
            </button>
            <button
                type="button"
                wire:click="setTab('addon_categories')"
                @class([
                    'rounded-md px-4 py-2 text-sm font-medium transition',
                    'bg-brand-500 text-white' => $tab === 'addon_categories',
                    'text-gray-700 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-white/[0.03]' => $tab !== 'addon_categories',
                ])
            >
                Kategori
            </button>
        </div>
    </div>

    {{-- ═══ ADD-ONS TAB ═══ --}}
    @if ($tab === 'addons')
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex flex-col justify-between gap-5 border-b border-gray-200 px-5 py-4 sm:flex-row sm:items-center dark:border-gray-800">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Daftar Add-on</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Kelola data add-on, harga, dan ketersediaan.</p>
                </div>
                <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center">
                    <div class="relative min-w-0 flex-1 sm:flex-auto">
                        <span class="absolute top-1/2 left-4 -translate-y-1/2 text-gray-500 dark:text-gray-400">
                            <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M3.04199 9.37363C3.04199 5.87693 5.87735 3.04199 9.37533 3.04199C12.8733 3.04199 15.7087 5.87693 15.7087 9.37363C15.7087 12.8703 12.8733 15.7053 9.37533 15.7053C5.87735 15.7053 3.04199 12.8703 3.04199 9.37363ZM9.37533 1.54199C5.04926 1.54199 1.54199 5.04817 1.54199 9.37363C1.54199 13.6991 5.04926 17.2053 9.37533 17.2053C11.2676 17.2053 13.0032 16.5344 14.3572 15.4176L17.1773 18.238C17.4702 18.5309 17.945 18.5309 18.2379 18.238C18.5308 17.9451 18.5309 17.4703 18.238 17.1773L15.4182 14.3573C16.5367 13.0033 17.2087 11.2669 17.2087 9.37363C17.2087 5.04817 13.7014 1.54199 9.37533 1.54199Z" fill="" />
                            </svg>
                        </span>
                        <input wire:model.live.debounce.400ms="search" type="text" placeholder="Cari add-on..." class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent py-2.5 pr-4 pl-11 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                    </div>
                    <div class="relative">
                        <select wire:model.live="filterCategoryId" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-white px-4 py-2.5 pr-11 text-sm text-gray-700 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                            <option value="">Semua Kategori</option>
                            @foreach ($addonCategories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        <span class="pointer-events-none absolute top-1/2 right-4 -translate-y-1/2 text-gray-700 dark:text-gray-400">
                            <svg class="stroke-current" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M4.79175 7.396L10.0001 12.6043L15.2084 7.396" stroke="" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>
                    </div>
                    <button type="button" wire:click="openAddonModal" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex h-11 items-center justify-center rounded-lg px-4 text-sm font-semibold text-white transition">
                        Tambah Add-on
                    </button>
                </div>
            </div>

            <div class="custom-scrollbar overflow-x-auto">
                <table class="w-full table-auto">
                    <thead>
                        <tr class="border-b border-gray-200 dark:divide-gray-800 dark:border-gray-800">
                            <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">
                                <button type="button" wire:click="sortBy('name')" class="flex items-center gap-2">
                                    Nama
                                    <span class="flex flex-col gap-0.5">
                                        <svg width="8" height="5" viewBox="0 0 8 5" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M4.40962 0.585167C4.21057 0.300808 3.78943 0.300807 3.59038 0.585166L1.05071 4.21327C0.81874 4.54466 1.05582 5 1.46033 5H6.53967C6.94418 5 7.18126 4.54466 6.94929 4.21327L4.40962 0.585167Z" fill="currentColor" class="{{ $sortField === 'name' && $sortAsc ? 'text-gray-500 dark:text-gray-400' : 'text-gray-300 dark:text-gray-400/50' }}" />
                                        </svg>
                                        <svg width="8" height="5" viewBox="0 0 8 5" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M4.40962 4.41483C4.21057 4.69919 3.78943 4.69919 3.59038 4.41483L1.05071 0.786732C0.81874 0.455343 1.05582 0 1.46033 0H6.53967C6.94418 0 7.18126 0.455342 6.94929 0.786731L4.40962 4.41483Z" fill="currentColor" class="{{ $sortField === 'name' && ! $sortAsc ? 'text-gray-500 dark:text-gray-400' : 'text-gray-300 dark:text-gray-400/50' }}" />
                                        </svg>
                                    </span>
                                </button>
                            </th>
                            <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Kategori</th>
                            <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">
                                <button type="button" wire:click="sortBy('price')" class="flex items-center gap-2">
                                    Harga
                                    <span class="flex flex-col gap-0.5">
                                        <svg width="8" height="5" viewBox="0 0 8 5" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M4.40962 0.585167C4.21057 0.300808 3.78943 0.300807 3.59038 0.585166L1.05071 4.21327C0.81874 4.54466 1.05582 5 1.46033 5H6.53967C6.94418 5 7.18126 4.54466 6.94929 4.21327L4.40962 0.585167Z" fill="currentColor" class="{{ $sortField === 'price' && $sortAsc ? 'text-gray-500 dark:text-gray-400' : 'text-gray-300 dark:text-gray-400/50' }}" />
                                        </svg>
                                        <svg width="8" height="5" viewBox="0 0 8 5" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M4.40962 4.41483C4.21057 4.69919 3.78943 4.69919 3.59038 4.41483L1.05071 0.786732C0.81874 0.455343 1.05582 0 1.46033 0H6.53967C6.94418 0 7.18126 0.455342 6.94929 0.786731L4.40962 4.41483Z" fill="currentColor" class="{{ $sortField === 'price' && ! $sortAsc ? 'text-gray-500 dark:text-gray-400' : 'text-gray-300 dark:text-gray-400/50' }}" />
                                        </svg>
                                    </span>
                                </button>
                            </th>
                            <th class="px-5 py-4 text-center text-xs font-medium text-gray-500 dark:text-gray-400">Status</th>
                            @canany(['products.edit', 'products.delete'])
                                <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Aksi</th>
                            @endcanany
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($addons as $addon)
                            <tr>
                                <td class="px-5 py-4">
                                    <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $addon->name }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                        {{ $addon->addonCategory?->name ?? '-' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="text-sm text-gray-800 dark:text-white/90">Rp {{ number_format($addon->price, 0, ',', '.') }}</p>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <button
                                        type="button"
                                        wire:click="toggleAvailability({{ (int) $addon->id }})"
                                        class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $addon->is_available ? 'bg-brand-500' : 'bg-gray-300 dark:bg-gray-600' }}"
                                        wire:loading.attr="disabled"
                                    >
                                        <span
                                            class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $addon->is_available ? 'translate-x-6' : 'translate-x-1' }}"
                                        ></span>
                                    </button>
                                </td>
                                @canany(['products.edit', 'products.delete'])
                                    <td class="px-5 py-4 text-right">
                                        <div class="inline-flex items-center gap-2">
                                            @can('products.edit')
                                                <button type="button" wire:click="startEditAddon({{ (int) $addon->id }})" class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                                                    Edit
                                                </button>
                                            @endcan
                                            @can('products.delete')
                                                <button type="button" x-on:click.prevent="$dispatch('confirm', { message: 'Hapus add-on ini?', method: 'deleteAddon', args: [{{ (int) $addon->id }}] })" class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                                                    Hapus
                                                </button>
                                            @endcan
                                        </div>
                                    </td>
                                @endcanany
                            </tr>
                        @empty
                            <x-common.empty-table-row colspan="5" message="Belum ada add-on." />
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($addons->hasPages())
                <div class="border-t border-gray-200 px-5 py-3 dark:border-gray-800">
                    {{ $addons->links('livewire.pagination.admin') }}
                </div>
            @endif
        </div>

        {{-- Add-on Create/Edit Modal --}}
        @if ($addonModalOpen)
            <div class="fixed inset-0 z-[100000] flex items-center justify-center p-4" aria-modal="true" role="dialog">
                <div class="absolute inset-0 bg-black/50" wire:click="closeAddonModal"></div>
                <div class="relative w-full max-w-xl overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                        <div>
                            <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">{{ $editingAddonId ? 'Edit Add-on' : 'Tambah Add-on' }}</h3>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $editingAddonId ? 'Perbarui data add-on.' : 'Buat add-on baru.' }}</p>
                        </div>
                        <button type="button" wire:click="closeAddonModal" class="text-sm font-medium text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
                            Tutup
                        </button>
                    </div>
                    <form wire:submit="{{ $editingAddonId ? 'updateAddon' : 'storeAddon' }}" class="space-y-4 p-5">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Nama Add-on</label>
                            <input wire:model.live="formName" type="text" aria-invalid="{{ $errors->has('formName') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('formName') ? 'error-formName' : '' }}" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" placeholder="Contoh: Extra Keju" />
                            <x-common.input-error for="formName" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Kategori</label>
                            <select wire:model.live="formAddonCategoryId" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-700 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                                <option value="">Pilih kategori</option>
                                @foreach ($addonCategories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                            <x-common.input-error for="formAddonCategoryId" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Harga (Rp)</label>
                            <input wire:model.live="formPrice" type="number" min="0" aria-invalid="{{ $errors->has('formPrice') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('formPrice') ? 'error-formPrice' : '' }}" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" placeholder="0" />
                            <x-common.input-error for="formPrice" />
                        </div>
                        <div class="flex items-center gap-3">
                            <label class="text-xs font-medium text-gray-600 dark:text-gray-400">Tersedia</label>
                            <button type="button" wire:click="$set('formIsAvailable', !{{ json_encode($formIsAvailable) }})" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $formIsAvailable ? 'bg-brand-500' : 'bg-gray-300 dark:bg-gray-600' }}">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $formIsAvailable ? 'translate-x-6' : 'translate-x-1' }}"></span>
                            </button>
                        </div>
                        <div class="flex items-center justify-end gap-2 pt-2">
                            <button type="button" wire:click="closeAddonModal" class="shadow-theme-xs inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                                Batal
                            </button>
                            <button type="submit" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex h-11 items-center justify-center rounded-lg px-4 text-sm font-semibold text-white transition">
                                {{ $editingAddonId ? 'Simpan' : 'Tambah' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    @endif

    {{-- ═══ ADD-ON CATEGORIES TAB ═══ --}}
    @if ($tab === 'addon_categories')
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex flex-col justify-between gap-5 border-b border-gray-200 px-5 py-4 sm:flex-row sm:items-center dark:border-gray-800">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Daftar Kategori Add-on</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Tambahkan dan ubah kategori add-on.</p>
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
                    <button type="button" wire:click="openCreateCategoryModal" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex h-11 items-center justify-center rounded-lg px-4 text-sm font-semibold text-white transition">
                        Tambah Kategori
                    </button>
                </div>
            </div>

            <div class="custom-scrollbar overflow-x-auto">
                <table class="w-full table-auto">
                    <thead>
                        <tr class="border-b border-gray-200 dark:divide-gray-800 dark:border-gray-800">
                            <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Nama</th>
                            @canany(['products.edit', 'products.delete'])
                                <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Aksi</th>
                            @endcanany
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
                                            @can('products.edit')
                                                <button type="button" wire:click="startEditCategory({{ (int) $category->id }})" class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                                                    Edit
                                                </button>
                                            @endcan
                                            @can('products.delete')
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

        {{-- Category Create Modal --}}
        @if ($createCategoryModalOpen)
            <div class="fixed inset-0 z-[100000] flex items-center justify-center p-4" aria-modal="true" role="dialog">
                <div class="absolute inset-0 bg-black/50" wire:click="closeCreateCategoryModal"></div>
                <div class="relative w-full max-w-xl overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                        <div>
                            <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Tambah Kategori Add-on</h3>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Buat kategori add-on baru.</p>
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
