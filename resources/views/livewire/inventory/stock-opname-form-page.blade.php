<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-3">
                <a href="{{ route('stock-opnames.index') }}" wire:navigate class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                    Kembali
                </a>
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Stock Opname</h2>
            </div>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            @if (! $isLocked)
                @canany(['inventory.opnames.create', 'inventory.opnames.edit', 'inventory.opnames.manage', 'inventory.manage'])
                    @if (($stockOpnameId ? $canEdit : $canCreate))
                        <button type="button" wire:click="saveDraft" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex h-11 items-center justify-center rounded-lg px-4 text-sm font-medium text-white transition">
                            Simpan Draft
                        </button>
                    @endif
                @endcanany
                @canany(['inventory.opnames.refresh_system_stocks', 'inventory.opnames.manage', 'inventory.manage'])
                    @if (($stockOpnameId ? $canEdit : $canCreate) && $canRefresh)
                        <button type="button" wire:click="refreshSystemStocks" class="shadow-theme-xs inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                            Refresh Stok Sistem
                        </button>
                    @endif
                @endcanany
                @canany(['inventory.opnames.post', 'inventory.opnames.manage', 'inventory.manage'])
                    @if ($canPost)
                        <button type="button" wire:click="openPostConfirm" class="bg-emerald-600 shadow-theme-xs hover:bg-emerald-700 inline-flex h-11 items-center justify-center rounded-lg px-4 text-sm font-medium text-white transition" @disabled(count($items) === 0)>
                            Posting
                        </button>
                    @endif
                @endcanany
                @canany(['inventory.opnames.cancel', 'inventory.opnames.manage', 'inventory.manage'])
                    @if ($stockOpnameId && $canCancel)
                        <button type="button" wire:click="openCancelConfirm" class="shadow-theme-xs inline-flex h-11 items-center justify-center rounded-lg border border-error-200 bg-white px-4 text-sm font-semibold text-error-700 hover:bg-error-50 dark:border-error-500/30 dark:bg-gray-900 dark:text-error-400 dark:hover:bg-error-500/10">
                            Batalkan
                        </button>
                    @endif
                @endcanany
            @endif
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-4 text-sm text-gray-700 dark:border-gray-800 dark:bg-white/[0.03] dark:text-gray-300">
        <div class="grid grid-cols-1 gap-2 lg:grid-cols-3">
            <div>
                <p class="font-semibold">Tujuan</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Sinkronkan stok sistem dengan stok fisik hasil hitung.</p>
            </div>
            <div>
                <p class="font-semibold">Kapan dipakai</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Koreksi berkala (mingguan/bulanan). Kejadian harian seperti rusak/expired/staff meal gunakan Pergerakan Stok.</p>
            </div>
            <div>
                <p class="font-semibold">Finalisasi</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Saat difinalisasi, sistem membuat penyesuaian stok berdasarkan selisih.</p>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Kode</label>
                <input wire:model.live="code" type="text" aria-invalid="{{ $errors->has('code') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('code') ? 'error-code' : '' }}" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" @disabled($isLocked) />
                <x-common.input-error for="code" />
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Tanggal Hitung</label>
                <x-common.date-picker wire-model="countedAt" :disabled="$isLocked" />
                <x-common.input-error for="countedAt" />
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Status</label>
                <input value="{{ $isPosted ? 'Posted' : ($isCancelled ? 'Cancelled' : 'Draft') }}" type="text" class="shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-gray-50 px-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400" readonly />
            </div>
        </div>

        <div class="mt-4">
            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Catatan</label>
            <input wire:model.live="note" type="text" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" @disabled($isLocked) />
        </div>
    </div>

    <x-common.input-error for="items" class="text-sm text-error-600" />

    @php
        $itemsCount = count($items);
        $adjustmentCount = 0;
        $sumPositive = 0.0;
        $sumNegative = 0.0;
        foreach ($items as $row) {
            $variance = (float) (\App\Support\Number\QuantityParser::parse($row['counted_qty'] ?? null) ?? 0) - (float) ($row['system_qty'] ?? 0);
            if (abs($variance) >= 0.0005) {
                $adjustmentCount++;
                if ($variance > 0) {
                    $sumPositive += $variance;
                } else {
                    $sumNegative += abs($variance);
                }
            }
        }
    @endphp
    <div class="rounded-2xl border border-gray-200 bg-white px-5 py-4 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
            <div class="text-sm text-gray-700 dark:text-gray-300">
                Total item: <span class="font-semibold">{{ number_format($itemsCount, 0, ',', '.') }}</span>
            </div>
            <div class="text-sm text-gray-700 dark:text-gray-300">
                Item selisih: <span class="font-semibold">{{ number_format($adjustmentCount, 0, ',', '.') }}</span>
            </div>
            <div class="text-sm text-gray-700 dark:text-gray-300">
                Total penyesuaian: <span class="font-semibold text-success-600 dark:text-success-500">+{{ \App\Support\Number\QuantityFormatter::format($sumPositive) }}</span> / <span class="font-semibold text-error-600 dark:text-error-500">-{{ \App\Support\Number\QuantityFormatter::format($sumNegative) }}</span>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="hidden sm:block">
            <div class="custom-scrollbar overflow-x-auto">
                <table class="w-full table-auto">
                    <thead>
                        <tr class="border-b border-gray-200 dark:divide-gray-800 dark:border-gray-800">
                            <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Bahan</th>
                            <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Stok Sistem</th>
                            <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Stok Fisik</th>
                            <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Selisih</th>
                            <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Catatan</th>
                            <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($items as $i => $row)
                            @php
                                $ingredientId = (int) ($row['ingredient_id'] ?? 0);
                                $unit = '-';
                                $name = '';
                                foreach ($ingredients as $ingredient) {
                                    if ((int) $ingredient['id'] === $ingredientId) {
                                        $unit = $ingredient['unit'];
                                        $name = (string) $ingredient['name'];
                                        break;
                                    }
                                }
                                $systemQty = (float) ($row['system_qty'] ?? 0);
                                $countedQty = (float) (\App\Support\Number\QuantityParser::parse($row['counted_qty'] ?? null) ?? 0);
                                $variance = $countedQty - $systemQty;
                                $hasVariance = abs($variance) >= 0.0005;
                            @endphp
                            <tr class="{{ $hasVariance ? 'bg-brand-50/50 dark:bg-brand-500/5' : '' }}">
                                <td class="px-5 py-4">
                                    <select wire:model.live="items.{{ $i }}.ingredient_id" aria-invalid="{{ $errors->has('items.'.$i.'.ingredient_id') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('items.'.$i.'.ingredient_id') ? 'error-items-'.$i.'-ingredient_id' : '' }}" class="shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-white px-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400" @disabled($isLocked)>
                                        <option value="">Pilih bahan</option>
                                        @foreach ($ingredients as $ingredient)
                                            <option value="{{ $ingredient['id'] }}">{{ $ingredient['name'] }} ({{ $ingredient['unit'] }})</option>
                                        @endforeach
                                    </select>
                                    <x-common.input-error :for="'items.'.$i.'.ingredient_id'" />
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ \App\Support\Number\QuantityFormatter::format((float) ($row['system_qty'] ?? 0)) }} {{ $unit }}</p>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <input wire:model.live="items.{{ $i }}.counted_qty" type="text" inputmode="decimal" aria-invalid="{{ $errors->has('items.'.$i.'.counted_qty') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('items.'.$i.'.counted_qty') ? 'error-items-'.$i.'-counted_qty' : '' }}" class="dark:bg-dark-900 shadow-theme-xs h-11 w-32 rounded-lg border border-gray-300 bg-transparent px-3 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 text-right" @disabled($isLocked) />
                                    <x-common.input-error :for="'items.'.$i.'.counted_qty'" />
                                </td>
                                <td class="px-5 py-4 text-right">
                                    @if ($ingredientId === 0)
                                        <p class="text-sm text-gray-500 dark:text-gray-400">-</p>
                                    @else
                                        <p class="text-sm font-semibold {{ $hasVariance ? ($variance > 0 ? 'text-success-600 dark:text-success-500' : 'text-error-600 dark:text-error-500') : 'text-gray-600 dark:text-gray-400' }}">
                                            {{ $hasVariance ? ($variance > 0 ? '+' : '') : '' }}{{ \App\Support\Number\QuantityFormatter::format($variance) }} {{ $unit }}
                                        </p>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <input wire:model.live="items.{{ $i }}.note" type="text" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" @disabled($isLocked) />
                                </td>
                                <td class="px-5 py-4 text-right">
                                    @if (! $isLocked)
                                        <button type="button" x-on:click.prevent="$dispatch('confirm', { message: 'Hapus item ini?', method: 'removeItem', args: [{{ $i }}] })" class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                                            Hapus
                                        </button>
                                    @else
                                        <p class="text-sm text-gray-500 dark:text-gray-400">-</p>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <x-common.empty-table-row colspan="6" message="Belum ada item opname. Klik “Tambah Item”." />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="sm:hidden">
            <div class="space-y-3 p-4">
                @forelse ($items as $i => $row)
                    @php
                        $ingredientId = (int) ($row['ingredient_id'] ?? 0);
                        $unit = '-';
                        $name = '';
                        foreach ($ingredients as $ingredient) {
                            if ((int) $ingredient['id'] === $ingredientId) {
                                $unit = $ingredient['unit'];
                                $name = (string) $ingredient['name'];
                                break;
                            }
                        }
                        $systemQty = (float) ($row['system_qty'] ?? 0);
                        $countedQty = (float) (\App\Support\Number\QuantityParser::parse($row['counted_qty'] ?? null) ?? 0);
                        $variance = $countedQty - $systemQty;
                        $hasVariance = abs($variance) >= 0.0005;
                    @endphp
                    <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900 {{ $hasVariance ? 'ring-1 ring-brand-500/20' : '' }}">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-semibold text-gray-800 dark:text-white/90">Item {{ number_format($i + 1, 0, ',', '.') }}</p>
                            @if (! $isLocked)
                                <button type="button" x-on:click.prevent="$dispatch('confirm', { message: 'Hapus item ini?', method: 'removeItem', args: [{{ $i }}] })" class="text-sm font-semibold text-error-700 hover:text-error-800 dark:text-error-400 dark:hover:text-error-300">
                                    Hapus
                                </button>
                            @endif
                        </div>

                        <div class="mt-3 space-y-3">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Bahan</label>
                                <select wire:model.live="items.{{ $i }}.ingredient_id" aria-invalid="{{ $errors->has('items.'.$i.'.ingredient_id') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('items.'.$i.'.ingredient_id') ? 'error-items-'.$i.'-ingredient_id' : '' }}" class="shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-white px-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300" @disabled($isLocked)>
                                    <option value="">Pilih bahan</option>
                                    @foreach ($ingredients as $ingredient)
                                        <option value="{{ $ingredient['id'] }}">{{ $ingredient['name'] }} ({{ $ingredient['unit'] }})</option>
                                    @endforeach
                                </select>
                                <x-common.input-error :for="'items.'.$i.'.ingredient_id'" />
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 dark:border-gray-800 dark:bg-gray-950/30">
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Stok Sistem</p>
                                    <p class="mt-1 text-sm font-semibold text-gray-800 dark:text-white/90">{{ \App\Support\Number\QuantityFormatter::format($systemQty) }} {{ $unit }}</p>
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Stok Fisik</label>
                                    <input wire:model.live="items.{{ $i }}.counted_qty" type="text" inputmode="decimal" aria-invalid="{{ $errors->has('items.'.$i.'.counted_qty') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('items.'.$i.'.counted_qty') ? 'error-items-'.$i.'-counted_qty' : '' }}" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 text-right" @disabled($isLocked) />
                                    <x-common.input-error :for="'items.'.$i.'.counted_qty'" />
                                </div>
                            </div>

                            <div class="flex items-center justify-between rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm dark:border-gray-800 dark:bg-gray-950/30">
                                <p class="text-gray-600 dark:text-gray-300">Selisih</p>
                                @if ($ingredientId === 0)
                                    <p class="font-semibold text-gray-500 dark:text-gray-400">-</p>
                                @else
                                    <p class="font-semibold {{ $hasVariance ? ($variance > 0 ? 'text-success-600 dark:text-success-500' : 'text-error-600 dark:text-error-500') : 'text-gray-600 dark:text-gray-400' }}">
                                        {{ $hasVariance ? ($variance > 0 ? '+' : '') : '' }}{{ \App\Support\Number\QuantityFormatter::format($variance) }} {{ $unit }}
                                    </p>
                                @endif
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Catatan</label>
                                <input wire:model.live="items.{{ $i }}.note" type="text" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" @disabled($isLocked) />
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="py-10">
                        <p class="text-center text-sm text-gray-500 dark:text-gray-400">Belum ada item opname. Klik “Tambah Item”.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
    @if (!$isLocked)
    @canany(['inventory.opnames.create', 'inventory.opnames.edit', 'inventory.opnames.manage', 'inventory.manage'])
        @if (($stockOpnameId ? $canEdit : $canCreate))
            <button type="button" wire:click="addItem" class="shadow-theme-md w-full inline-flex h-11 items-center justify-center rounded-lg border border-brand-400 bg-brand-50 px-4 text-sm font-medium text-brand-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                + Tambah Item
            </button>
        @endif
    @endcanany
    @endif

    @if ($postConfirmOpen && ! $isLocked)
        <div class="fixed inset-0 z-[100000] flex items-center justify-center p-4" aria-modal="true" role="dialog">
            <div class="absolute inset-0 bg-black/50" wire:click="closePostConfirm"></div>
            <div class="relative w-full max-w-lg overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <div>
                        <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Posting Stock Opname</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Aksi ini akan menyimpan draft dan membuat penyesuaian stok.</p>
                    </div>
                    <button type="button" wire:click="closePostConfirm" class="text-sm font-medium text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
                        Tutup
                    </button>
                </div>
                <div class="p-5">
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 dark:border-gray-800 dark:bg-gray-950/40 dark:text-gray-300">
                        Pastikan stok fisik sudah benar. Setelah diposting, dokumen akan menjadi readonly.
                    </div>
                    <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:justify-end">
                        <button type="button" wire:click="closePostConfirm" class="shadow-theme-xs inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                            Batal
                        </button>
                        <button type="button" wire:click="post" class="bg-emerald-600 shadow-theme-xs hover:bg-emerald-700 inline-flex h-11 items-center justify-center rounded-lg px-4 text-sm font-semibold text-white transition" @disabled(count($items) === 0)>
                            Posting Sekarang
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($cancelConfirmOpen && ! $isLocked)
        <div class="fixed inset-0 z-[100000] flex items-center justify-center p-4" aria-modal="true" role="dialog">
            <div class="absolute inset-0 bg-black/50" wire:click="closeCancelConfirm"></div>
            <div class="relative w-full max-w-lg overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <div>
                        <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Batalkan Stock Opname</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Opname draft akan ditandai cancelled dan tidak bisa diposting.</p>
                    </div>
                    <button type="button" wire:click="closeCancelConfirm" class="text-sm font-medium text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
                        Tutup
                    </button>
                </div>
                <div class="p-5">
                    <div class="rounded-xl border border-error-200 bg-error-50 p-4 text-sm text-error-700 dark:border-error-500/30 dark:bg-error-500/10 dark:text-error-200">
                        Pastikan Anda memang ingin membatalkan dokumen ini.
                    </div>
                    <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:justify-end">
                        <button type="button" wire:click="closeCancelConfirm" class="shadow-theme-xs inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                            Batal
                        </button>
                        <button type="button" wire:click="cancelOpname" class="bg-error-600 shadow-theme-xs hover:bg-error-700 inline-flex h-11 items-center justify-center rounded-lg px-4 text-sm font-semibold text-white transition">
                            Batalkan Opname
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <x-common.confirm-modal confirm-label="Ya, hapus" />
</div>
