<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Performa Voucher</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Persentase transaksi yang memakai voucher dan dampaknya pada rata-rata nilai transaksi.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('vouchers.index') }}" wire:navigate class="shadow-theme-xs inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                Kembali
            </a>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-6">
            <x-common.date-range-picker
                :preset="$rangePreset"
                :from="$from"
                :to="$to"
                wire-from-model="from"
                wire-to-model="to"
                class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center lg:col-span-4"
                select-class="shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-white px-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 sm:w-auto"
                input-class="h-11 w-full rounded-lg border border-gray-200 bg-white py-2.5 pl-[42px] pr-4 text-sm font-medium text-gray-700 shadow-theme-xs focus:outline-hidden focus:ring-0 focus-visible:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400"
            />
            <div class="lg:col-span-2 flex items-end justify-end">
                <a href="{{ route('vouchers.redemptions') }}" wire:navigate class="shadow-theme-xs inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                    Lihat Riwayat Pakai
                </a>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-950">
                <p class="text-xs text-gray-500 dark:text-gray-400">Total transaksi</p>
                <p class="mt-1 text-lg font-semibold text-gray-800 dark:text-white/90">{{ number_format((int) $totalTx, 0, ',', '.') }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-950">
                <p class="text-xs text-gray-500 dark:text-gray-400">Transaksi pakai voucher</p>
                <p class="mt-1 text-lg font-semibold text-gray-800 dark:text-white/90">{{ number_format((int) $txWithVoucher, 0, ',', '.') }}</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Persentase pakai: {{ number_format((float) $conversionAll, 2, ',', '.') }}%</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-950">
                <p class="text-xs text-gray-500 dark:text-gray-400">Total diskon voucher</p>
                <p class="mt-1 text-lg font-semibold text-gray-800 dark:text-white/90">Rp{{ number_format((int) $discountTotal, 0, ',', '.') }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-950">
                <p class="text-xs text-gray-500 dark:text-gray-400">Rata-rata nilai transaksi (AOV)</p>
                <p class="mt-1 text-sm font-semibold text-gray-800 dark:text-white/90">Rp{{ number_format((int) $avgWithVoucher, 0, ',', '.') }} <span class="text-xs font-medium text-gray-500 dark:text-gray-400">vs</span> Rp{{ number_format((int) $avgWithoutVoucher, 0, ',', '.') }}</p>
                @php
                    $impactAll = (float) $avgWithVoucher - (float) $avgWithoutVoucher;
                @endphp
                <p class="mt-1 text-xs font-semibold {{ $impactAll >= 0 ? 'text-success-600 dark:text-success-500' : 'text-error-600 dark:text-error-500' }}">
                    Selisih: {{ $impactAll >= 0 ? '+' : '' }}Rp{{ number_format($impactAll, 0, ',', '.') }}
                </p>
            </div>
        </div>

        <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">AOV dihitung dari nilai transaksi setelah refund (jika ada). Data transaksi dihitung dari status terbayar dan refund.</p>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="custom-scrollbar overflow-x-auto">
            <table class="w-full table-auto">
                <thead>
                    <tr class="border-b border-gray-200 dark:divide-gray-800 dark:border-gray-800">
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Program</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Transaksi</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Persentase Pakai</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Diskon Total</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Rata-rata (Voucher)</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Rata-rata (Tanpa Voucher)</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Selisih</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($rows as $row)
                        <tr>
                            <td class="px-5 py-4">
                                <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $row['campaign_name'] }}</p>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <p class="text-sm text-gray-800 dark:text-white/90">{{ number_format((int) $row['tx_count'], 0, ',', '.') }}</p>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <p class="text-sm text-gray-800 dark:text-white/90">{{ number_format((float) $row['conversion_rate'], 2, ',', '.') }}%</p>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <p class="text-sm text-gray-800 dark:text-white/90">Rp{{ number_format((int) $row['discount_sum'], 0, ',', '.') }}</p>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <p class="text-sm text-gray-800 dark:text-white/90">Rp{{ number_format((float) $row['avg_total'], 0, ',', '.') }}</p>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <p class="text-sm text-gray-800 dark:text-white/90">Rp{{ number_format((float) $row['avg_without_voucher'], 0, ',', '.') }}</p>
                            </td>
                            <td class="px-5 py-4 text-right">
                                @php
                                    $impact = (float) $row['aov_impact'];
                                @endphp
                                <p class="text-sm font-semibold {{ $impact >= 0 ? 'text-success-600 dark:text-success-500' : 'text-error-600 dark:text-error-500' }}">
                                    {{ $impact >= 0 ? '+' : '' }}Rp{{ number_format($impact, 0, ',', '.') }}
                                </p>
                            </td>
                        </tr>
                    @empty
                        <x-common.empty-table-row colspan="7" message="Belum ada data." />
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
