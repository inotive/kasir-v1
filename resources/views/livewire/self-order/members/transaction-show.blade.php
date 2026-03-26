<div class="flex min-h-screen flex-col font-poppins bg-gray-50 pb-10">
    <livewire:self-order.components.page-title-nav :title="'Detail Transaksi'" :hasBack="true" :backTransactions="true" :hasFilter="false" />

    <div class="container mx-auto px-4 mt-4 space-y-4">
        @if (! $transaction)
            <div class="bg-white border border-gray-200 rounded-2xl p-4">
                <div class="text-sm font-semibold text-gray-900">Transaksi tidak ditemukan</div>
                <div class="text-xs text-gray-600 mt-1">Silakan kembali ke riwayat transaksi.</div>
            </div>
        @else
            <div class="rounded-2xl bg-white border border-gray-100 p-4 shadow-sm space-y-2">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="text-sm font-bold text-gray-900">{{ (string) $transaction->code }}</div>
                        <div class="text-xs text-gray-500 mt-1">
                            {{ optional($transaction->created_at)->format('d/m/Y H:i') }}
                            @if ($transaction->diningTable)
                                <span class="mx-1">•</span>
                                Meja #{{ (string) $transaction->diningTable->table_number }}
                            @endif
                        </div>
                    </div>
                    <div class="shrink-0 text-right">
                        <div class="text-xs text-gray-500">Total</div>
                        <div class="text-sm font-bold text-primary-60">
                            Rp {{ number_format((int) ($transaction->total ?? 0), 0, ',', '.') }}
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 pt-2">
                    <div class="rounded-xl bg-gray-50 border border-gray-200 p-3">
                        <div class="text-[10px] text-gray-500">Status Pembayaran</div>
                        <div class="text-xs font-bold text-gray-900 mt-1">{{ \App\Helpers\DataLabelHelper::enum($transaction->payment_status ?? null, 'payment_status') }}</div>
                    </div>
                    <div class="rounded-xl bg-gray-50 border border-gray-200 p-3">
                        <div class="text-[10px] text-gray-500">Metode Bayar</div>
                        <div class="text-xs font-bold text-gray-900 mt-1">{{ \App\Helpers\DataLabelHelper::enum($transaction->payment_method ?? null, 'payment_method') }}</div>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl bg-white border border-gray-100 p-4 shadow-sm">
                <div class="text-sm font-bold text-gray-900">Item</div>

                <div class="mt-3 space-y-3">
                    @foreach ($transaction->transactionItems->whereNull('parent_transaction_item_id') as $item)
                        <div class="border-b border-gray-100 pb-3 last:border-b-0 last:pb-0">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="text-sm font-semibold text-gray-900 truncate">
                                        {{ (string) ($item->product?->name ?? '') }}
                                        @php
                                            $variantDisplay = \App\Support\Products\ItemNameFormatter::displayVariantName((int) $item->product_id, $item->variant?->name);
                                        @endphp
                                        @if ($variantDisplay !== '')
                                            <span class="text-xs text-gray-500">- {{ $variantDisplay }}</span>
                                        @endif
                                    </div>
                                    @if (is_string($item->note) && trim($item->note) !== '')
                                        <div class="text-xs text-gray-500 mt-1">Catatan: {{ (string) $item->note }}</div>
                                    @endif
                                </div>
                                <div class="shrink-0 text-right">
                                    <div class="text-xs text-gray-500">x{{ (int) ($item->quantity ?? 0) }}</div>
                                    <div class="text-sm font-bold text-gray-900">
                                        Rp {{ number_format((int) ($item->subtotal ?? 0), 0, ',', '.') }}
                                    </div>
                                </div>
                            </div>

                            @if ($item->childTransactionItems && $item->childTransactionItems->count() > 0)
                                <div class="mt-2 space-y-2">
                                    @foreach ($item->childTransactionItems as $child)
                                        <div class="flex items-start justify-between gap-3 pl-4">
                                            <div class="min-w-0">
                                                <div class="text-xs font-semibold text-gray-700 truncate">
                                                    {{ (string) ($child->product?->name ?? '') }}
                                                    @php
                                                        $childVariantDisplay = \App\Support\Products\ItemNameFormatter::displayVariantName((int) $child->product_id, $child->variant?->name);
                                                    @endphp
                                                    @if ($childVariantDisplay !== '')
                                                        <span class="text-[11px] text-gray-500">- {{ $childVariantDisplay }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="shrink-0 text-right">
                                                <div class="text-[11px] text-gray-500">x{{ (int) ($child->quantity ?? 0) }}</div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-2xl bg-white border border-gray-100 p-4 shadow-sm space-y-2">
                <div class="text-sm font-bold text-gray-900">Ringkasan</div>

                <div class="flex items-center justify-between text-sm">
                    <div class="text-gray-600">Subtotal</div>
                    <div class="font-semibold text-gray-900">Rp {{ number_format((int) ($transaction->subtotal ?? 0), 0, ',', '.') }}</div>
                </div>

                <div class="flex items-center justify-between text-sm">
                    <div class="text-gray-600">Diskon Voucher</div>
                    <div class="font-semibold text-gray-900">Rp {{ number_format((int) ($transaction->voucher_discount_amount ?? 0), 0, ',', '.') }}</div>
                </div>

                <div class="flex items-center justify-between text-sm">
                    <div class="text-gray-600">Diskon Manual</div>
                    <div class="font-semibold text-gray-900">Rp {{ number_format((int) ($transaction->manual_discount_amount ?? 0), 0, ',', '.') }}</div>
                </div>

                <div class="flex items-center justify-between text-sm">
                    <div class="text-gray-600">Diskon Poin</div>
                    <div class="font-semibold text-gray-900">Rp {{ number_format((int) ($transaction->point_discount_amount ?? 0), 0, ',', '.') }}</div>
                </div>

                <div class="flex items-center justify-between text-sm">
                    <div class="text-gray-600">Poin Dipakai</div>
                    <div class="font-semibold text-gray-900">{{ number_format((int) ($transaction->points_redeemed ?? 0), 0, ',', '.') }}</div>
                </div>

                <div class="flex items-center justify-between text-sm">
                    <div class="text-gray-600">Poin Didapat</div>
                    <div class="font-semibold text-gray-900">{{ number_format((int) ($transaction->points_earned ?? 0), 0, ',', '.') }}</div>
                </div>

                <div class="flex items-center justify-between text-sm">
                    <div class="text-gray-600">Pajak</div>
                    <div class="font-semibold text-gray-900">Rp {{ number_format((int) ($transaction->tax_amount ?? 0), 0, ',', '.') }}</div>
                </div>

                <div class="flex items-center justify-between text-sm">
                    <div class="text-gray-600">Biaya</div>
                    <div class="font-semibold text-gray-900">Rp {{ number_format((int) ($transaction->payment_fee_amount ?? 0), 0, ',', '.') }}</div>
                </div>

                <div class="flex items-center justify-between text-sm">
                    <div class="text-gray-600">Pembulatan</div>
                    <div class="font-semibold text-gray-900">Rp {{ number_format((int) ($transaction->rounding_amount ?? 0), 0, ',', '.') }}</div>
                </div>

                <div class="pt-2 border-t border-gray-100 flex items-center justify-between">
                    <div class="text-sm font-bold text-gray-900">Total</div>
                    <div class="text-sm font-bold text-primary-60">Rp {{ number_format((int) ($transaction->total ?? 0), 0, ',', '.') }}</div>
                </div>
            </div>
        @endif
    </div>
</div>
