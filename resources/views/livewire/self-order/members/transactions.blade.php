<div class="flex min-h-screen flex-col font-poppins bg-gray-50 pb-10">
    <livewire:self-order.components.page-title-nav :title="'Riwayat Transaksi'" :hasBack="true" :hasFilter="false" />

    <div class="container mx-auto px-4 mt-4 space-y-3">
        @php $transactions = $this->transactions; @endphp

        @if ($transactions->isEmpty())
            <div class="bg-white border border-gray-200 rounded-2xl p-4">
                <div class="text-sm font-semibold text-gray-900">Belum ada transaksi</div>
                <div class="text-xs text-gray-600 mt-1">Riwayat transaksi member akan tampil di sini.</div>
            </div>
        @else
            <div class="space-y-3">
                @foreach ($transactions as $trx)
                    <a
                        wire:navigate
                        href="{{ route('self-order.member.transactions.show', ['transaction' => $trx->id]) }}"
                        class="block rounded-2xl bg-white border border-gray-100 p-4 shadow-sm hover:bg-gray-50 transition-colors">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="text-sm font-bold text-gray-900 truncate">{{ (string) $trx->code }}</div>
                                <div class="text-xs text-gray-500 mt-1">
                                    {{ optional($trx->created_at)->format('d/m/Y H:i') }}
                                    <span class="mx-1">•</span>
                                    {{ \App\Helpers\DataLabelHelper::enum($trx->channel ?? null, 'channel') }}
                                </div>
                                <div class="text-xs text-gray-500 mt-1">
                                    Bayar: {{ \App\Helpers\DataLabelHelper::enum($trx->payment_status ?? null, 'payment_status') }}
                                </div>

                                @if ((int) ($trx->points_earned ?? 0) > 0 || (int) ($trx->points_redeemed ?? 0) > 0)
                                    <div class="text-xs text-gray-500 mt-1">
                                        @if ((int) ($trx->points_earned ?? 0) > 0)
                                            <span>Poin +{{ number_format((int) $trx->points_earned, 0, ',', '.') }}</span>
                                        @endif
                                        @if ((int) ($trx->points_earned ?? 0) > 0 && (int) ($trx->points_redeemed ?? 0) > 0)
                                            <span class="mx-1">•</span>
                                        @endif
                                        @if ((int) ($trx->points_redeemed ?? 0) > 0)
                                            <span>Poin -{{ number_format((int) $trx->points_redeemed, 0, ',', '.') }}</span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                            <div class="shrink-0 text-right">
                                <div class="text-xs text-gray-500">Total</div>
                                <div class="text-sm font-bold text-primary-60">
                                    Rp {{ number_format((int) ($trx->total ?? 0), 0, ',', '.') }}
                                </div>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="pt-2">
                {{ $transactions->links('livewire.pagination.self-order') }}
            </div>
        @endif
    </div>
</div>
