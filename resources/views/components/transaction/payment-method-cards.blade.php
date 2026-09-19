@props([
    'stats' => [
        'cash' => ['count' => 0, 'revenue' => 0],
        'qris' => ['count' => 0, 'revenue' => 0],
    ],
])

@php
    $cashCount = (int) ($stats['cash']['count'] ?? 0);
    $cashRevenue = (int) ($stats['cash']['revenue'] ?? 0);
    $qrisCount = (int) ($stats['qris']['count'] ?? 0);
    $qrisRevenue = (int) ($stats['qris']['revenue'] ?? 0);
@endphp

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6">
    <div class="rounded-2xl border border-gray-200 bg-white p-5 md:p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <p class="text-theme-sm text-gray-500 dark:text-gray-400">Tunai</p>
        <div class="mt-3 flex items-end justify-between">
            <h4 class="text-2xl font-bold text-gray-800 dark:text-white/90">Rp{{ number_format($cashRevenue, 0, ',', '.') }}</h4>
            <span class="text-theme-sm text-gray-500 dark:text-gray-400">{{ number_format($cashCount, 0, ',', '.') }} transaksi</span>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-5 md:p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <p class="text-theme-sm text-gray-500 dark:text-gray-400">QRIS</p>
        <div class="mt-3 flex items-end justify-between">
            <h4 class="text-2xl font-bold text-gray-800 dark:text-white/90">Rp{{ number_format($qrisRevenue, 0, ',', '.') }}</h4>
            <span class="text-theme-sm text-gray-500 dark:text-gray-400">{{ number_format($qrisCount, 0, ',', '.') }} transaksi</span>
        </div>
    </div>
</div>
