@php
    $canManage = auth()->user() && method_exists(auth()->user(), 'can') ? auth()->user()->can('reports.expenses.manage') : false;
    $fmtCurrency = fn ($value) => 'Rp'.number_format((float) $value, 0, ',', '.');
    $suggestedCategories = (array) ($suggestedCategories ?? []);
@endphp

<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Beban Operasional</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Catat biaya operasional usaha untuk perhitungan laba bersih.</p>
        </div>
        @if ($canManage)
            <button type="button" wire:click="openCreate" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex h-11 items-center justify-center rounded-lg px-4 text-sm font-semibold text-white transition">
                Tambah Beban
            </button>
        @endif
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-col justify-between gap-4 border-b border-gray-200 px-5 py-4 sm:flex-row sm:items-center dark:border-gray-800">
            <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
                <div class="relative flex-1 sm:flex-none">
                    <span class="absolute top-1/2 left-4 -translate-y-1/2 text-gray-500 dark:text-gray-400">
                        <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M3.04199 9.37363C3.04199 5.87693 5.87735 3.04199 9.37533 3.04199C12.8733 3.04199 15.7087 5.87693 15.7087 9.37363C15.7087 12.8703 12.8733 15.7053 9.37533 15.7053C5.87735 15.7053 3.04199 12.8703 3.04199 9.37363ZM9.37533 1.54199C5.04926 1.54199 1.54199 5.04817 1.54199 9.37363C1.54199 13.6991 5.04926 17.2053 9.37533 17.2053C11.2676 17.2053 13.0032 16.5344 14.3572 15.4176L17.1773 18.238C17.4702 18.5309 17.945 18.5309 18.2379 18.238C18.5308 17.9451 18.5309 17.4703 18.238 17.1773L15.4182 14.3573C16.5367 13.0033 17.2087 11.2669 17.2087 9.37363C17.2087 5.04817 13.7014 1.54199 9.37533 1.54199Z" fill="" />
                        </svg>
                    </span>
                    <input wire:model.live.debounce.400ms="search" type="text" placeholder="Cari kategori/catatan..." class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent py-2.5 pr-4 pl-11 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden sm:w-[280px] sm:min-w-[280px] dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                </div>

                <x-common.date-range-picker
                    :preset="$rangePreset"
                    :from="$fromDate"
                    :to="$toDate"
                    wire-from-model="fromDate"
                    wire-to-model="toDate"
                    class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center"
                />
            </div>

            <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                Total Beban: <span class="font-semibold">{{ $fmtCurrency($total ?? 0) }}</span>
            </div>
        </div>

        <div class="custom-scrollbar overflow-x-auto">
            <table class="w-full table-auto">
                <thead>
                    <tr class="border-b border-gray-200 dark:divide-gray-800 dark:border-gray-800">
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Tanggal</th>
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Kategori</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Nilai</th>
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Catatan</th>
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Dibuat oleh</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($rows as $row)
                        <tr>
                            <td class="px-5 py-4 whitespace-nowrap">
                                <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ optional($row->expense_date)->format('d M Y') }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ optional($row->expense_date)->format('Y-m-d') }}</p>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ (string) $row->category }}</p>
                            </td>
                            <td class="px-5 py-4 text-right whitespace-nowrap">
                                <p class="text-sm font-semibold text-gray-800 dark:text-white/90">{{ $fmtCurrency((int) $row->amount) }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <p class="text-sm text-gray-800 dark:text-white/90">{{ (string) ($row->note ?? '-') }}</p>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                <p class="text-sm text-gray-800 dark:text-white/90">{{ (string) ($users[(int) ($row->created_by_user_id ?? 0)] ?? '-') }}</p>
                            </td>
                            <td class="px-5 py-4 text-right whitespace-nowrap">
                                @if ($canManage)
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button" wire:click="openEdit({{ (int) $row->id }})" class="shadow-theme-xs inline-flex h-10 items-center justify-center rounded-lg border border-gray-300 bg-white px-3 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                                            Edit
                                        </button>
                                        <button type="button" wire:click="openDeleteConfirm({{ (int) $row->id }})" class="shadow-theme-xs inline-flex h-10 items-center justify-center rounded-lg border border-gray-300 bg-white px-3 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                                            Hapus
                                        </button>
                                    </div>
                                @else
                                    <p class="text-sm text-gray-500 dark:text-gray-400">-</p>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <x-common.empty-table-row colspan="6" message="Belum ada data." />
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-200 px-5 py-4 dark:border-gray-800">
            {{ $rows->links('livewire.pagination.admin') }}
        </div>
    </div>

    @if ($formModalOpen)
        <div class="fixed inset-0 z-[100000] flex items-center justify-center p-4" aria-modal="true" role="dialog">
            <div class="absolute inset-0 bg-black/50" wire:click="closeForm"></div>
            <div class="relative w-full max-w-2xl overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <div>
                        <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">{{ $editingId ? 'Edit Beban' : 'Tambah Beban' }}</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Biaya operasional yang mengurangi laba bersih.</p>
                    </div>
                    <button type="button" wire:click="closeForm" class="text-sm font-medium text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
                        Tutup
                    </button>
                </div>

                <form wire:submit="save" class="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Tanggal</label>
                        <x-common.date-picker wire-model="expenseDate" />
                        <x-common.input-error for="expenseDate" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Kategori</label>
                        <input wire:model.live="category" type="text" list="expense-category-suggestions" aria-invalid="{{ $errors->has('category') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('category') ? 'error-category' : '' }}" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" placeholder="Contoh: Listrik, Transport, ATK" />
                        <datalist id="expense-category-suggestions">
                            @foreach ($suggestedCategories as $cat)
                                <option value="{{ (string) $cat }}"></option>
                            @endforeach
                        </datalist>
                        <x-common.input-error for="category" />
                        @if ($suggestedCategories !== [])
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach ($suggestedCategories as $cat)
                                    <button
                                        type="button"
                                        wire:click="$set('category', @js($cat))"
                                        class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-white/[0.03]"
                                    >
                                        {{ (string) $cat }}
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Nilai (Rupiah)</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <span class="text-gray-500 dark:text-gray-400 font-semibold">Rp</span>
                            </div>
                            <input
                                x-data="currencyInput($wire.entangle('amount'))"
                                x-model="displayValue"
                                @input="handleInput"
                                type="text"
                                inputmode="numeric"
                                aria-invalid="{{ $errors->has('amount') ? 'true' : 'false' }}"
                                aria-describedby="{{ $errors->has('amount') ? 'error-amount' : '' }}"
                                class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 pl-10 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                placeholder="0"
                            />
                        </div>
                        <x-common.input-error for="amount" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Catatan</label>
                        <input wire:model.live="note" type="text" aria-invalid="{{ $errors->has('note') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('note') ? 'error-note' : '' }}" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                        <x-common.input-error for="note" />
                    </div>
                    <div class="sm:col-span-2 flex items-center justify-end gap-2">
                        <button type="button" wire:click="closeForm" class="shadow-theme-xs inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
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

    @if ($deleteConfirmOpen)
        <div class="fixed inset-0 z-[100000] flex items-center justify-center p-4" aria-modal="true" role="dialog">
            <div class="absolute inset-0 bg-black/50" wire:click="closeDeleteConfirm"></div>
            <div class="relative w-full max-w-lg overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <div>
                        <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Hapus Beban</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Data beban akan dihapus permanen.</p>
                    </div>
                    <button type="button" wire:click="closeDeleteConfirm" class="text-sm font-medium text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
                        Tutup
                    </button>
                </div>
                <div class="p-5">
                    <div class="rounded-xl border border-error-200 bg-error-50 p-4 text-sm text-error-700 dark:border-error-500/30 dark:bg-error-500/10 dark:text-error-200">
                        Pastikan Anda memang ingin menghapus data ini.
                    </div>
                    <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:justify-end">
                        <button type="button" wire:click="closeDeleteConfirm" class="shadow-theme-xs inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                            Batal
                        </button>
                        <button type="button" wire:click="delete" class="bg-error-600 shadow-theme-xs hover:bg-error-700 inline-flex h-11 items-center justify-center rounded-lg px-4 text-sm font-semibold text-white transition">
                            Hapus
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
