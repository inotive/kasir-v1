<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Kartu Stok</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Riwayat pergerakan stok per bahan baku beserta saldo berjalan.</p>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
            <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
                    <select wire:model.live="ingredientId" class="shadow-theme-xs h-11 rounded-lg border border-gray-300 bg-white px-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                        <option value="">Pilih bahan</option>
                        @foreach ($ingredients as $ingredient)
                            <option value="{{ $ingredient->id }}">{{ $ingredient->name }} ({{ $ingredient->unit }})</option>
                        @endforeach
                    </select>

                    <x-common.date-range-picker
                        :preset="$rangePreset"
                        :from="$fromDate"
                        :to="$toDate"
                        wire-from-model="fromDate"
                        wire-to-model="toDate"
                        class="flex flex-col gap-3 sm:flex-row sm:items-center"
                    />
                </div>

                @php
                    $selectedUnit = (string) ($selectedIngredient?->unit ?? '');
                    $fromLabel = $fromDate ? \Carbon\CarbonImmutable::parse($fromDate)->format('d M Y') : null;
                @endphp
                <div class="flex flex-col items-start gap-2 xl:items-end">
                    <a
                        href="{{ $ingredientId ? route('inventory-reports.stock-card.excel', ['ingredientId' => (int) $ingredientId, 'from' => (string) ($fromDate ?? ''), 'to' => (string) ($toDate ?? '')]) : '#' }}"
                        @class([
                            'shadow-theme-xs inline-flex h-11 items-center justify-center rounded-lg border px-4 text-sm font-semibold transition',
                            'border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]' => (bool) $ingredientId,
                            'border-gray-200 bg-gray-100 text-gray-400 cursor-not-allowed dark:border-gray-800 dark:bg-gray-950 dark:text-gray-600' => ! (bool) $ingredientId,
                        ])
                        @if (! $ingredientId) aria-disabled="true" @endif
                    >
                        Export Excel
                    </a>

                    <div class="w-full rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 xl:w-auto">
                        <p>
                            Saldo awal{{ $fromLabel ? ' (sebelum '.$fromLabel.')' : '' }}:
                            {{ \App\Support\Number\QuantityFormatter::format((float) $startingBalance) }}{{ $selectedUnit !== '' ? ' '.$selectedUnit : '' }}
                        </p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Nilai saldo awal: Rp{{ number_format((float) ($startingValue ?? 0), 0, ',', '.') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        @if (! $ingredientId)
            <div class="px-5 py-10">
                <p class="text-center text-sm text-gray-500 dark:text-gray-400">Pilih bahan untuk melihat kartu stok.</p>
            </div>
        @else
            <div class="custom-scrollbar overflow-x-auto">
                <table class="w-full table-auto">
                    <thead>
                        <tr class="border-b border-gray-200 dark:divide-gray-800 dark:border-gray-800">
                            <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Waktu</th>
                            <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Tipe</th>
                            <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Supplier</th>
                            <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Qty</th>
                            <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Saldo</th>
                            <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">HPP/Unit</th>
                            <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Nilai</th>
                            <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Nilai Saldo</th>
                            <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Ref</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($movements as $movement)
                            @php
                                $qty = (float) $movement->quantity;
                                $qtyText = \App\Support\Number\QuantityFormatter::format(abs($qty));
                                $balance = (float) ($balances[$movement->id] ?? 0);
                                $when = $movement->happened_at ?? $movement->created_at;
                                $unitCost = $movement->unit_cost === null ? null : (float) $movement->unit_cost;
                                $deltaValue = (float) ($values[$movement->id] ?? 0);
                                $runningValue = (float) ($runningValues[$movement->id] ?? 0);

                                $typeLabel = match ((string) $movement->type) {
                                    'purchase' => 'Pembelian',
                                    'sale_consumption' => 'Penjualan (Konsumsi)',
                                    'sale_reversal' => 'Pembatalan Penjualan',
                                    'usage' => 'Pemakaian',
                                    'waste' => 'Waste',
                                    'adjustment' => 'Penyesuaian',
                                    'opname_adjustment' => 'Stock Opname',
                                    default => (string) $movement->type,
                                };

                                $typeBadgeClass = match ((string) $movement->type) {
                                    'purchase' => 'bg-success-50 text-success-600 border-success-200 dark:bg-success-500/10 dark:text-success-500 dark:border-success-500/20',
                                    'sale_consumption' => 'bg-error-50 text-error-600 border-error-200 dark:bg-error-500/10 dark:text-error-500 dark:border-error-500/20',
                                    'sale_reversal' => 'bg-success-50 text-success-600 border-success-200 dark:bg-success-500/10 dark:text-success-500 dark:border-success-500/20',
                                    'usage' => 'bg-error-50 text-error-600 border-error-200 dark:bg-error-500/10 dark:text-error-500 dark:border-error-500/20',
                                    'waste' => 'bg-error-50 text-error-600 border-error-200 dark:bg-error-500/10 dark:text-error-500 dark:border-error-500/20',
                                    'adjustment' => 'bg-brand-50 text-brand-600 border-brand-200 dark:bg-brand-500/10 dark:text-brand-400 dark:border-brand-500/20',
                                    'opname_adjustment' => 'bg-warning-50 text-warning-700 border-warning-200 dark:bg-warning-500/10 dark:text-warning-400 dark:border-warning-500/20',
                                    default => 'bg-gray-50 text-gray-700 border-gray-200 dark:bg-white/[0.03] dark:text-gray-300 dark:border-gray-800',
                                };

                                $refType = (string) ($movement->reference_type ?? '');
                                $refId = (int) ($movement->reference_id ?? 0);
                                $refCode = $refType !== '' && $refId > 0 ? (string) (($refCodes[$refType][$refId] ?? '') ?: '') : '';
                                $refText = $refType !== '' && $refId > 0 ? $refType.'#'.$refId : '-';

                                if ($refType === 'transactions' && $refCode !== '') {
                                    $refText = 'Transaksi '.$refCode;
                                } elseif ($refType === 'purchases' && $refCode !== '') {
                                    $refText = 'Pembelian '.$refCode;
                                } elseif ($refType === 'stock_opnames' && $refCode !== '') {
                                    $refText = 'Opname '.$refCode;
                                }

                                $refUrl = null;
                                if ($refType === 'transactions' && $refId) {
                                    $refUrl = route('transactions.show', ['transaction' => $refId]);
                                } elseif ($refType === 'purchases' && $refId) {
                                    $refUrl = route('purchases.edit', ['purchase' => $refId]);
                                } elseif ($refType === 'stock_opnames' && $refId) {
                                    $refUrl = route('stock-opnames.edit', ['stockOpname' => $refId]);
                                }
                            @endphp
                            <tr>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ optional($when)->format('d M Y') }}</p>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-theme-xs font-semibold {{ $typeBadgeClass }}">{{ $typeLabel }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <p class="text-sm text-gray-800 dark:text-white/90">{{ $movement->supplier?->name ?? '-' }}</p>
                                </td>
                                <td class="px-5 py-4 text-right whitespace-nowrap">
                                    <p class="text-sm font-semibold {{ $qty < 0 ? 'text-error-600 dark:text-error-500' : 'text-success-600 dark:text-success-500' }}">
                                        {{ $qty < 0 ? '-' : '+' }}{{ $qtyText }}{{ $selectedUnit !== '' ? ' '.$selectedUnit : '' }}
                                    </p>
                                </td>
                                <td class="px-5 py-4 text-right whitespace-nowrap">
                                    <p class="text-sm font-semibold text-gray-800 dark:text-white/90">
                                        {{ \App\Support\Number\QuantityFormatter::format($balance) }}{{ $selectedUnit !== '' ? ' '.$selectedUnit : '' }}
                                    </p>
                                </td>
                                <td class="px-5 py-4 text-right whitespace-nowrap">
                                    <p class="text-sm text-gray-800 dark:text-white/90">
                                        {{ $unitCost === null ? '-' : 'Rp'.number_format($unitCost, 0, ',', '.') }}
                                    </p>
                                </td>
                                <td class="px-5 py-4 text-right whitespace-nowrap">
                                    <p class="text-sm text-gray-800 dark:text-white/90">
                                        {{ 'Rp'.number_format($deltaValue, 0, ',', '.') }}
                                    </p>
                                </td>
                                <td class="px-5 py-4 text-right whitespace-nowrap">
                                    <p class="text-sm font-semibold text-gray-800 dark:text-white/90">
                                        {{ 'Rp'.number_format($runningValue, 0, ',', '.') }}
                                    </p>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    @if ($refUrl)
                                        <a href="{{ $refUrl }}" class="text-sm font-medium text-brand-600 hover:underline dark:text-brand-400">
                                            {{ $refText }}
                                        </a>
                                    @else
                                        <p class="text-sm text-gray-800 dark:text-white/90">{{ $refText }}</p>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <x-common.empty-table-row colspan="9" message="Data tidak ditemukan." />
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-200 px-5 py-4 dark:border-gray-800">
                {{ $movements->links('livewire.pagination.admin') }}
            </div>
        @endif
    </div>
</div>
