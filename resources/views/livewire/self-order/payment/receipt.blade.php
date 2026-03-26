@component('layouts.self-order')
    <style>
        @media print {
            @page {
                size: 80mm auto;
                margin: 0;
            }
            html, body {
                width: 80mm;
                margin: 0;
                padding: 0;
                background: #ffffff;
            }
            #receipt-paper {
                width: 80mm;
                max-width: 80mm;
                margin: 0 auto;
            }
            .receipt-card {
                border: none;
                border-radius: 0;
                box-shadow: none;
                background: #ffffff;
            }
            .receipt-header {
                background: #ffffff;
                color: #000000;
                padding: 8px 12px;
            }
            .receipt-header h1,
            .receipt-header p {
                color: #000000;
            }
            .receipt-body {
                padding: 8px 12px;
                color: #000000;
            }
            .receipt-muted {
                color: #4b5563;
            }
            .receipt-item {
                background: transparent;
                padding: 6px 0;
                border-radius: 0;
            }
            .receipt-total {
                color: #000000;
            }
        }
    </style>
    <div class="min-h-screen bg-gray-50 font-poppins flex items-center justify-center p-4 print:bg-white print:p-0 print:min-h-0">
    <div id="receipt-paper" class="w-full max-w-md print:max-w-none print:w-[80mm]">
        <div class="rounded-3xl overflow-hidden bg-white shadow-sm border border-gray-200 receipt-card">
            <div class="text-center p-6 bg-primary-60 receipt-header">
                <h1 class="text-2xl font-bold text-white print:text-black">Struk Pembayaran</h1>
                <p class="text-sm text-white/80 print:text-black">Terima kasih atas pesanan Anda!</p>
            </div>

            <!-- Transaction Info -->
            <div class="p-6 space-y-4 receipt-body">
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-gray-500 receipt-muted">Kode Transaksi</p>
                        <p class="font-semibold text-gray-900">{{ $transaction->code }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-500 receipt-muted">Tanggal</p>
                        <p class="font-semibold text-gray-900">{{ $transaction->updated_at->format('d/m/Y H:i') }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 receipt-muted">Nama Pelanggan</p>
                        <p class="font-semibold text-gray-900">{{ $transaction->name }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-500 receipt-muted">Nomor Meja</p>
                        <p class="font-semibold text-gray-900">{{ optional($transaction->diningTable)->table_number ?? '-' }}</p>
                    </div>
                </div>

                <!-- Divider -->
                <div class="border-t border-dashed border-gray-200"></div>

                <!-- Order Items -->
                <div>
                    <h2 class="font-semibold text-gray-900 mb-2 receipt-total">Detail Pesanan</h2>
                    <ul class="space-y-2">
                        @foreach($transaction->transactionItems as $it)
                            <li class="flex items-start justify-between text-sm p-3 rounded-lg bg-gray-50 receipt-item">
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-gray-800">{{ $it->quantity }}x {{ optional($it->product)->name }}</p>
                                    @php
                                        $variantDisplay = \App\Support\Products\ItemNameFormatter::displayVariantName((int) $it->product_id, $it->variant?->name);
                                    @endphp
                                    @if($variantDisplay !== '')
                                        <p class="text-xs text-gray-500 receipt-muted">({{ $variantDisplay }})</p>
                                    @endif
                                </div>
                                <div class="font-medium text-gray-800 whitespace-nowrap">
                                    Rp{{ number_format($it->subtotal, 0, ',', '.') }}
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <!-- Divider -->
                <div class="border-t border-dashed border-gray-200"></div>

                <!-- Totals -->
                <div class="space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <p class="text-gray-600 receipt-muted">Subtotal</p>
                        <p class="font-semibold text-gray-900">Rp{{ number_format($transaction->subtotal, 0, ',', '.') }}</p>
                    </div>

                    @if($transaction->voucher_discount_amount > 0)
                    <div class="flex items-center justify-between text-success-600">
                        <p class="receipt-muted">Diskon Voucher{{ ! empty($transaction->voucher_code) ? ' ('.$transaction->voucher_code.')' : '' }}</p>
                        <p class="font-semibold">-Rp{{ number_format($transaction->voucher_discount_amount, 0, ',', '.') }}</p>
                    </div>
                    @endif

                    @if($transaction->manual_discount_amount > 0)
                    <div class="flex items-center justify-between text-success-600">
                        <p class="receipt-muted">Diskon Manual</p>
                        <p class="font-semibold">-Rp{{ number_format($transaction->manual_discount_amount, 0, ',', '.') }}</p>
                    </div>
                    @endif

                    @if($transaction->point_discount_amount > 0)
                    <div class="flex items-center justify-between text-success-600">
                        <p class="receipt-muted">Diskon Poin ({{ number_format($transaction->points_redeemed, 0, ',', '.') }} Poin)</p>
                        <p class="font-semibold">-Rp{{ number_format($transaction->point_discount_amount, 0, ',', '.') }}</p>
                    </div>
                    @endif

                    @if($transaction->tax_amount > 0)
                    <div class="flex items-center justify-between">
                        <p class="text-gray-600 receipt-muted">Pajak PB1 ({{ $transaction->tax_percentage }}%)</p>
                        <p class="font-semibold text-gray-900">Rp{{ number_format($transaction->tax_amount, 0, ',', '.') }}</p>
                    </div>
                    @endif

                    @if(($transaction->payment_fee_amount ?? 0) > 0)
                    <div class="flex items-center justify-between">
                        <p class="text-gray-600 receipt-muted">Biaya Admin</p>
                        <p class="font-semibold text-gray-900">Rp{{ number_format($transaction->payment_fee_amount, 0, ',', '.') }}</p>
                    </div>
                    @endif

                    @if($transaction->rounding_amount != 0)
                    <div class="flex items-center justify-between">
                        <p class="text-gray-600 receipt-muted">Pembulatan</p>
                        <p class="font-semibold text-gray-900">Rp{{ number_format($transaction->rounding_amount, 0, ',', '.') }}</p>
                    </div>
                    @endif

                    <div class="flex items-center justify-between text-base">
                        <p class="font-bold text-gray-900 receipt-total">Total</p>
                        <p class="font-bold text-primary-60 receipt-total">Rp{{ number_format($transaction->total, 0, ',', '.') }}</p>
                    </div>

                    @if($transaction->points_earned > 0)
                    <div class="mt-2 flex items-center justify-center rounded-lg bg-green-50 p-2 text-center text-sm font-medium text-green-700">
                        Anda mendapatkan {{ number_format($transaction->points_earned, 0, ',', '.') }} Poin dari transaksi ini!
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="mt-6 flex items-center justify-center gap-4 print:hidden">
            <a href="{{ route('self-order.scan') }}" wire:navigate class="rounded-full px-6 py-3 text-sm font-bold text-gray-700 bg-white border-2 border-gray-200 hover:bg-gray-50">
                Kembali
            </a>
            <button onclick="window.print()" class="rounded-full bg-primary-60 text-white px-8 py-3 text-sm font-bold shadow-lg hover:shadow-xl transition-transform transform hover:scale-105">
                Cetak / Unduh
            </button>
        </div>
    </div>
</div>
@endcomponent
