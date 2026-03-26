<div class="grid grid-cols-1 gap-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Riwayat Transaksi</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Pantau ringkasan transaksi dan detail penjualan.</p>
        </div>
    </div>

    <x-common.date-range-picker
        :preset="$rangePreset"
        :from="$fromDate"
        :to="$toDate"
        wire-from-model="fromDate"
        wire-to-model="toDate"
        class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center"
    />

    <x-transaction.history-metrics :stats="$stats" />

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="custom-scrollbar overflow-x-auto border-b border-gray-200 px-5 py-4 dark:border-gray-800">
            <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                <div class="relative flex-1 xl:flex-none">
                    <span class="absolute top-1/2 left-4 -translate-y-1/2 text-gray-500 dark:text-gray-400">
                        <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M3.04199 9.37363C3.04199 5.87693 5.87735 3.04199 9.37533 3.04199C12.8733 3.04199 15.7087 5.87693 15.7087 9.37363C15.7087 12.8703 12.8733 15.7053 9.37533 15.7053C5.87735 15.7053 3.04199 12.8703 3.04199 9.37363ZM9.37533 1.54199C5.04926 1.54199 1.54199 5.04817 1.54199 9.37363C1.54199 13.6991 5.04926 17.2053 9.37533 17.2053C11.2676 17.2053 13.0032 16.5344 14.3572 15.4176L17.1773 18.238C17.4702 18.5309 17.945 18.5309 18.2379 18.238C18.5308 17.9451 18.5309 17.4703 18.238 17.1773L15.4182 14.3573C16.5367 13.0033 17.2087 11.2669 17.2087 9.37363C17.2087 5.04817 13.7014 1.54199 9.37533 1.54199Z" fill="" />
                        </svg>
                    </span>
                    <input wire:model.live.debounce.400ms="search" type="text" placeholder="Cari kode/nama/telepon..." class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent py-2.5 pr-4 pl-11 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden xl:w-[340px] xl:min-w-[340px] dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="flex flex-col gap-3 sm:flex-row">
                        <select wire:model.live="paymentStatus" class="shadow-theme-xs h-11 rounded-lg border border-gray-300 bg-white px-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                            <option value="">Semua Status</option>
                            @foreach ($paymentStatusOptions as $status)
                                <option value="{{ $status }}">{{ \App\Helpers\DataLabelHelper::enum($status, 'payment_status') }}</option>
                            @endforeach
                        </select>

                        <select wire:model.live="paymentMethod" class="shadow-theme-xs h-11 rounded-lg border border-gray-300 bg-white px-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                            <option value="">Semua Metode</option>
                            @foreach ($paymentMethodOptions as $method)
                                <option value="{{ $method }}">{{ \App\Helpers\DataLabelHelper::enum($method, 'payment_method') }}</option>
                            @endforeach
                        </select>

                        <select wire:model.live="orderType" class="shadow-theme-xs h-11 rounded-lg border border-gray-300 bg-white px-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                            <option value="">Semua Tipe</option>
                            @foreach ($orderTypeOptions as $type)
                                <option value="{{ $type }}">{{ $type === 'dine_in' ? 'Dine in' : 'Take away' }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        @php
            $canActions = (bool) (auth()->user()?->can('transactions.details') || auth()->user()?->can('transactions.print'));
        @endphp
        <div class="custom-scrollbar overflow-x-auto">
            <table class="w-full table-auto">
                <thead>
                    <tr class="border-b border-gray-200 dark:divide-gray-800 dark:border-gray-800">
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">
                            <button type="button" wire:click="sortBy('created_at')" class="flex items-center gap-2">
                                Tanggal
                            </button>
                        </th>
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Kode</th>
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Pelanggan</th>
                        <th class="px-5 py-4 text-center text-xs font-medium text-gray-500 dark:text-gray-400">Tipe</th>
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Pembayaran</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">
                            <button type="button" wire:click="sortBy('total')" class="ml-auto flex items-center justify-end gap-2">
                                Total
                            </button>
                        </th>
                        @if ($canActions)
                            <th class="px-5 py-4 text-center text-xs font-medium text-gray-500 dark:text-gray-400">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($transactions as $transaction)
                        @php
                            $customer = (string) ($transaction->member?->name ?? $transaction->name ?? '-');
                            $orderType = (string) ($transaction->order_type ?? '');
                            $paymentMethodKey = (string) ($transaction->payment_method ?? '');
                            $paymentMethodLabel = \App\Helpers\DataLabelHelper::enum($paymentMethodKey, 'payment_method');
                            $paymentStatusKey = (string) ($transaction->payment_status ?? '');
                            $paymentStatusLabel = \App\Helpers\DataLabelHelper::enum($paymentStatusKey, 'payment_status');
                        @endphp
                        <tr>
                            <td class="px-5 py-4">
                                <div class="space-y-1">
                                    <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ optional($transaction->created_at)->format('d M Y') }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ optional($transaction->created_at)->format('H:i') }}</p>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $transaction->code }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <p class="text-sm text-gray-800 dark:text-white/90">{{ $customer }}</p>
                                @can('transactions.pii.view')
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $transaction->phone ?? $transaction->email }}</p>
                                @else
                                    <p class="text-xs text-gray-500 dark:text-gray-400">-</p>
                                @endcan
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-center">
                                <span class="rounded-full px-2 py-0.5 text-theme-xs font-medium bg-gray-50 text-gray-600 dark:bg-gray-500/15 dark:text-gray-400">
                                    {{ $orderType === 'dine_in' ? 'Dine in' : 'Take away' }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="space-y-1">
                                    <p class="text-sm text-gray-800 dark:text-white/90">{{ $paymentMethodLabel }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $paymentStatusLabel }}</p>
                                    @if ((int) ($transaction->manual_discount_amount ?? 0) > 0)
                                        <p class="text-xs font-semibold text-error-700 dark:text-error-400">Diskon Manual</p>
                                    @endif
                                </div>
                            </td>
                           
                            <td class="px-5 py-4 text-right">
                                <p class="text-sm font-semibold text-gray-800 dark:text-white/90">Rp{{ number_format((int) $transaction->total, 0, ',', '.') }}</p>
                            </td>
                            @if ($canActions)
                                <td class="px-5 py-4 text-center">
                                    <div class="inline-flex items-center gap-2">
                                        @can('transactions.print')
                                            <button type="button" wire:click="printTransaction({{ (int) $transaction->id }})" class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                                                Cetak Struk
                                            </button>
                                        @endcan
                                        @can('transactions.details')
                                            <a href="{{ route('transactions.show', $transaction) }}" wire:navigate class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                                                Detail
                                            </a>
                                        @endcan
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canActions ? 10 : 9 }}" class="px-5 py-10">
                                <p class="text-center text-sm text-gray-500 dark:text-gray-400">Transaksi tidak ditemukan.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-200 px-5 py-4 dark:border-gray-800">
            {{ $transactions->links('livewire.pagination.admin') }}
        </div>
    </div>
</div>
