<div class="min-h-screen bg-gray-50 font-poppins">
    @php
        $rawEmail = (string) ($email ?? '');
        $maskedEmail = '-';
        if ($rawEmail !== '' && filter_var($rawEmail, FILTER_VALIDATE_EMAIL)) {
            [$local, $domain] = array_pad(explode('@', $rawEmail, 2), 2, '');
            $local = (string) $local;
            $domain = (string) $domain;
            if ($local !== '' && $domain !== '') {
                $prefix = mb_substr($local, 0, 1);
                $maskedEmail = $prefix.'***@'.$domain;
            } else {
                $maskedEmail = $rawEmail;
            }
        } elseif ($rawEmail !== '') {
            $maskedEmail = $rawEmail;
        }
    @endphp
    <header class="bg-white border-b border-gray-200">
        <div class="px-4 py-4">
            <div class="flex items-center gap-3">
                <a wire:navigate href="{{ route('self-order.start') }}" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-700 hover:bg-gray-50">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <div class="min-w-0">
                    <div class="text-sm font-bold text-gray-900 truncate">Verifikasi Email</div>
                    <div class="text-xs text-gray-500 truncate">Self Order</div>
                </div>
            </div>
        </div>
    </header>

    <main class="px-4 py-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <div class="text-sm font-semibold text-gray-900">Cek email untuk verifikasi</div>
            <div class="mt-1 text-xs text-gray-600">
                Kami sudah mengirim tautan verifikasi ke:
            </div>
            <div class="mt-2 rounded-xl bg-gray-50 px-4 py-3 text-sm font-semibold text-gray-900">
                {{ $maskedEmail }}
            </div>
            <div class="mt-3 text-xs text-gray-500">
                Jika email tidak masuk, cek folder spam/promosi. Setelah verifikasi, Anda bisa lanjut sebagai member.
            </div>

            @if ($notice)
                <div class="mt-4 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-800">
                    {{ $notice }}
                </div>
            @endif

            <div class="mt-5 grid grid-cols-1 gap-2">
                <button type="button" wire:click="resend" class="bg-primary-60 inline-flex h-11 w-full items-center justify-center rounded-xl px-4 text-sm font-bold text-white hover:bg-primary-70">
                    Kirim Ulang Email Verifikasi
                </button>
                <button type="button" wire:click="continueAsGuest" class="inline-flex h-11 w-full items-center justify-center rounded-xl border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    Kembali ke Start
                </button>
            </div>
        </div>
    </main>
</div>
