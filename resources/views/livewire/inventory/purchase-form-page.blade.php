<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-3">
                <a href="{{ route('purchases.index') }}" wire:navigate class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                    Kembali
                </a>
                <div class="flex items-center gap-3">
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Pembelian</h2>
                    <span class="rounded-full px-2 py-0.5 text-theme-xs font-medium {{ $isReceived ? 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500' : ($isCancelled ? 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500' : 'bg-gray-50 text-gray-600 dark:bg-gray-500/15 dark:text-gray-400') }}">
                        {{ $isReceived ? 'Received' : ($isCancelled ? 'Cancelled' : 'Draft') }}
                    </span>
                </div>
            </div>
           
        </div>

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            @if (! $isLocked)
                @canany(['inventory.purchases.create', 'inventory.purchases.edit', 'inventory.purchases.manage', 'inventory.manage'])
                    @if (($purchaseId ? $canEdit : $canCreate))
                        <button type="button" wire:click="saveDraft" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex h-11 items-center justify-center rounded-lg px-4 text-sm font-medium text-white transition">
                            Simpan Draft
                        </button>
                    @endif
                @endcanany
                @canany(['inventory.purchases.receive', 'inventory.purchases.manage', 'inventory.manage'])
                    @if ($canReceive)
                        <button type="button" wire:click="openReceiveConfirm" class="bg-emerald-600 shadow-theme-xs hover:bg-emerald-700 inline-flex h-11 items-center justify-center rounded-lg px-4 text-sm font-medium text-white transition" @disabled(count($items) === 0)>
                            Receive
                        </button>
                    @endif
                @endcanany
                @canany(['inventory.purchases.cancel', 'inventory.purchases.manage', 'inventory.manage'])
                    @if ($purchaseId && $canCancel)
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
                <p class="font-semibold">Penerimaan</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Saat diterima, stok bertambah dan harga pokok bahan diperbarui.</p>
            </div>
            <div>
                <p class="font-semibold">Metode Cost</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Harga pokok bahan mengikuti rata-rata tertimbang (moving average).</p>
            </div>
            <div>
                <p class="font-semibold">Catatan</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Jika ada stok minus sebelum penerimaan, sistem memberi peringatan karena costing bisa bias.</p>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Kode</label>
                <input wire:model.live="code" type="text" aria-invalid="{{ $errors->has('code') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('code') ? 'error-code' : '' }}" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" @disabled($isLocked) />
                <x-common.input-error for="code" />
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Tanggal</label>
                <x-common.date-picker wire-model="purchasedAt" :disabled="$isLocked" />
                <x-common.input-error for="purchasedAt" />
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Supplier</label>
                <select wire:model.live="supplierId" aria-invalid="{{ $errors->has('supplierId') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('supplierId') ? 'error-supplierId' : '' }}" class="shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-white px-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400" @disabled($isLocked)>
                    <option value="">-</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier['id'] }}">{{ $supplier['name'] }}</option>
                    @endforeach
                </select>
                <x-common.input-error for="supplierId" />
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Status</label>
                <input value="{{ $isReceived ? 'Received' : ($isCancelled ? 'Cancelled' : 'Draft') }}" type="text" class="shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-gray-50 px-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400" readonly />
            </div>
        </div>

        <div class="mt-4">
            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Catatan</label>
            <input wire:model.live="note" type="text" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" @disabled($isLocked) />
        </div>
    </div>

    <x-common.input-error for="items" class="text-sm text-error-600" />

    @php
        $estTotal = 0.0;
        foreach ($items as $row) {
            $qty = (float) (\App\Support\Number\QuantityParser::parse($row['input_quantity'] ?? null) ?? 0);
            $unitCost = $row['input_unit_cost'] === null || $row['input_unit_cost'] === '' ? null : (float) $row['input_unit_cost'];
            if ($unitCost !== null && $qty > 0) {
                $estTotal += $qty * $unitCost;
            }
        }
        $itemsCount = count($items);
    @endphp
    <div class="rounded-2xl border border-gray-200 bg-white px-5 py-4 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div class="text-sm text-gray-700 dark:text-gray-300">
                Total item: <span class="font-semibold">{{ number_format($itemsCount, 0, ',', '.') }}</span>
            </div>
            <div class="text-sm text-gray-700 dark:text-gray-300">
                Estimasi total: <span class="font-semibold">Rp{{ number_format($estTotal, 0, ',', '.') }}</span>
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
                            <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Qty</th>
                            <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Harga/unit</th>
                            <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Subtotal</th>
                            <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Catatan</th>
                            <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($items as $i => $row)
                            @php
                                $ingredientId = (int) ($row['ingredient_id'] ?? 0);
                                $units = [];
                                $baseUnit = '-';
                                $unitFactors = [];
                                foreach ($ingredients as $ingredient) {
                                    if ((int) $ingredient['id'] === $ingredientId) {
                                        $units = (array) ($ingredient['units'] ?? []);
                                        $baseUnit = (string) ($ingredient['base_unit'] ?? '-');
                                        $unitFactors = (array) ($ingredient['unit_factors'] ?? []);
                                        break;
                                    }
                                }
                                $inputQty = (float) (\App\Support\Number\QuantityParser::parse($row['input_quantity'] ?? null) ?? 0);
                                $inputUnit = (string) ($row['input_unit'] ?? '');
                                $inputUnitCost = $row['input_unit_cost'] === null || $row['input_unit_cost'] === '' ? null : (float) $row['input_unit_cost'];
                                $factor = (float) ($unitFactors[$inputUnit] ?? 0);
                                $baseQty = $factor > 0 ? $inputQty * $factor : null;
                                $baseUnitCost = $inputUnitCost !== null && $factor > 0 ? $inputUnitCost / $factor : null;
                                $subtotal = $inputUnitCost === null ? 0 : $inputQty * $inputUnitCost;
                            @endphp
                            <tr>
                                <td class="px-5 py-4">
                                    <select wire:model.live="items.{{ $i }}.ingredient_id" aria-invalid="{{ $errors->has('items.'.$i.'.ingredient_id') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('items.'.$i.'.ingredient_id') ? 'error-items-'.$i.'-ingredient_id' : '' }}" class="shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-white px-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400" @disabled($isLocked)>
                                        <option value="">Pilih bahan</option>
                                        @foreach ($ingredients as $ingredient)
                                            <option value="{{ $ingredient['id'] }}">{{ $ingredient['name'] }} ({{ $ingredient['base_unit'] }})</option>
                                        @endforeach
                                    </select>
                                    <x-common.input-error :for="'items.'.$i.'.ingredient_id'" />
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <input wire:model.live="items.{{ $i }}.input_quantity" type="text" inputmode="decimal" aria-invalid="{{ $errors->has('items.'.$i.'.input_quantity') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('items.'.$i.'.input_quantity') ? 'error-items-'.$i.'-input_quantity' : '' }}" class="dark:bg-dark-900 shadow-theme-xs h-11 w-28 rounded-lg border border-gray-300 bg-transparent px-3 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 text-right" @disabled($isLocked) />
                                        <select wire:model.live="items.{{ $i }}.input_unit" aria-invalid="{{ $errors->has('items.'.$i.'.input_unit') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('items.'.$i.'.input_unit') ? 'error-items-'.$i.'-input_unit' : '' }}" class="shadow-theme-xs h-11 w-24 rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400" @disabled($isLocked || $ingredientId === 0)>
                                            <option value="">Unit</option>
                                            @foreach ($units as $unit)
                                                <option value="{{ $unit }}">{{ $unit }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @if ($baseQty !== null && $baseUnit !== '-' && $factor > 0)
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Masuk stok: {{ \App\Support\Number\QuantityFormatter::format($baseQty) }} {{ $baseUnit }}</p>
                                    @endif
                                    <x-common.input-error :for="'items.'.$i.'.input_quantity'" />
                                    <x-common.input-error :for="'items.'.$i.'.input_unit'" />
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <x-common.rupiah-input
                                        wire-model="items.{{ $i }}.input_unit_cost"
                                        placeholder="0"
                                        :disabled="$isLocked"
                                        input-class="dark:bg-dark-900 shadow-theme-xs h-11 w-32 rounded-lg border border-gray-300 bg-transparent px-3 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 text-right"
                                    />
                                    @if ($baseUnitCost !== null && $baseUnit !== '-' && $factor > 0)
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">≈ Rp{{ number_format($baseUnitCost, 0, ',', '.') }}/{{ $baseUnit }}</p>
                                    @endif
                                    <x-common.input-error :for="'items.'.$i.'.input_unit_cost'" />
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <p class="text-sm font-semibold text-gray-800 dark:text-white/90">{{ $subtotal > 0 ? 'Rp'.number_format($subtotal, 0, ',', '.') : '-' }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <input wire:model.live="items.{{ $i }}.note" type="text" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" @disabled($isLocked) />
                                </td>
                                <td class="px-5 py-4 text-right">
                                    @if (! $isLocked)
                                        <button type="button" x-on:click.prevent="$dispatch('confirm', { message: 'Hapus item ini?', method: 'removeItem', args: [@js((string) ($row['key'] ?? $i))] })" class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                                            Hapus
                                        </button>
                                    @else
                                        <p class="text-sm text-gray-500 dark:text-gray-400">-</p>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <x-common.empty-table-row colspan="6" message="Belum ada item pembelian. Klik “Tambah Item”." />
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
                        $units = [];
                        $baseUnit = '-';
                        $unitFactors = [];
                        foreach ($ingredients as $ingredient) {
                            if ((int) $ingredient['id'] === $ingredientId) {
                                $units = (array) ($ingredient['units'] ?? []);
                                $baseUnit = (string) ($ingredient['base_unit'] ?? '-');
                                $unitFactors = (array) ($ingredient['unit_factors'] ?? []);
                                break;
                            }
                        }
                        $inputQty = (float) (\App\Support\Number\QuantityParser::parse($row['input_quantity'] ?? null) ?? 0);
                        $inputUnit = (string) ($row['input_unit'] ?? '');
                        $inputUnitCost = $row['input_unit_cost'] === null || $row['input_unit_cost'] === '' ? null : (float) $row['input_unit_cost'];
                        $factor = (float) ($unitFactors[$inputUnit] ?? 0);
                        $baseQty = $factor > 0 ? $inputQty * $factor : null;
                        $baseUnitCost = $inputUnitCost !== null && $factor > 0 ? $inputUnitCost / $factor : null;
                        $subtotal = $inputUnitCost === null ? 0 : $inputQty * $inputUnitCost;
                    @endphp
                    <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-semibold text-gray-800 dark:text-white/90">Item {{ number_format($i + 1, 0, ',', '.') }}</p>
                            @if (! $isLocked)
                                <button type="button" x-on:click.prevent="$dispatch('confirm', { message: 'Hapus item ini?', method: 'removeItem', args: [@js((string) ($row['key'] ?? $i))] })" class="text-sm font-semibold text-error-700 hover:text-error-800 dark:text-error-400 dark:hover:text-error-300">
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
                                        <option value="{{ $ingredient['id'] }}">{{ $ingredient['name'] }} ({{ $ingredient['base_unit'] }})</option>
                                    @endforeach
                                </select>
                                <x-common.input-error :for="'items.'.$i.'.ingredient_id'" />
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Qty</label>
                                    <input wire:model.live="items.{{ $i }}.input_quantity" type="text" inputmode="decimal" aria-invalid="{{ $errors->has('items.'.$i.'.input_quantity') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('items.'.$i.'.input_quantity') ? 'error-items-'.$i.'-input_quantity' : '' }}" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 text-right" @disabled($isLocked) />
                                    <x-common.input-error :for="'items.'.$i.'.input_quantity'" />
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Unit</label>
                                    <select wire:model.live="items.{{ $i }}.input_unit" aria-invalid="{{ $errors->has('items.'.$i.'.input_unit') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('items.'.$i.'.input_unit') ? 'error-items-'.$i.'-input_unit' : '' }}" class="shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300" @disabled($isLocked || $ingredientId === 0)>
                                        <option value="">Unit</option>
                                        @foreach ($units as $unit)
                                            <option value="{{ $unit }}">{{ $unit }}</option>
                                        @endforeach
                                    </select>
                                    <x-common.input-error :for="'items.'.$i.'.input_unit'" />
                                </div>
                            </div>

                            @if ($baseQty !== null && $baseUnit !== '-' && $factor > 0)
                                <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-600 dark:border-gray-800 dark:bg-gray-950/30 dark:text-gray-300">
                                    Masuk stok: {{ \App\Support\Number\QuantityFormatter::format($baseQty) }} {{ $baseUnit }}
                                </div>
                            @endif

                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Harga/unit</label>
                                <x-common.rupiah-input
                                    wire-model="items.{{ $i }}.input_unit_cost"
                                    placeholder="0"
                                    :disabled="$isLocked"
                                    input-class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 text-right"
                                />
                                @if ($baseUnitCost !== null && $baseUnit !== '-' && $factor > 0)
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">≈ Rp{{ number_format($baseUnitCost, 0, ',', '.') }}/{{ $baseUnit }}</p>
                                @endif
                                <x-common.input-error :for="'items.'.$i.'.input_unit_cost'" />
                            </div>

                            <div class="flex items-center justify-between rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm dark:border-gray-800 dark:bg-gray-950/30">
                                <p class="text-gray-600 dark:text-gray-300">Subtotal</p>
                                <p class="font-semibold text-gray-800 dark:text-white/90">{{ $subtotal > 0 ? 'Rp'.number_format($subtotal, 0, ',', '.') : '-' }}</p>
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Catatan</label>
                                <input wire:model.live="items.{{ $i }}.note" type="text" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" @disabled($isLocked) />
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="py-10">
                        <p class="text-center text-sm text-gray-500 dark:text-gray-400">Belum ada item pembelian. Klik “Tambah Item”.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    
    @if (!$isLocked)
    @canany(['inventory.purchases.create', 'inventory.purchases.edit', 'inventory.purchases.manage', 'inventory.manage'])
        @if (($purchaseId ? $canEdit : $canCreate))
            <button type="button" wire:click="addItem" class="shadow-theme-md w-full inline-flex h-11 items-center justify-center rounded-lg border border-brand-400 bg-brand-50 px-4 text-sm font-medium text-brand-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                + Tambah Item
            </button>
        @endif
    @endcanany
    @endif

    @if ($receiveConfirmOpen && ! $isLocked)
        <div class="fixed inset-0 z-[100000] flex items-center justify-center p-4" aria-modal="true" role="dialog">
            <div class="absolute inset-0 bg-black/50" wire:click="closeReceiveConfirm"></div>
            <div class="relative w-full max-w-lg overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <div>
                        <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Receive Pembelian</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Aksi ini akan menyimpan draft dan menambah stok.</p>
                    </div>
                    <button type="button" wire:click="closeReceiveConfirm" class="text-sm font-medium text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
                        Tutup
                    </button>
                </div>
                <div class="p-5">
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 dark:border-gray-800 dark:bg-gray-950/40 dark:text-gray-300">
                        Setelah di-receive, dokumen akan menjadi readonly.
                    </div>
                    <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:justify-end">
                        <button type="button" wire:click="closeReceiveConfirm" class="shadow-theme-xs inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                            Batal
                        </button>
                        <button type="button" wire:click="receive" class="bg-emerald-600 shadow-theme-xs hover:bg-emerald-700 inline-flex h-11 items-center justify-center rounded-lg px-4 text-sm font-semibold text-white transition" @disabled(count($items) === 0)>
                            Receive Sekarang
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
                        <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Batalkan Pembelian</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Pembelian draft akan ditandai cancelled dan tidak bisa di-receive.</p>
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
                        <button type="button" wire:click="cancelPurchase" class="bg-error-600 shadow-theme-xs hover:bg-error-700 inline-flex h-11 items-center justify-center rounded-lg px-4 text-sm font-semibold text-white transition">
                            Batalkan Pembelian
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <x-common.confirm-modal confirm-label="Ya, hapus" />
</div>
