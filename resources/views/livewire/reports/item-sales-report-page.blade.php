@php
    $fmtCurrency = fn ($value) => 'Rp'.number_format((float) $value, 0, ',', '.');
@endphp

<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Penjualan per Item</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Rangkuman omzet per produk untuk rentang yang dipilih.</p>
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

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-xs text-gray-500 dark:text-gray-400">Omzet Periode Ini</p>
            <p class="mt-1 text-xl font-semibold text-gray-800 dark:text-white/90">{{ $fmtCurrency($totalRevenue) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-xs text-gray-500 dark:text-gray-400">Item Terjual</p>
            <p class="mt-1 text-xl font-semibold text-gray-800 dark:text-white/90">{{ number_format($totalQty, 0, ',', '.') }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-xs text-gray-500 dark:text-gray-400">Item Terlaris</p>
            <p class="mt-1 text-xl font-semibold text-gray-800 dark:text-white/90">{{ $topProductName }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] xl:col-span-1">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Proporsi Omzet per Item</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Top {{ min($detailRows->count(), 8) }} produk, sisanya digabung "Lainnya".</p>
            </div>
            <div class="p-5">
                <div id="chartFour" data-series='@json($chartSeries)' data-labels='@json($chartLabels)'></div>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] xl:col-span-2">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Detail per Produk</h3>
            </div>
            <div class="custom-scrollbar overflow-x-auto">
                <table class="w-full table-auto">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-800">
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Produk</th>
                            <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Qty</th>
                            <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Omzet</th>
                            <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400">% Omzet</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($detailRows as $row)
                            <tr>
                                <td class="px-5 py-3 text-sm font-medium text-gray-800 dark:text-white/90">{{ $row['product_name'] }}</td>
                                <td class="px-5 py-3 text-right text-sm text-gray-700 dark:text-gray-300">{{ number_format($row['qty'], 0, ',', '.') }}</td>
                                <td class="px-5 py-3 text-right text-sm text-gray-700 dark:text-gray-300">{{ $fmtCurrency($row['revenue']) }}</td>
                                <td class="px-5 py-3 text-right text-sm text-gray-700 dark:text-gray-300">{{ number_format($row['percent'], 1, ',', '.') }}%</td>
                            </tr>
                        @empty
                            <x-common.empty-table-row colspan="4" message="Belum ada penjualan pada rentang ini." />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
