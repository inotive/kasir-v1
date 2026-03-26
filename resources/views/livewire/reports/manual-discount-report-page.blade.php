<div class="space-y-6">
    @php
        $fromLabel = $from ? \Carbon\CarbonImmutable::parse($from)->format('d M Y') : '-';
        $toLabel = $to ? \Carbon\CarbonImmutable::parse($to)->format('d M Y') : '-';
        $periodLabel = $fromLabel.' – '.$toLabel;
        $cashierName = null;
        foreach ($cashiers as $u) {
            if ((int) $u->id === (int) ($cashierId ?? 0)) {
                $cashierName = (string) $u->name;
                break;
            }
        }
        $cashierLabel = $cashierName ?: 'Semua kasir';
        $txTotal = method_exists($rows, 'total') ? (int) $rows->total() : 0;
        $avgDiscount = $txTotal > 0 ? ((float) $totalDiscount / $txTotal) : 0.0;
        $fmtCurrency = fn ($value) => 'Rp'.number_format((float) $value, 0, ',', '.');
    @endphp

    <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Laporan Diskon Manual</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Periode: {{ $periodLabel }} · Kasir: {{ $cashierLabel }}
            </p>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
            <a
                href="{{ route('reports.manual-discount.excel', ['from' => (string) ($from ?? ''), 'to' => (string) ($to ?? ''), 'cashierId' => (string) ($cashierId ?? '')]) }}"
                class="shadow-theme-xs inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]"
            >
                Export Excel
            </a>

            <x-common.date-range-picker
                :preset="$rangePreset"
                :from="$from"
                :to="$to"
                wire-from-model="from"
                wire-to-model="to"
                class="flex flex-col gap-3 sm:flex-row sm:items-center"
                select-class="shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-white px-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 sm:w-auto"
                input-class="h-11 w-full rounded-lg border border-gray-200 bg-white py-2.5 pl-[42px] pr-4 text-sm font-medium text-gray-700 shadow-theme-xs focus:outline-hidden focus:ring-0 focus-visible:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400"
            />

            <div class="w-full sm:w-auto">
                <label class="sr-only">Kasir</label>
                <select wire:model.live="cashierId" class="shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-white px-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 sm:w-[220px]">
                    <option value="">Semua kasir</option>
                    @foreach ($cashiers as $u)
                        <option value="{{ (int) $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3 md:gap-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 md:p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-theme-sm text-gray-500 dark:text-gray-400">Total Diskon</p>
            <h4 class="mt-3 text-2xl font-bold text-gray-800 dark:text-white/90">{{ $fmtCurrency((float) $totalDiscount) }}</h4>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 md:p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-theme-sm text-gray-500 dark:text-gray-400">Jumlah Transaksi</p>
            <h4 class="mt-3 text-2xl font-bold text-gray-800 dark:text-white/90">{{ number_format($txTotal, 0, ',', '.') }}</h4>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 md:p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-theme-sm text-gray-500 dark:text-gray-400">Rata-rata Diskon/Transaksi</p>
            <h4 class="mt-3 text-2xl font-bold text-gray-800 dark:text-white/90">{{ $fmtCurrency($avgDiscount) }}</h4>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="custom-scrollbar overflow-x-auto">
            <table class="w-full table-auto">
                <thead>
                    <tr class="border-b border-gray-200 dark:divide-gray-800 dark:border-gray-800">
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Waktu</th>
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Transaksi</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Diskon</th>
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Kasir</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($rows as $t)
                        <tr>
                            <td class="px-5 py-4">
                                <p class="text-sm text-gray-800 dark:text-white/90">{{ $t->created_at?->format('d/m/Y H:i') }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ \App\Helpers\DataLabelHelper::enum($t->channel ?? null, 'channel') }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <a href="{{ route('transactions.show', ['transaction' => $t->id]) }}" wire:navigate class="text-sm font-semibold text-brand-600 hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300">
                                    {{ $t->code }}
                                </a>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Total: Rp{{ number_format((int) $t->total, 0, ',', '.') }}</p>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <p class="text-sm font-semibold text-gray-800 dark:text-white/90">Rp{{ number_format((int) $t->manual_discount_amount, 0, ',', '.') }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <p class="text-sm text-gray-800 dark:text-white/90">{{ $t->manualDiscountByUser?->name ?? '-' }}</p>
                            </td>
                        </tr>
                    @empty
                        <x-common.empty-table-row colspan="4" message="Belum ada data." />
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-200 px-5 py-4 dark:border-gray-800">
            {{ $rows->links('livewire.pagination.admin') }}
        </div>
    </div>
</div>
