@props([
    'stats' => [
        'totalTransactions' => 0,
        'totalRevenue' => 0,
        'avgRevenue' => 0,
        'totalItemsSold' => 0,
    ],
])

@php
    $totalTransactions = (int) ($stats['totalTransactions'] ?? 0);
    $totalRevenue = (int) ($stats['totalRevenue'] ?? 0);
    $avgRevenue = (int) ($stats['avgRevenue'] ?? 0);
    $totalItemsSold = (int) ($stats['totalItemsSold'] ?? 0);
@endphp

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4 md:gap-6">
    <div class="rounded-2xl border border-gray-200 bg-white p-5 md:p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <p class="text-theme-sm text-gray-500 dark:text-gray-400">Total Transaksi</p>
        <div class="mt-3 flex items-end justify-between">
            <h4 class="text-2xl font-bold text-gray-800 dark:text-white/90">{{ number_format($totalTransactions, 0, ',', '.') }}</h4>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-5 md:p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <p class="text-theme-sm text-gray-500 dark:text-gray-400">Omzet (Net Sales)</p>
        <div class="mt-3 flex items-end justify-between">
            <h4 class="text-2xl font-bold text-gray-800 dark:text-white/90">Rp{{ number_format($totalRevenue, 0, ',', '.') }}</h4>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-5 md:p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <p class="text-theme-sm text-gray-500 dark:text-gray-400">Rata-rata Omzet</p>
        <div class="mt-3 flex items-end justify-between">
            <h4 class="text-2xl font-bold text-gray-800 dark:text-white/90">Rp{{ number_format($avgRevenue, 0, ',', '.') }}</h4>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-5 md:p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <p class="text-theme-sm text-gray-500 dark:text-gray-400">Item Terjual</p>
        <div class="mt-3 flex items-end justify-between">
            <h4 class="text-2xl font-bold text-gray-800 dark:text-white/90">{{ number_format($totalItemsSold, 0, ',', '.') }}</h4>
        </div>
    </div>
</div>
