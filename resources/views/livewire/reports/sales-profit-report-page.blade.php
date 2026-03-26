@php
    $range = (array) ($metrics['range'] ?? []);
    $kpi = (array) ($metrics['kpi'] ?? []);
    $current = (array) ($metrics['current'] ?? []);
    $from = $range['from'] ?? null;
    $to = $range['to'] ?? null;
    $prevFrom = $range['prevFrom'] ?? null;
    $prevTo = $range['prevTo'] ?? null;

    $fmtCurrency = fn ($value) => 'Rp'.number_format((float) $value, 0, ',', '.');
    $fmtPercent = fn ($value) => number_format((float) $value, 1, ',', '.').'%';
    $fmtDeltaPercent = function ($value) {
        if ($value === null) {
            return null;
        }

        return number_format((float) $value, 1, ',', '.').'%';
    };

    $badge = function ($deltaUp) {
        return $deltaUp
            ? 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500'
            : 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500';
    };
@endphp

<div class="space-y-6 grid grid-cols-1">
    <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Laporan Penjualan & Laba</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ $from ? $from->format('d M Y') : '-' }} - {{ $to ? $to->format('d M Y') : '-' }}
                @if ($prevFrom && $prevTo)
                    · dibandingkan {{ $prevFrom->format('d M Y') }} - {{ $prevTo->format('d M Y') }}
                @endif
            </p>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
            <a
                href="{{ route('reports.sales-profit.excel', ['from' => (string) ($fromDate ?? ''), 'to' => (string) ($toDate ?? '')]) }}"
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
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3 md:gap-6">
        @php
            $target = (array) ($metrics['target'] ?? []);
            $cards = [
                [
                    'label' => 'Transaksi', 
                    'k' => 'txCount', 
                    'fmt' => fn($v) => number_format((float) $v, 0, ',', '.')],
                [
                    'label' => 'Omzet (Net Sales)',
                    'k' => 'revenue',
                    'fmt' => $fmtCurrency,
                    'subLabel' => 'Avg Order',
                    'subK' => 'avgOrder',
                    'subFmt' => $fmtCurrency,
                ],
                [
                    'label' => 'COGS Penjualan',
                    'k' => 'cogsInventory',
                    'fmt' => $fmtCurrency,
                ],
                [
                    'label' => 'Loss Stok (Net)',
                    'k' => 'stockLossNet',
                    'fmt' => $fmtCurrency,
                ],
                [
                    'label' => 'Total COGS + Loss',
                    'k' => 'cogsTotal',
                    'fmt' => $fmtCurrency,
                ],
                [
                    'label' => 'Laba Kotor (Setelah Loss)',
                    'k' => 'grossProfit',
                    'fmt' => $fmtCurrency,
                    'subLabel' => 'Margin Kotor',
                    'subK' => 'grossMarginPercent',
                    'subFmt' => $fmtPercent,
                ],
                [
                    'label' => 'Beban Operasional',
                    'k' => 'operatingExpenseTotal',
                    'fmt' => $fmtCurrency,
                ],
                [
                    'label' => 'Laba Bersih',
                    'k' => 'netProfit',
                    'fmt' => $fmtCurrency,
                    'subLabel' => 'Margin Bersih',
                    'subK' => 'netMarginPercent',
                    'subFmt' => $fmtPercent,
                ],
            ];
        @endphp

        <div class="rounded-2xl border border-gray-200 bg-white p-5 md:p-6 dark:border-gray-800 dark:bg-white/[0.03] sm:col-span-2 xl:col-span-3">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-theme-sm text-gray-500 dark:text-gray-400">Target Pendapatan</p>
                    <h4 class="mt-2 text-2xl font-bold text-gray-800 dark:text-white/90">{{ $fmtCurrency((float) ($target['targetAmount'] ?? 0)) }}</h4>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ (string) ($target['label'] ?? '') }}
                    </p>
                </div>
                <div class="sm:text-right">
                    <p class="text-theme-sm text-gray-500 dark:text-gray-400">Pencapaian</p>
                    <h4 class="mt-2 text-2xl font-bold text-gray-800 dark:text-white/90">
                        @php
                            $ach = $target['achievementPercent'] ?? null;
                        @endphp
                        {{ $ach === null ? '-' : number_format((float) $ach, 1, ',', '.') . '%' }}
                    </h4>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Selisih: {{ ($target['gapAmount'] ?? null) === null ? '-' : $fmtCurrency((float) $target['gapAmount']) }}
                    </p>
                </div>
            </div>
            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Bulan</label>
                    <select wire:model.live="targetMonth" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full appearance-none rounded-lg border border-gray-300 bg-white px-4 py-2.5 pr-11 text-sm text-gray-700 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                        @foreach ([1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'] as $m => $label)
                            <option value="{{ (int) $m }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Tahun</label>
                    <input wire:model.live="targetYear" type="number" min="2000" max="2100" step="1" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                </div>
                <div>
                    <p class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Realisasi</p>
                    <p class="mt-3 text-base font-semibold text-gray-800 dark:text-white/90">{{ $fmtCurrency((float) ($target['revenueAmount'] ?? 0)) }}</p>
                </div>
            </div>
            <div class="mt-4 h-2 w-full rounded-full bg-gray-100 dark:bg-gray-800">
                @php
                    $percent = (float) ($target['achievementPercent'] ?? 0);
                    $bar = min(100, max(0, $percent));
                @endphp
                <div class="h-2 rounded-full bg-brand-500" style="width: {{ $bar }}%"></div>
            </div>
            @if (! (bool) ($target['hasAnyTarget'] ?? false))
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    Target belum diatur untuk periode ini. Atur di Pengaturan → Target Bulanan.
                </p>
            @endif
        </div>

        @foreach ($cards as $card)
            @php
                $m = (array) ($kpi[$card['k']] ?? []);
                $deltaPercent = $fmtDeltaPercent($m['deltaPercent'] ?? null);
                $subMetric = array_key_exists('subK', $card) ? (array) ($kpi[$card['subK']] ?? []) : [];
            @endphp
            <div class="rounded-2xl border border-gray-200 bg-white p-5 md:p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-theme-sm text-gray-500 dark:text-gray-400">{{ $card['label'] }}</p>
                        <h4 class="mt-3 text-2xl font-bold text-gray-800 dark:text-white/90">{{ ($card['fmt'])($m['value'] ?? 0) }}</h4>
                    </div>
                    @if ($deltaPercent !== null)
                        <span class="rounded-full px-2 py-0.5 text-theme-xs font-medium {{ $badge((bool) ($m['deltaUp'] ?? true)) }}">
                            {{ ($m['deltaUp'] ?? true) ? '+' : '' }}{{ $deltaPercent }}
                        </span>
                    @endif
                </div>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    Periode sebelumnya: {{ ($card['fmt'])($m['previous'] ?? 0) }}
                </p>
                @if (array_key_exists('subK', $card))
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ (string) ($card['subLabel'] ?? '') }}: {{ ($card['subFmt'])($subMetric['value'] ?? 0) }}
                    </p>
                @endif
            </div>
        @endforeach
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-gray-800 dark:text-white/90">Cara Baca</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Ringkasan definisi angka agar laporan mudah dipahami (best practice UMKM).</p>
            </div>
            <span class="inline-flex items-center rounded-full border border-brand-200 bg-brand-50 px-2 py-0.5 text-theme-xs font-semibold text-brand-700 dark:border-brand-500/20 dark:bg-brand-500/10 dark:text-brand-300">
                UMKM
            </span>
        </div>

        <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
            <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-900">
                <p class="text-xs font-semibold text-gray-700 dark:text-gray-300">Rumus Utama</p>
                <div class="mt-3 space-y-3 text-sm text-gray-700 dark:text-gray-300">
                    <div class="flex items-start justify-between gap-4">
                        <span class="text-gray-500 dark:text-gray-400">Omzet (Net Sales)</span>
                        <span class="font-semibold text-gray-800 dark:text-white/90">subtotal item − diskon item</span>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <span class="text-gray-500 dark:text-gray-400">COGS Penjualan</span>
                        <span class="font-semibold text-gray-800 dark:text-white/90">pemakaian stok dari penjualan</span>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <span class="text-gray-500 dark:text-gray-400">Loss Stok (Net)</span>
                        <span class="font-semibold text-gray-800 dark:text-white/90">waste + usage + opname + penyesuaian</span>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <span class="text-gray-500 dark:text-gray-400">Laba Kotor</span>
                        <span class="font-semibold text-gray-800 dark:text-white/90">omzet − (COGS + loss stok)</span>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <span class="text-gray-500 dark:text-gray-400">Laba Bersih</span>
                        <span class="font-semibold text-gray-800 dark:text-white/90">laba kotor − beban operasional</span>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-900">
                <p class="text-xs font-semibold text-gray-700 dark:text-gray-300">Catatan Praktis</p>
                <div class="mt-3 space-y-2 text-sm text-gray-700 dark:text-gray-300">
                    <div class="flex items-start gap-2">
                        <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-gray-400 dark:bg-gray-500"></span>
                        <span>Biaya admin adalah ditagihkan ke customer (informasi), bukan pengurang laba.</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-gray-400 dark:bg-gray-500"></span>
                        <span>Pajak PB1 tidak dimasukkan ke omzet (omzet dihitung dari item setelah diskon).</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-gray-400 dark:bg-gray-500"></span>
                        <span>Jika HPP belum lengkap, pastikan stok & harga pokok inventory sudah diproses.</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-gray-400 dark:bg-gray-500"></span>
                        <span>Jika loss stok besar, cek menu Pergerakan Stok dan Stock Opname.</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] xl:col-span-2">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Trend Harian</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Omzet, HPP, dan Laba Kotor per hari.</p>
                    </div>
                </div>
            </div>
            <div class="p-5">
                <div id="chartThree" data-series='@json($chartSeries)' data-categories='@json($chartCategories)'></div>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Metode Pembayaran</h3>
            </div>
            <div class="p-5 space-y-4">
                @forelse ($paymentMethods as $row)
                    <div>
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ \App\Helpers\DataLabelHelper::enum($row['payment_method'] ?? null, 'payment_method') }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ number_format((float) $row['percent'], 1, ',', '.') }}%</p>
                        </div>
                        <div class="mt-2 h-2 w-full rounded-full bg-gray-100 dark:bg-gray-800">
                            <div class="h-2 rounded-full bg-brand-500" style="width: {{ min(100, max(0, (float) $row['percent'])) }}%"></div>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $fmtCurrency($row['revenue']) }}</p>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada data.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Top 10 Profit (Per Menu)</h3>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Perhitungan profit per menu mengikuti HPP resep yang tersimpan.</p>
            </div>
            <div class="custom-scrollbar overflow-x-auto">
                <table class="w-full table-auto">
                    <thead>
                        <tr class="border-b border-gray-200 dark:divide-gray-800 dark:border-gray-800">
                            <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Item</th>
                            <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Qty</th>
                            <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Omzet</th>
                            <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Profit (Resep)</th>
                            <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Margin</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($topByProfit as $row)
                            <tr>
                                <td class="px-5 py-4">
                                    <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $row['product_name'] }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $row['variant_name'] }}</p>
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
                            </tr>
                        @empty
                            <x-common.empty-table-row colspan="5" message="Belum ada data." />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Top 10 Omzet (Per Menu)</h3>
            </div>
            <div class="custom-scrollbar overflow-x-auto">
                <table class="w-full table-auto">
                    <thead>
                        <tr class="border-b border-gray-200 dark:divide-gray-800 dark:border-gray-800">
                            <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Item</th>
                            <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Qty</th>
                            <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Omzet</th>
                            <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Profit (Resep)</th>
                            <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Margin</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($topByRevenue as $row)
                            <tr>
                                <td class="px-5 py-4">
                                    <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $row['product_name'] }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $row['variant_name'] }}</p>
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
                            </tr>
                        @empty
                            <x-common.empty-table-row colspan="5" message="Belum ada data." />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
            <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Ringkasan Harian</h3>
        </div>
        <div class="custom-scrollbar overflow-x-auto">
            <table class="w-full table-auto">
                <thead>
                    <tr class="border-b border-gray-200 dark:divide-gray-800 dark:border-gray-800">
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Tanggal</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Transaksi</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Item</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Omzet</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">COGS Penjualan</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Loss Stok (Net)</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Total COGS</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Laba Kotor</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Margin</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Biaya Admin</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Beban</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Laba Bersih</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($dailyRows as $row)
                        <tr>
                            <td class="px-5 py-4">
                                <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ \Carbon\CarbonImmutable::parse($row['day'])->format('d M Y') }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $row['day'] }}</p>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <p class="text-sm text-gray-800 dark:text-white/90">{{ number_format((int) $row['tx_count'], 0, ',', '.') }}</p>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <p class="text-sm text-gray-800 dark:text-white/90">{{ number_format((int) $row['items_qty'], 0, ',', '.') }}</p>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <p class="text-sm text-gray-800 dark:text-white/90">{{ $fmtCurrency($row['revenue']) }}</p>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <p class="text-sm text-gray-800 dark:text-white/90">{{ $fmtCurrency($row['cogs_sales'] ?? 0) }}</p>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <p class="text-sm text-gray-800 dark:text-white/90">{{ $fmtCurrency($row['stock_loss_net'] ?? 0) }}</p>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <p class="text-sm text-gray-800 dark:text-white/90">{{ $fmtCurrency($row['cogs_total'] ?? 0) }}</p>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <p class="text-sm font-semibold text-gray-800 dark:text-white/90">{{ $fmtCurrency($row['gross_profit'] ?? 0) }}</p>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <p class="text-sm text-gray-800 dark:text-white/90">{{ number_format((float) ($row['gross_margin_percent'] ?? 0), 1, ',', '.') }}%</p>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <p class="text-sm text-gray-800 dark:text-white/90">{{ $fmtCurrency($row['payment_fee_total'] ?? 0) }}</p>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <p class="text-sm text-gray-800 dark:text-white/90">{{ $fmtCurrency($row['operating_expense_total'] ?? 0) }}</p>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <p class="text-sm font-semibold text-gray-800 dark:text-white/90">{{ $fmtCurrency($row['net_profit'] ?? 0) }}</p>
                            </td>
                        </tr>
                    @empty
                        <x-common.empty-table-row colspan="12" message="Belum ada data." />
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
