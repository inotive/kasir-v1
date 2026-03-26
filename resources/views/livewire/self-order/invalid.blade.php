<div class="min-h-screen bg-gradient-to-b from-brand-25 via-white to-gray-50 px-4 py-8 flex items-center">
    <div class="w-full">
        <div class="rounded-[28px] border border-white/60 bg-white/80 p-6 shadow-theme-lg backdrop-blur">
            <div class="mt-6 flex flex-col items-center gap-4">
                <div class="inline-flex h-24 w-24 items-center justify-center rounded-2xl bg-error-50 text-error-600">
                    <svg class="h-12 w-12" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 9v4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <path d="M12 17h.01" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                        <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="min-w-0 text-center">
                    <div class="text-xl font-extrabold tracking-tight text-gray-900">QR Code tidak valid</div>
                    <div class="mt-2 text-sm font-medium text-gray-700">
                        Untuk mulai self-order, silakan scan QR meja yang tersedia di meja Anda.
                    </div>
                </div>
            </div>

            <div class="mt-5 rounded-2xl border border-gray-200/80 bg-white px-4 py-3 text-sm text-gray-700">
                <div class="font-semibold text-gray-900">Yang bisa Anda lakukan:</div>
                <ul class="mt-2 space-y-1 text-sm text-gray-700">
                    <li>Pastikan QR yang dipindai adalah QR meja.</li>
                    <li>Coba scan ulang dengan pencahayaan cukup.</li>
                    <li>Jika masih gagal, minta bantuan kasir untuk QR meja yang benar.</li>
                </ul>
            </div>

            <div class="mt-6 grid grid-cols-2 gap-3">
                <a
                    href="{{ route('landing') }}"
                    class="inline-flex w-full items-center justify-center rounded-2xl border border-gray-200/80 bg-white px-4 py-3 text-sm font-semibold text-gray-900 shadow-theme-xs hover:bg-gray-50"
                >
                    Kembali
                </a>
                <a
                    href="{{ route('self-order.scan') }}"
                    class="inline-flex w-full items-center justify-center rounded-2xl bg-brand-600 px-4 py-3 text-sm font-semibold text-white shadow-theme-sm hover:bg-brand-700"
                >
                    Scan QR
                </a>
            </div>
        </div>
    </div>
</div>
