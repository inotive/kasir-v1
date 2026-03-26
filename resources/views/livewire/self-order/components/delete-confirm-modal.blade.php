<x-modal :title="'Konfirmasi'" :showClose="false">
    <div class="flex flex-col items-center text-center">
        <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-full bg-error-50">
                    <svg class="h-10 w-10 text-error-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </div>

        <p class="mt-4 text-lg font-bold text-gray-900">
            Hapus semua item di keranjang?
        </p>
        <p class="mt-1 text-sm text-gray-500">
            Tindakan ini akan mengosongkan keranjang belanja.
        </p>
    </div>

    <div class="mt-5 grid grid-cols-2 gap-3">
        <button
            x-on:click="open = false"
            type="button"
            class="w-full rounded-xl border border-primary-20 bg-white px-4 py-3 text-sm font-semibold text-primary-60 transition-colors hover:bg-primary-10"
        >
            Batal
        </button>
        <button
            type="button"
            x-on:click="
                $wire.$parent.clearCart()
                open = false
            "
            class="w-full rounded-xl bg-gradient-to-r from-primary-60 to-primary-70 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-primary-60/25 transition-all hover:from-primary-70 hover:to-primary-80 active:scale-[0.98]"
        >
            Hapus
        </button>
    </div>
</x-modal>
