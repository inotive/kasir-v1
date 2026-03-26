<div class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="max-w-md w-full bg-white shadow rounded-2xl p-6 text-center">
        <h1 class="text-xl font-bold text-gray-900 mb-2">Verifikasi Member</h1>
        @if(!empty($error))
            <p class="text-sm text-red-600 mb-4">{{ $error }}</p>
        @else
            <p class="text-sm text-gray-600 mb-4">Email Anda telah diverifikasi. Terima kasih telah menjadi member.</p>
        @endif
        <p class="text-xs text-gray-500 mb-6">Untuk memesan, buka kembali halaman self-order dari QR meja Anda.</p>
        <a href="/" class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-primary-60 hover:bg-primary-70 text-white font-semibold">
            Selesai
        </a>
    </div>
</div>
