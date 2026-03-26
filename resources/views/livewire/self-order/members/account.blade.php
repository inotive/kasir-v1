<div class="flex min-h-screen flex-col font-poppins bg-gray-50">
    <livewire:self-order.components.page-title-nav :title="'Akun'" :hasBack="true" :hasFilter="false" />

    <div class="container mx-auto px-4 mt-4 space-y-4">
        <div class="rounded-2xl bg-white border border-gray-100 p-4 shadow-sm">
            <div class="text-sm font-bold text-gray-900">{{ (string) session('name') }}</div>
            <div class="text-xs text-gray-500">{{ (string) session('email') }}</div>
            <div class="text-xs text-gray-500 mt-1">{{ (string) session('phone') }}</div>

            <div class="mt-3 flex items-center justify-between rounded-xl bg-gray-50 border border-gray-200 px-3 py-2">
                <div class="text-xs font-semibold text-gray-600">Poin</div>
                <div class="text-sm font-bold text-primary-60">{{ number_format((int) ($points ?? 0), 0, ',', '.') }}</div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-3">
            <a
                wire:navigate
                href="{{ route('self-order.member.profile.edit') }}"
                class="flex items-center justify-between rounded-2xl bg-white border border-gray-100 p-4 shadow-sm hover:bg-gray-50 transition-colors">
                <div>
                    <div class="text-sm font-bold text-gray-900">Edit Profil</div>
                    <div class="text-xs text-gray-500">Ubah data member</div>
                </div>
                <img src="{{ asset('assets/icons/pencil-icon.svg') }}" alt="Edit" class="w-5 h-5" />
            </a>

            <a
                wire:navigate
                href="{{ route('self-order.member.transactions') }}"
                class="flex items-center justify-between rounded-2xl bg-white border border-gray-100 p-4 shadow-sm hover:bg-gray-50 transition-colors">
                <div>
                    <div class="text-sm font-bold text-gray-900">Riwayat Transaksi</div>
                    <div class="text-xs text-gray-500">Lihat transaksi member</div>
                </div>
                <img src="{{ asset('assets/icons/arrow-right-white-icon.svg') }}" alt="Riwayat" class="w-5 h-5" />
            </a>
        </div>

        <button
            type="button"
            wire:click="logoutMember"
            class="w-full rounded-2xl border border-red-100 bg-white px-4 py-3 text-red-600 font-bold hover:bg-red-50 transition-colors">
            Logout / Ganti Akun
        </button>
    </div>
</div>
