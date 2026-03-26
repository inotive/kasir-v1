@props(['transactions' => []])

@php
    $rows = $transactions ?? [];

    $getStatusClasses = function (string $status) {
        $base = 'rounded-full px-2 py-0.5 text-theme-xs font-medium';

        return match ($status) {
            'paid' => $base.' bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500',
            'pending' => $base.' bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-orange-400',
            default => $base.' bg-gray-50 text-gray-600 dark:bg-gray-500/15 dark:text-gray-400',
        };
    };
@endphp

<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white px-4 pb-3 pt-4 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Transaksi Terakhir</h3>
    </div>

    <div class="max-w-full overflow-x-auto custom-scrollbar">
        <table class="min-w-full">
            <thead>
                <tr class="border-t border-gray-100 dark:border-gray-800">
                    <th class="py-3 text-left">
                        <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Kode</p>
                    </th>
                    <th class="py-3 text-left">
                        <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Customer</p>
                    </th>
                    <th class="py-3 text-right">
                        <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Total</p>
                    </th>
                    <th class="py-3 text-center">
                        <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Status</p>
                    </th>
                    <th class="py-3 text-left">
                        <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Waktu</p>
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    @php
                        $statusKey = strtolower((string) ($row['payment_status'] ?? ''));
                        $statusLabel = \App\Helpers\DataLabelHelper::enum($statusKey !== '' ? $statusKey : null, 'payment_status');
                    @endphp
                    <tr class="border-t border-gray-100 dark:border-gray-800">
                        <td class="py-3 whitespace-nowrap">
                            <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90">{{ $row['code'] ?? '-' }}</p>
                        </td>
                        <td class="py-3 whitespace-nowrap">
                            <p class="text-gray-700 text-theme-sm dark:text-gray-300">{{ $row['customer'] ?? '-' }}</p>
                            <p class="text-gray-500 text-theme-xs dark:text-gray-400">{{ $row['phone'] ?? '-' }}</p>
                        </td>
                        <td class="py-3 text-right whitespace-nowrap">
                            <p class="text-gray-500 text-theme-sm dark:text-gray-400">{{ $row['total'] ?? '-' }}</p>
                        </td>
                        <td class="py-3 text-center whitespace-nowrap">
                            <span class="{{ $getStatusClasses($statusKey) }}">{{ $statusLabel }}</span>
                        </td>
                        <td class="py-3 whitespace-nowrap">
                            <p class="text-gray-500 text-theme-sm dark:text-gray-400">{{ $row['created_at'] ?? '-' }}</p>
                        </td>
                    </tr>
                @empty
                    <tr class="border-t border-gray-100 dark:border-gray-800">
                        <td colspan="6" class="py-6">
                            <p class="text-center text-theme-sm text-gray-500 dark:text-gray-400">Belum ada transaksi.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
