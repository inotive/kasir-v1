<div class="min-h-screen bg-gradient-to-b from-brand-25 via-white to-gray-50 px-4 py-8">
    <div class="mx-auto w-full max-w-md">
        <div class="flex items-center justify-between gap-3">
            <a
                href="{{ route('landing') }}"
                class="inline-flex items-center justify-center rounded-2xl border border-gray-200/80 bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-theme-xs hover:bg-gray-50"
            >
                <img src="{{ asset('assets/icons/arrow-left-icon.svg') }}" alt="" class="mr-2 h-4 w-4" />
                Kembali
            </a>
            <a
                href="{{ url('/order/scan') }}"
                class="inline-flex items-center justify-center rounded-2xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-theme-sm hover:bg-brand-700"
            >
                Ulangi
            </a>
        </div>

        <div class="mt-6 rounded-[28px] border border-white/60 bg-white/80 p-6 shadow-theme-lg backdrop-blur">
            <div>
                <div class="text-xl font-extrabold tracking-tight text-gray-900">Scan QR Meja</div>
                <div class="mt-2 text-sm font-medium text-gray-700">
                    Klik “Aktifkan Kamera”, lalu arahkan ke QR Code yang tersedia di meja.
                </div>
            </div>

            <div class="mt-6 overflow-hidden rounded-[22px] border border-gray-200/70 bg-gray-100">
                <div class="relative">
                    <div id="reader" class="w-full min-h-[320px]"></div>
                    <div class="pointer-events-none absolute inset-0 flex items-center justify-center">
                        <div class="h-56 w-56 rounded-[26px] ring-2 ring-white/70"></div>
                    </div>
                </div>
            </div>

            <div id="scan-status" class="mt-4 hidden rounded-2xl border border-gray-200/80 bg-white px-4 py-3 text-sm font-semibold text-gray-700"></div>

            <div class="mt-6 grid gap-3">
                <button
                    type="button"
                    id="start-scan"
                    class="inline-flex w-full items-center justify-center rounded-2xl bg-brand-600 px-4 py-3 text-sm font-semibold text-white shadow-theme-sm hover:bg-brand-700"
                >
                    Aktifkan Kamera
                </button>

                <label
                    for="qr-input-file"
                    class="inline-flex w-full items-center justify-center rounded-2xl border border-gray-200/80 bg-white px-4 py-3 text-sm font-semibold text-gray-900 shadow-theme-xs hover:bg-gray-50"
                >
                    Scan dari Foto QR
                </label>
                <input id="qr-input-file" type="file" accept="image/*" class="hidden" />
            </div>
        </div>

        <div class="mt-4 text-center text-xs font-medium text-gray-600">
            Jika kamera tidak muncul: pastikan izin kamera aktif. Di iPhone, gunakan Safari atau pilih “Scan dari Foto QR”.
        </div>
    </div>
</div>
