<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Riwayat Pemakaian Voucher</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Pantau voucher yang dipakai di transaksi.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('vouchers.index') }}" wire:navigate class="shadow-theme-xs inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                Kembali
            </a>
            <button type="button" wire:click="exportCsv" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex h-11 items-center justify-center rounded-lg px-4 text-sm font-semibold text-white transition">
                Unduh CSV
            </button>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-12">
            <div class="lg:col-span-3">
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Program</label>
                <select wire:model.live="campaignId" class="shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-white px-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                    <option value="">Semua</option>
                    @foreach ($campaigns as $c)
                        <option value="{{ (int) $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2 lg:col-span-5 items-center">
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Filter Tanggal</label>
                <x-common.date-range-picker
                    :preset="$rangePreset"
                    :from="$from"
                    :to="$to"
                    wire-from-model="from"
                    wire-to-model="to"
                    class="flex flex-row gap-3 items-center"
                    select-class="shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-white px-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 sm:w-auto"
                    input-class="h-11 w-full rounded-lg border border-gray-300 bg-white py-2.5 pl-[42px] pr-4 text-sm font-medium text-gray-700 shadow-theme-xs focus:outline-hidden focus:ring-0 focus-visible:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400"
                />
            </div>
            <div class="lg:col-span-2">
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Kode</label>
                <input wire:model.live.debounce.400ms="codeSearch" type="text" class="shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-700 placeholder:text-gray-400 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:placeholder:text-gray-500" placeholder="Kode..." />
            </div>
            <div class="sm:col-span-2 lg:col-span-2">
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Pelanggan</label>
                <input wire:model.live.debounce.400ms="customerSearch" type="text" class="shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-700 placeholder:text-gray-400 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:placeholder:text-gray-500" placeholder="Nama/Telepon..." />
            </div>
        </div>
        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-950">
                <p class="text-xs text-gray-500 dark:text-gray-400">Total dipakai (sesuai filter)</p>
                <p class="mt-1 text-lg font-semibold text-gray-800 dark:text-white/90">{{ number_format((int) $summaryCount, 0, ',', '.') }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-950">
                <p class="text-xs text-gray-500 dark:text-gray-400">Total potongan (sesuai filter)</p>
                <p class="mt-1 text-lg font-semibold text-gray-800 dark:text-white/90">Rp{{ number_format((int) $summaryDiscount, 0, ',', '.') }}</p>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="custom-scrollbar overflow-x-auto">
            <table class="w-full table-auto">
                <thead>
                    <tr class="border-b border-gray-200 dark:divide-gray-800 dark:border-gray-800">
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Waktu</th>
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Program</th>
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Kode</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Diskon</th>
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Pelanggan</th>
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Transaksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($rows as $row)
                        <tr>
                            <td class="px-5 py-4">
                                <p class="text-sm text-gray-800 dark:text-white/90">{{ $row->redeemed_at?->format('d/m/Y H:i') }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <p class="text-sm text-gray-800 dark:text-white/90">{{ $row->campaign?->name ?? '-' }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <p class="text-sm font-semibold text-gray-800 dark:text-white/90">{{ $row->code?->code ?? '-' }}</p>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <p class="text-sm text-gray-800 dark:text-white/90">Rp{{ number_format((int) $row->discount_amount, 0, ',', '.') }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <p class="text-sm text-gray-800 dark:text-white/90">{{ $row->member?->name ?? '-' }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $row->member?->phone ?? ($row->guest_identifier ?? '-') }}</p>
                            </td>
                            <td class="px-5 py-4">
                                @if ($row->transaction)
                                    <a href="{{ route('transactions.show', ['transaction' => $row->transaction->id]) }}" wire:navigate class="text-sm font-semibold text-brand-600 hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300">
                                        {{ $row->transaction->code }}
                                    </a>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Rp{{ number_format((int) $row->transaction->total, 0, ',', '.') }}</p>
                                @else
                                    <p class="text-sm text-gray-500 dark:text-gray-400">-</p>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <x-common.empty-table-row colspan="6" message="Belum ada data pemakaian." />
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-200 px-5 py-4 dark:border-gray-800">
            {{ $rows->links('livewire.pagination.admin') }}
        </div>
    </div>
</div>
