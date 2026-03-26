@component('layouts.self-order')
    <div class="mx-auto max-w-md min-h-screen font-poppins bg-gray-50 flex items-center justify-center px-4 py-12">
        <div class="w-full rounded-3xl overflow-hidden bg-white shadow-sm border border-gray-200">
            <div class="p-6 text-center">
                <div class="mx-auto mb-4 inline-flex h-20 w-20 items-center justify-center rounded-full bg-error-600 text-white">
                    <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M12 3a9 9 0 100 18 9 9 0 000-18z"></path>
                    </svg>
                </div>

                <h1 class="text-xl font-bold text-gray-900">Pembayaran Gagal</h1>
                <p class="mt-2 text-sm text-gray-600">Silakan coba lagi atau pilih metode pembayaran lain.</p>
            </div>

            <div class="border-t border-gray-200 p-6 space-y-3">
                <a href="{{ route('self-order.payment.page') }}" wire:navigate class="flex w-full items-center justify-center gap-2 rounded-2xl bg-primary-60 hover:bg-primary-70 px-6 py-4 text-white font-bold text-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <span>Coba Lagi</span>
                </a>

                <a href="{{ route('self-order.payment.cart') }}" wire:navigate class="flex w-full items-center justify-center gap-2 rounded-2xl bg-white border-2 border-gray-200 hover:border-primary-40 px-6 py-4 text-gray-900 font-bold text-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13l-1.5 7h13L17 13M7 13H5.4M10 21a1 1 0 100-2 1 1 0 000 2zm8 0a1 1 0 100-2 1 1 0 000 2z"></path>
                    </svg>
                    <span>Kembali ke Keranjang</span>
                </a>

                <a href="{{ route('self-order.home') }}" wire:navigate class="flex w-full items-center justify-center gap-2 rounded-2xl bg-gray-50 hover:bg-gray-100 px-6 py-4 text-gray-900 font-bold text-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    <span>Kembali ke Menu</span>
                </a>
            </div>
        </div>
    </div>
@endcomponent

