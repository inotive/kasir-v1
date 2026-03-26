<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Bahan Baku</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Kelola bahan baku dan pantau stok berjalan.</p>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-col justify-between gap-5 border-b border-gray-200 px-5 py-4 sm:flex-row sm:items-center dark:border-gray-800">
            <div class="relative flex-1 sm:flex-none">
                <span class="absolute top-1/2 left-4 -translate-y-1/2 text-gray-500 dark:text-gray-400">
                    <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M3.04199 9.37363C3.04199 5.87693 5.87735 3.04199 9.37533 3.04199C12.8733 3.04199 15.7087 5.87693 15.7087 9.37363C15.7087 12.8703 12.8733 15.7053 9.37533 15.7053C5.87735 15.7053 3.04199 12.8703 3.04199 9.37363ZM9.37533 1.54199C5.04926 1.54199 1.54199 5.04817 1.54199 9.37363C1.54199 13.6991 5.04926 17.2053 9.37533 17.2053C11.2676 17.2053 13.0032 16.5344 14.3572 15.4176L17.1773 18.238C17.4702 18.5309 17.945 18.5309 18.2379 18.238C18.5308 17.9451 18.5309 17.4703 18.238 17.1773L15.4182 14.3573C16.5367 13.0033 17.2087 11.2669 17.2087 9.37363C17.2087 5.04817 13.7014 1.54199 9.37533 1.54199Z" fill="" />
                    </svg>
                </span>
                <input wire:model.live.debounce.400ms="search" type="text" placeholder="Cari bahan baku..." class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent py-2.5 pr-4 pl-11 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden sm:w-[320px] sm:min-w-[320px] dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
            </div>

            @canany(['inventory.ingredients.manage', 'inventory.manage'])
                <button type="button" wire:click="openCreateIngredientModal" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex h-11 items-center justify-center rounded-lg px-4 text-sm font-semibold text-white transition">
                    Tambah Bahan
                </button>
            @endcanany
        </div>

        <div class="custom-scrollbar overflow-x-auto">
            <table class="w-full table-auto">
                <thead>
                    <tr class="border-b border-gray-200 dark:divide-gray-800 dark:border-gray-800">
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Bahan</th>
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Unit</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Stok</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Reorder</th>
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Status</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($ingredients as $ingredient)
                        @php
                            $stock = (float) ($ingredient->stock_on_hand ?? 0);
                            $reorder = (float) ($ingredient->reorder_level ?? 0);
                        @endphp
                        <tr>
                            <td class="px-5 py-4 whitespace-nowrap">
                                @if ($editingIngredientId === (int) $ingredient->id)
                                    <div class="space-y-2">
                                        <input wire:model.live="editingName" type="text" aria-invalid="{{ $errors->has('editingName') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('editingName') ? 'error-editingName' : '' }}" class="dark:bg-dark-900 shadow-theme-xs h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                                        <x-common.input-error for="editingName" class="text-xs text-error-600" />
                                        <div class="grid grid-cols-3 gap-2">
                                            <input wire:model.live="editingSku" type="text" placeholder="SKU" class="dark:bg-dark-900 shadow-theme-xs h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                                            <input wire:model.live="editingUnit" type="text" placeholder="Unit" class="dark:bg-dark-900 shadow-theme-xs h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                                            <x-common.rupiah-input
                                                wire-model="editingCostPrice"
                                                placeholder="HPP"
                                                input-class="dark:bg-dark-900 shadow-theme-xs h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                            />
                                        </div>
                                        <input wire:model.live="editingReorderLevel" type="text" inputmode="decimal" placeholder="Reorder level" class="dark:bg-dark-900 shadow-theme-xs h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                                    </div>
                                @else
                                    <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $ingredient->name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $ingredient->sku }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                <p class="text-sm text-gray-800 dark:text-white/90">{{ $ingredient->unit }}</p>
                            </td>
                            <td class="px-5 py-4 text-right whitespace-nowrap">
                                <p class="text-sm font-semibold {{ $stock <= $reorder && $reorder > 0 ? 'text-error-600 dark:text-error-500' : 'text-gray-800 dark:text-white/90' }}">
                                    {{ \App\Support\Number\QuantityFormatter::format($stock) . ' ' . $ingredient->unit }}
                                </p>
                            </td>
                            <td class="px-5 py-4 text-right whitespace-nowrap">
                                <p class="text-sm text-gray-800 dark:text-white/90">{{ \App\Support\Number\QuantityFormatter::format($reorder) . ' ' . $ingredient->unit }}</p>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                @canany(['inventory.ingredients.manage', 'inventory.manage'])
                                    <button type="button" wire:click="toggleActive({{ (int) $ingredient->id }})" class="rounded-full px-2 py-0.5 text-theme-xs font-medium {{ $ingredient->is_active ? 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500' : 'bg-gray-50 text-gray-600 dark:bg-gray-500/15 dark:text-gray-400' }}">
                                        {{ $ingredient->is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                @else
                                    <span class="rounded-full px-2 py-0.5 text-theme-xs font-medium {{ $ingredient->is_active ? 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500' : 'bg-gray-50 text-gray-600 dark:bg-gray-500/15 dark:text-gray-400' }}">
                                        {{ $ingredient->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                @endcanany
                            </td>
                            <td class="px-5 py-4 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-2">
                                    @if ($editingIngredientId === (int) $ingredient->id)
                                        <button type="button" wire:click="updateIngredient" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-3 py-2 text-xs font-medium text-white transition">
                                            Simpan
                                        </button>
                                        <button type="button" wire:click="cancelEditIngredient" class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                                            Batal
                                        </button>
                                    @else
                                        @canany(['inventory.ingredients.manage', 'inventory.manage'])
                                            <a href="{{ route('ingredients.conversions', $ingredient) }}" wire:navigate class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                                                Konversi
                                            </a>
                                            <button type="button" wire:click="startEditIngredient({{ (int) $ingredient->id }})" class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                                                Edit
                                            </button>
                                            <button type="button" x-on:click.prevent="$dispatch('confirm', { message: 'Hapus bahan baku ini?', method: 'deleteIngredient', args: [{{ (int) $ingredient->id }}] })" class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                                                Hapus
                                            </button>
                                        @else
                                            <p class="text-sm text-gray-500 dark:text-gray-400">-</p>
                                        @endcanany
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-common.empty-table-row colspan="6" message="Bahan baku belum ada." />
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-200 px-5 py-4 dark:border-gray-800">
            {{ $ingredients->links('livewire.pagination.admin') }}
        </div>
    </div>

    @if ($createIngredientModalOpen)
        <div class="fixed inset-0 z-[100000] flex items-center justify-center p-4" aria-modal="true" role="dialog">
            <div class="absolute inset-0 bg-black/50" wire:click="closeCreateIngredientModal"></div>
            <div class="relative w-full max-w-xl overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <div>
                        <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Tambah Bahan Baku</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Isi data bahan baku baru.</p>
                    </div>
                    <button type="button" wire:click="closeCreateIngredientModal" class="text-sm font-medium text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
                        Tutup
                    </button>
                </div>

                <form wire:submit="createIngredient" class="space-y-4 p-5">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Nama</label>
                            <input wire:model.live="name" type="text" aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('name') ? 'error-name' : '' }}" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                            <x-common.input-error for="name" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">SKU</label>
                            <input wire:model.live="sku" type="text" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Unit</label>
                            <input wire:model.live="unit" type="text" aria-invalid="{{ $errors->has('unit') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('unit') ? 'error-unit' : '' }}" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                            <x-common.input-error for="unit" />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">HPP per Unit</label>
                            <x-common.rupiah-input wire-model="costPrice" input-class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                            <x-common.input-error for="costPrice" />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Reorder Level (Opsional)</label>
                            <input wire:model.live="reorderLevel" type="text" inputmode="decimal" aria-invalid="{{ $errors->has('reorderLevel') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('reorderLevel') ? 'error-reorderLevel' : '' }}" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                            <x-common.input-error for="reorderLevel" />
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2">
                        <button type="button" wire:click="closeCreateIngredientModal" class="shadow-theme-xs inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
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

    <x-common.confirm-modal confirm-label="Ya, hapus" />
</div>
