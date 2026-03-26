@php
    $statusLower = strtolower((string) $status);
    $isCashierPending = ((string) ($transaction->payment_method ?? '') === 'cash') && in_array($statusLower, ['pending', 'unpaid'], true);
    $isOnlinePending = ! $isCashierPending && in_array($statusLower, ['pending', 'unpaid'], true);
    $isFinalFailed = in_array($statusLower, ['failed', 'expired', 'canceled'], true);
    $checkoutLink = (string) ($transaction->checkout_link ?? '');
    $hasCheckoutLink = $checkoutLink !== '' && $checkoutLink !== '-';
@endphp

<div class="mx-auto max-w-md min-h-screen font-poppins bg-gray-50 flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md" @class(['opacity-80' => $status === 'expired' || $status === 'failed'])>
        <div>
            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-primary-60 text-white mx-auto mb-4">
                    @if(in_array(strtolower($status), ['paid','settled']))
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    @elseif(in_array(strtolower($status), ['expired','failed','canceled']))
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M12 3a9 9 0 100 18 9 9 0 000-18z"></path>
                        </svg>
                    @else
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    @endif
                </div>
                <h1 class="text-2xl font-bold text-gray-900 mb-2">
                    @if ($isCashierPending)
                        Silakan Bayar di Kasir
                    @else
                        @switch(strtolower($status))
                            @case('paid') Pembayaran Berhasil! @break
                            @case('settled') Pembayaran Berhasil! @break
                            @case('expired') Pembayaran Kedaluwarsa @break
                            @case('failed') Pembayaran Gagal @break
                            @case('canceled') Pembayaran Dibatalkan @break
                            @default Menunggu Pembayaran
                        @endswitch
                    @endif
                </h1>
                <p class="text-sm text-gray-600">
                    @if ($isCashierPending)
                        Tunjukkan kode transaksi dan nomor meja ke kasir.
                    @else
                        @switch(strtolower($status))
                            @case('paid') Pesanan sudah kami terima dan sedang diproses. @break
                            @case('settled') Pesanan sudah kami terima dan sedang diproses. @break
                            @case('expired') Waktu pembayaran telah habis. Silakan buat pesanan ulang. @break
                            @case('failed') Pembayaran tidak berhasil diproses. Silakan coba metode lain. @break
                            @case('canceled') Pembayaran dibatalkan. Silakan pilih metode pembayaran lain. @break
                            @default Selesaikan pembayaran Anda. Setelah terkonfirmasi, status akan ter-update otomatis.
                        @endswitch
                    @endif
                </p>
            </div>

            <div class="rounded-3xl overflow-hidden bg-white shadow-sm border border-gray-200">
                <div class="bg-primary-60 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-sm font-semibold text-white/90">Kode Transaksi</span>
                        </div>
                        <span class="text-sm font-bold text-white">{{ $transaction->code }}</span>
                    </div>
                </div>

                <div class="p-6 space-y-4">
                    @if ($isCashierPending)
                        <div class="rounded-2xl border border-primary-20 bg-primary-10/40 p-4">
                            <p class="text-sm font-semibold text-gray-900">Instruksi Pembayaran di Kasir</p>
                            <ol class="mt-2 space-y-1 text-sm text-gray-700 list-decimal list-inside">
                                <li>Sebutkan kode transaksi: <span class="font-bold">{{ $transaction->code }}</span></li>
                                @if(optional($transaction->diningTable)->table_number)
                                    <li>Sebutkan nomor meja: <span class="font-bold">{{ optional($transaction->diningTable)->table_number }}</span></li>
                                @endif
                                <li>Jika sudah membayar, status akan berubah dan pesanan akan diproses.</li>
                            </ol>
                        </div>
                    @endif

                    @if ($isOnlinePending)
                        <div class="rounded-2xl border border-primary-100 bg-primary-10/40 p-4 space-y-3">
                            <div>
                                <p class="text-sm font-bold text-gray-900">Pembayaran Online</p>
                                <p class="mt-0.5 text-xs text-gray-600">Selesaikan pembayaran melalui tombol di bawah. Setelah terkonfirmasi, status akan ter-update otomatis.</p>
                            </div>

                            @if ($hasCheckoutLink)
                                <a href="{{ $checkoutLink }}" class="flex w-full items-center justify-center gap-2 rounded-2xl bg-primary-60 hover:bg-primary-70 px-6 py-3 text-white font-bold text-sm">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                                    </svg>
                                    <span>Lanjutkan Pembayaran</span>
                                </a>
                            @endif

                            <a href="{{ route('self-order.payment.page') }}" wire:navigate class="flex w-full items-center justify-center gap-2 rounded-2xl bg-white border border-gray-200 hover:border-primary-40 px-6 py-3 text-gray-900 font-bold text-sm">
                                <span>Ganti Metode Pembayaran</span>
                            </a>
                        </div>
                    @endif

                    @if ($isFinalFailed)
                        <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4 space-y-3">
                            <div>
                                <p class="text-sm font-bold text-gray-900">Pembayaran Tidak Berhasil</p>
                                <p class="mt-0.5 text-xs text-gray-600">Anda bisa kembali ke checkout untuk mencoba lagi atau kembali ke keranjang.</p>
                            </div>
                            <a href="{{ route('self-order.payment.cart') }}" wire:navigate class="flex w-full items-center justify-center gap-2 rounded-2xl bg-primary-60 hover:bg-primary-70 px-6 py-3 text-white font-bold text-sm">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                <span>Kembali ke Keranjang</span>
                            </a>
                        </div>
                    @endif

                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-gray-50 rounded-2xl p-4 border border-gray-200">
                            <div class="flex justify-center text-center items-center gap-2 mb-2">
                                <svg class="w-4 h-4 text-primary-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                                <span class="text-xs text-gray-600">Nomor Meja</span>
                            </div>
                            <div class="text-md text-center font-bold text-gray-900">
                                {{ optional($transaction->diningTable)->table_number }}
                            </div>
                        </div>

                        <div class="bg-gray-50 rounded-2xl p-4 border border-gray-200">
                            <div class="flex justify-center text-center items-center gap-2 mb-2">
                                <svg class="w-4 h-4 text-primary-50" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"></path>
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="text-xs text-gray-600">Total Bayar</span>
                            </div>
                            <div class="text-md text-center font-bold text-primary-70">
                                Rp {{ number_format($transaction->total, 0, ',', '.') }}
                            </div>
                        </div>
                    </div>

                    <div class="relative py-2">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t-2 border-dashed border-gray-200"></div>
                        </div>
                        <div class="relative flex justify-center">
                            <span class="bg-white px-3 text-xs font-semibold text-gray-500">Detail Pesanan</span>
                        </div>
                    </div>

                    <div class="space-y-2 max-h-64 overflow-y-auto">
                        @foreach($transaction->transactionItems as $it)
                            <div class="flex items-start justify-between p-3 rounded-xl bg-gray-50 border border-gray-200">
                                <div class="flex items-start gap-3 flex-1 min-w-0">
                                    <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-primary-60 text-white text-xs font-bold flex-shrink-0">
                                        {{ $it->quantity }}x
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-gray-900 line-clamp-2">
                                            {{ optional($it->product)->name }}
                                            @if($it->variant)
                                                <span class="text-xs text-gray-500">({{ $it->variant->name }})</span>
                                            @endif
                                        </p>
                                        @if($it->note)
                                            <p class="text-xs text-gray-500 line-clamp-2">
                                                {{ $it->note }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                                <div class="text-sm font-bold text-gray-900 ml-2 whitespace-nowrap">
                                    Rp {{ number_format($it->subtotal, 0, ',', '.') }}
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="relative py-2">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t-2 border-dashed border-gray-200"></div>
                        </div>
                        <div class="relative flex justify-center">
                            <span class="bg-white px-3 text-xs font-semibold text-gray-500">Rincian Pembayaran</span>
                        </div>
                    </div>

                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between text-gray-600">
                            <span>Subtotal</span>
                            <span class="font-semibold">Rp {{ number_format($transaction->subtotal, 0, ',', '.') }}</span>
                        </div>
                        @if(((int) ($transaction->voucher_discount_amount ?? 0)) > 0)
                        <div class="flex justify-between text-gray-600">
                            <span>Diskon Voucher{{ ! empty($transaction->voucher_code) ? ' ('.$transaction->voucher_code.')' : '' }}</span>
                            <span class="font-semibold">-Rp {{ number_format((int) $transaction->voucher_discount_amount, 0, ',', '.') }}</span>
                        </div>
                        @endif
                        @if(((int) ($transaction->point_discount_amount ?? 0)) > 0)
                        <div class="flex justify-between text-gray-600">
                            <span>Diskon Poin ({{ number_format((int) ($transaction->points_redeemed ?? 0), 0, ',', '.') }} poin)</span>
                            <span class="font-semibold">-Rp {{ number_format((int) $transaction->point_discount_amount, 0, ',', '.') }}</span>
                        </div>
                        @endif
                        @if($transaction->tax_amount > 0)
                        <div class="flex justify-between text-gray-600">
                            <span>Pajak PB1 ({{ $transaction->tax_percentage }}%)</span>
                            <span class="font-semibold">Rp {{ number_format($transaction->tax_amount, 0, ',', '.') }}</span>
                        </div>
                        @endif
                        @if(($transaction->payment_fee_amount ?? 0) > 0)
                        <div class="flex justify-between text-gray-600">
                            <span>Biaya Admin</span>
                            <span class="font-semibold">Rp {{ number_format($transaction->payment_fee_amount, 0, ',', '.') }}</span>
                        </div>
                        @endif
                        @if($transaction->rounding_amount != 0)
                        <div class="flex justify-between text-gray-600">
                            <span>Pembulatan</span>
                            <span class="font-semibold">Rp {{ number_format($transaction->rounding_amount, 0, ',', '.') }}</span>
                        </div>
                        @endif
                        <div class="flex justify-between text-gray-900 text-base font-bold pt-2 border-t border-gray-100">
                            <span>Total</span>
                            <span class="text-primary-70">Rp {{ number_format($transaction->total, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <div class="space-y-3 pt-2">
                        @if(in_array(strtolower($status), ['paid','settled']))
                            <a href="{{ route('self-order.payment.receipt', ['code' => $transaction->code, 'token' => $transaction->self_order_token]) }}" target="_blank"
                               class="flex w-full items-center justify-center gap-2 rounded-2xl bg-primary-60 hover:bg-primary-70 px-6 py-4 text-white font-bold text-sm">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <span>Unduh Struk Digital</span>
                            </a>
                        @endif
                        <a href="{{ route('self-order.home') }}" wire:navigate 
                           class="flex w-full items-center justify-center gap-2 rounded-2xl bg-white border-2 border-gray-200 hover:border-primary-40 px-6 py-4 text-gray-900 font-bold text-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                            </svg>
                            <span>Kembali ke Menu</span>
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
