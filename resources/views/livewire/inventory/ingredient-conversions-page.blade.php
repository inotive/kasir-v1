<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-3">
                <a href="{{ route('ingredients.index') }}" wire:navigate class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                    Kembali
                </a>
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Konversi Unit</h2>
            </div>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $ingredient->name }} · Base unit: {{ $ingredient->unit }}</p>
        </div>
        @canany(['inventory.ingredients.manage', 'inventory.manage'])
            <button type="button" wire:click="openCreateConversionModal" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex h-11 items-center justify-center rounded-lg px-4 text-sm font-semibold text-white transition">
                Tambah Konversi
            </button>
        @endcanany
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="custom-scrollbar overflow-x-auto">
            <table class="w-full table-auto">
                <thead>
                    <tr class="border-b border-gray-200 dark:divide-gray-800 dark:border-gray-800">
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Unit</th>
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Factor</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($conversions as $conversion)
                        <tr>
                            <td class="px-5 py-4">
                                @if ($editingConversionId === (int) $conversion->id)
                                    <input wire:model.live="editingUnit" type="text" aria-invalid="{{ $errors->has('editingUnit') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('editingUnit') ? 'error-editingUnit' : '' }}" class="dark:bg-dark-900 shadow-theme-xs h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                                    <x-common.input-error for="editingUnit" />
                                @else
                                    <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $conversion->unit }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                @if ($editingConversionId === (int) $conversion->id)
                                    <input wire:model.live="editingFactorToBase" type="text" inputmode="decimal" aria-invalid="{{ $errors->has('editingFactorToBase') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('editingFactorToBase') ? 'error-editingFactorToBase' : '' }}" class="dark:bg-dark-900 shadow-theme-xs h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" placeholder="1 unit = ? base" />
                                    <x-common.input-error for="editingFactorToBase" />
                                @else
                                    <p class="text-sm text-gray-800 dark:text-white/90">{{ \App\Support\Number\QuantityFormatter::format((float) $conversion->factor_to_base, 6) }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="inline-flex items-center gap-2">
                                    @if ($editingConversionId === (int) $conversion->id)
                                        <button type="button" wire:click="updateConversion" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-3 py-2 text-xs font-medium text-white transition">
                                            Simpan
                                        </button>
                                        <button type="button" wire:click="cancelEdit" class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                                            Batal
                                        </button>
                                    @else
                                        @canany(['inventory.ingredients.manage', 'inventory.manage'])
                                            <button type="button" wire:click="startEdit({{ (int) $conversion->id }})" class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                                                Edit
                                            </button>
                                            <button type="button" x-on:click.prevent="$dispatch('confirm', { message: 'Hapus konversi ini?', method: 'deleteConversion', args: [{{ (int) $conversion->id }}] })" class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
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
                        <x-common.empty-table-row colspan="3" message="Belum ada konversi." />
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($createConversionModalOpen)
            <div class="fixed inset-0 z-[100000] flex items-center justify-center p-4" aria-modal="true" role="dialog">
                <div class="absolute inset-0 bg-black/50" wire:click="closeCreateConversionModal"></div>
                <div class="relative w-full max-w-xl overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                        <div>
                            <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Tambah Konversi</h3>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Base unit: {{ $ingredient->unit }}</p>
                        </div>
                        <button type="button" wire:click="closeCreateConversionModal" class="text-sm font-medium text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
                            Tutup
                        </button>
                    </div>
                    <form wire:submit="createConversion" class="space-y-4 p-5">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Unit</label>
                                <input wire:model.live="unit" type="text" aria-invalid="{{ $errors->has('unit') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('unit') ? 'error-unit' : '' }}" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" placeholder="kg" />
                                <x-common.input-error for="unit" />
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Factor ke Base</label>
                                <input wire:model.live="factorToBase" type="text" inputmode="decimal" aria-invalid="{{ $errors->has('factorToBase') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('factorToBase') ? 'error-factorToBase' : '' }}" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" placeholder="1 unit = ? base" />
                                <x-common.input-error for="factorToBase" />
                            </div>
                        </div>
                        <div class="flex items-center justify-end gap-2">
                            <button type="button" wire:click="closeCreateConversionModal" class="shadow-theme-xs inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
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
    </div>

    <x-common.confirm-modal confirm-label="Ya, hapus" />
</div>
