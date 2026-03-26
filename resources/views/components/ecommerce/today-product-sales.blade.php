@props(['products' => [], 'totalRevenue' => 0, 'selectedDate' => ''])

@php
    $rows = $products ?? [];
    $total = (int) ($totalRevenue ?? 0);
    $date = $selectedDate ?? now()->toDateString();
    $formattedDate = \Illuminate\Support\Carbon::parse($date)->format('d M Y');
@endphp

<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white px-4 pb-3 pt-4 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Penjualan Produk</h3>
        <x-common.date-picker
            wire-model="productSalesDate"
            :default-today="false"
            input-class="h-9 w-auto rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-medium text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400"
        />
    </div>

    <div class="mb-4 flex items-center justify-between rounded-lg bg-gray-50 px-4 py-3 dark:bg-gray-800/50">
        <div>
            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Pendapatan</span>
            <p class="text-xs text-gray-500 dark:text-gray-500">{{ $formattedDate }}</p>
        </div>
        <span class="text-lg font-bold text-gray-800 dark:text-white">Rp{{ number_format($total, 0, ',', '.') }}</span>
    </div>

    <div class="max-h-[320px] overflow-y-auto custom-scrollbar">
        <table class="min-w-full">
            <thead>
                <tr class="border-t border-gray-100 dark:border-gray-800">
                    <th class="py-3 text-left">
                        <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Produk</p>
                    </th>
                    <th class="py-3 text-right">
                        <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Terjual</p>
                    </th>
                    <th class="py-3 text-right">
                        <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Total</p>
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    @php
                        $image = (string) ($row['image'] ?? '');
                        $imageUrl = $image === '' ? asset('/images/product/product-01.jpg') : (str_starts_with($image, '/') || str_starts_with($image, 'http') ? asset($image) : asset('storage/'.$image));
                        $itemTotal = (int) ($row['total'] ?? 0);
                    @endphp
                    <tr class="border-t border-gray-100 dark:border-gray-800">
                        <td class="py-3 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                <div class="h-[44px] w-[44px] overflow-hidden rounded-md bg-gray-100 dark:bg-gray-800">
                                    <img src="{{ $imageUrl }}" alt="{{ $row['name'] ?? '-' }}" class="h-full w-full object-cover" />
                                </div>
                                <div>
                                    <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90">{{ $row['name'] ?? '-' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="py-3 text-right whitespace-nowrap">
                            <p class="text-gray-700 text-theme-sm dark:text-gray-300">{{ number_format((int) ($row['sold'] ?? 0), 0, '.', ',') }}</p>
                        </td>
                        <td class="py-3 text-right whitespace-nowrap">
                            <p class="text-gray-700 text-theme-sm dark:text-gray-300">Rp{{ number_format($itemTotal, 0, ',', '.') }}</p>
                        </td>
                    </tr>
                @empty
                    <tr class="border-t border-gray-100 dark:border-gray-800">
                        <td colspan="4" class="py-6">
                            <p class="text-center text-theme-sm text-gray-500 dark:text-gray-400">Belum ada penjualan.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
