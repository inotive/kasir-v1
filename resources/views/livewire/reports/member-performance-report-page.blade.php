@php
    $fmtCurrency = fn ($value) => 'Rp'.number_format((float) $value, 0, ',', '.');
    $fmtPercent = fn ($value) => number_format((float) $value, 1, ',', '.').'%';
    $canViewPii = (bool) ($canViewPii ?? false);
@endphp

<div class="space-y-6">
    <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Laporan Performa Member</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Pantau kontribusi revenue, profit, dan loyalitas member.</p>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
            <a
                href="{{ route('reports.member-performance.excel', ['from' => (string) ($fromDate ?? ''), 'to' => (string) ($toDate ?? ''), 'paymentScope' => (string) ($paymentScope ?? 'paid')]) }}"
                class="shadow-theme-xs inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]"
            >
                Export Excel
            </a>
            <x-common.date-range-picker
                :preset="$rangePreset"
                :from="$fromDate"
                :to="$toDate"
                wire-from-model="fromDate"
                wire-to-model="toDate"
                class="flex flex-col gap-3 sm:flex-row sm:items-center"
            />

            <select wire:model.live="paymentScope" class="shadow-theme-xs h-11 rounded-lg border border-gray-300 bg-white px-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                <option value="paid">Hanya Paid</option>
                <option value="all">Semua Status</option>
            </select>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3 md:gap-6">
        @php
            $cards = [
                ['label' => 'Revenue Member', 'value' => $overview['memberRevenue'] ?? 0, 'fmt' => $fmtCurrency],
                ['label' => 'Profit Member', 'value' => $overview['memberProfit'] ?? 0, 'fmt' => $fmtCurrency],
                ['label' => 'Margin Member', 'value' => $overview['memberMarginPercent'] ?? 0, 'fmt' => $fmtPercent],
                ['label' => 'Transaksi Member', 'value' => $overview['memberTxCount'] ?? 0, 'fmt' => fn($v) => number_format((float) $v, 0, ',', '.')],
                ['label' => 'Active Member', 'value' => $overview['activeMembers'] ?? 0, 'fmt' => fn($v) => number_format((float) $v, 0, ',', '.')],
                ['label' => 'Repeat Rate', 'value' => $overview['repeatRatePercent'] ?? 0, 'fmt' => $fmtPercent],
            ];
        @endphp

        @foreach ($cards as $card)
            <div class="rounded-2xl border border-gray-200 bg-white p-5 md:p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                <p class="text-theme-sm text-gray-500 dark:text-gray-400">{{ $card['label'] }}</p>
                <h4 class="mt-3 text-2xl font-bold text-gray-800 dark:text-white/90">{{ ($card['fmt'])($card['value']) }}</h4>
                @if ($card['label'] === 'Revenue Member')
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Share: {{ number_format((float) ($overview['memberSharePercent'] ?? 0), 1, ',', '.') }}%
                    </p>
                @endif
                @if ($card['label'] === 'Profit Member')
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        HPP: {{ $fmtCurrency((float) ($overview['memberHpp'] ?? 0)) }}
                    </p>
                @endif
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] xl:col-span-2">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Trend Revenue</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Perbandingan revenue member vs non-member per hari.</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-2 text-sm text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                        Coverage HPP: {{ number_format((float) ($overview['hppCoveragePercent'] ?? 0), 1, ',', '.') }}%
                    </div>
                </div>
            </div>
            <div class="p-5">
                <div id="chartThree" data-series='@json($chartSeries)' data-categories='@json($chartCategories)'></div>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Catatan</h3>
            </div>
            <div class="p-5 space-y-3 text-sm text-gray-600 dark:text-gray-300">
                <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-800 dark:bg-gray-900">
                    Avg Order Member: {{ $fmtCurrency((float) ($overview['avgOrder'] ?? 0)) }}
                </div>
                <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-800 dark:bg-gray-900">
                    Item terjual (member): {{ number_format((int) ($overview['memberQty'] ?? 0), 0, ',', '.') }}
                </div>
                <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-800 dark:bg-gray-900">
                    Repeat rate = member dengan ≥ 2 transaksi / active member
                </div>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Peta Persebaran Member</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Titik mewakili kab/kota (centroid), warna berdasarkan jumlah member aktif.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <span class="inline-flex items-center gap-2 rounded-full border border-gray-200 bg-white px-3 py-1 text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                        <span class="h-2 w-2 rounded-full" style="background:#94a3b8"></span> 0
                    </span>
                    <span class="inline-flex items-center gap-2 rounded-full border border-gray-200 bg-white px-3 py-1 text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                        <span class="h-2 w-2 rounded-full" style="background:#ef4444"></span> 1–5
                    </span>
                    <span class="inline-flex items-center gap-2 rounded-full border border-gray-200 bg-white px-3 py-1 text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                        <span class="h-2 w-2 rounded-full" style="background:#f59e0b"></span> 6–20
                    </span>
                    <span class="inline-flex items-center gap-2 rounded-full border border-gray-200 bg-white px-3 py-1 text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                        <span class="h-2 w-2 rounded-full" style="background:#22c55e"></span> 21+
                    </span>
                </div>
            </div>
        </div>
        <div class="p-5">
            <div class="relative">
                <div wire:ignore id="memberMap" class="h-[420px] w-full overflow-hidden rounded-xl border border-gray-200 dark:border-gray-800" data-markers='@json($regionMarkers)'></div>
                @if (count($regionMarkers) === 0)
                    <div class="absolute inset-0 flex items-center justify-center rounded-xl bg-white/70 px-6 text-center backdrop-blur-sm dark:bg-gray-900/70">
                        <p class="text-sm text-gray-600 dark:text-gray-300">Belum ada data wilayah (pastikan member punya Wilayah + GeoJSON, dan ada transaksi pada periode).</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
            <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Top Member (berdasarkan revenue)</h3>
        </div>
        <div class="custom-scrollbar overflow-x-auto">
            <table class="w-full table-auto">
                <thead>
                    <tr class="border-b border-gray-200 dark:divide-gray-800 dark:border-gray-800">
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Member</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Transaksi</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Qty</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Revenue</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Profit</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Margin</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Avg</th>
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Last</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($topMembers as $row)
                        <tr>
                            <td class="px-5 py-4">
                                <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $row['name'] }}</p>
                                @if ($canViewPii)
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $row['phone'] ?? $row['email'] ?? '-' }}</p>
                                @else
                                    <p class="text-xs text-gray-500 dark:text-gray-400">-</p>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                                <p class="text-sm text-gray-800 dark:text-white/90">{{ number_format((int) $row['tx_count'], 0, ',', '.') }}</p>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <p class="text-sm text-gray-800 dark:text-white/90">{{ number_format((int) $row['qty'], 0, ',', '.') }}</p>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <p class="text-sm text-gray-800 dark:text-white/90">{{ $fmtCurrency($row['revenue']) }}</p>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <p class="text-sm font-semibold text-gray-800 dark:text-white/90">{{ $fmtCurrency($row['profit']) }}</p>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <p class="text-sm text-gray-800 dark:text-white/90">{{ number_format((float) $row['margin_percent'], 1, ',', '.') }}%</p>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <p class="text-sm text-gray-800 dark:text-white/90">{{ $fmtCurrency((float) $row['avg_order']) }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <p class="text-sm text-gray-800 dark:text-white/90">{{ $row['last_purchase_at'] ? \Carbon\CarbonImmutable::parse($row['last_purchase_at'])->format('d M Y') : '-' }}</p>
                            </td>
                        </tr>
                    @empty
                        <x-common.empty-table-row colspan="8" message="Belum ada data." />
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
