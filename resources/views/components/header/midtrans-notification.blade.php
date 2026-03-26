@php
    $user = auth()->user();
    $isCashier = ($user?->can('pos.access') ?? false) && ($user?->can('transactions.view') ?? false);
@endphp

@if ($isCashier)
    <div class="relative" x-data="{
        dropdownOpen: false,
        loading: false,
        items: [],
        count: 0,
        async refresh() {
            this.loading = true;
            try {
                const res = await fetch('{{ route('midtrans.unprocessed', [], false) }}', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!res.ok) return;
                const data = await res.json();
                this.count = Number(data?.count ?? 0) || 0;
                this.items = Array.isArray(data?.items) ? data.items : [];
            } finally {
                this.loading = false;
            }
        },
        init() {
            this.refresh();
            window.addEventListener('midtrans-paid', () => this.refresh());
            window.addEventListener('midtrans-processed', () => this.refresh());
        },
        toggleDropdown() {
            this.dropdownOpen = !this.dropdownOpen;
            if (this.dropdownOpen) this.refresh();
        },
        closeDropdown() {
            this.dropdownOpen = false;
        },
    }" x-init="init()" @click.away="closeDropdown()">
        <button
            class="relative flex items-center justify-center text-gray-500 transition-colors bg-white border border-gray-200 rounded-full hover:text-dark-900 h-11 w-11 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
            @click="toggleDropdown()"
            type="button"
        >
            <template x-if="count > 0">
                <span class="absolute -right-1 -top-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-brand-600 px-1 text-[11px] font-semibold text-white">
                    <span x-text="count"></span>
                </span>
            </template>

            <svg xmlns="http://www.w3.org/2000/svg" width="21" height="21" fill="currentColor" class="bi bi-bell" viewBox="0 0 16 16">
                <path d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2"/>
                <path d="M8 1.918l-.797.161A4 4 0 0 0 4 6c0 .628-.134 2.197-.459 3.742-.16.767-.376 1.566-.663 2.258h10.244c-.287-.692-.502-1.49-.663-2.258C12.134 8.197 12 6.628 12 6a4 4 0 0 0-3.203-3.92L8 1.917zM14.22 12c.223.447.481.801.78 1H1c.299-.199.557-.553.78-1C2.68 10.2 3 6.88 3 6a5 5 0 0 1 4.462-4.959.5.5 0 0 1 .538.389l.09.447.09-.447a.5.5 0 0 1 .538-.389A5 5 0 0 1 13 6c0 .88.32 4.2 1.22 6"/>
            </svg>
        </button>

        <div
            x-show="dropdownOpen"
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="transform opacity-0 scale-95"
            x-transition:enter-end="transform opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="transform opacity-100 scale-100"
            x-transition:leave-end="transform opacity-0 scale-95"
            class="absolute -right-[240px] mt-[17px] flex max-h-[520px] w-[350px] flex-col rounded-2xl border border-gray-200 bg-white p-3 shadow-theme-lg dark:border-gray-800 dark:bg-gray-dark sm:w-[361px] lg:right-0"
            style="display: none;"
        >
            <div class="flex items-center justify-between pb-3 mb-3 border-b border-gray-100 dark:border-gray-800">
                <div>
                    <h5 class="text-lg font-semibold text-gray-800 dark:text-white/90">Midtrans</h5>
                    <p class="text-sm text-gray-500 dark:text-gray-400" x-text="count > 0 ? count + ' transaksi belum diproses' : 'Tidak ada transaksi baru'"></p>
                </div>

                <button @click="closeDropdown()" class="text-gray-500 dark:text-gray-400" type="button">
                    <svg class="fill-current" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M6.21967 7.28131C5.92678 6.98841 5.92678 6.51354 6.21967 6.22065C6.51256 5.92775 6.98744 5.92775 7.28033 6.22065L11.999 10.9393L16.7176 6.22078C17.0105 5.92789 17.4854 5.92788 17.7782 6.22078C18.0711 6.51367 18.0711 6.98855 17.7782 7.28144L13.0597 12L17.7782 16.7186C18.0711 17.0115 18.0711 17.4863 17.7782 17.7792C17.4854 18.0721 17.0105 18.0721 16.7176 17.7792L11.999 13.0607L7.28033 17.7794C6.98744 18.0722 6.51256 18.0722 6.21967 17.7794C5.92678 17.4865 5.92678 17.0116 6.21967 16.7187L10.9384 12L6.21967 7.28131Z" fill="" />
                    </svg>
                </button>
            </div>

            <div class="flex flex-col gap-2 overflow-y-auto custom-scrollbar">
                <template x-if="loading">
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                        Memuat...
                    </div>
                </template>

                <template x-if="!loading && count === 0">
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                        Tidak ada transaksi Midtrans yang menunggu diproses.
                    </div>
                </template>

                <template x-for="it in items" :key="it.id">
                    <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-gray-800 dark:text-white/90" x-text="it.code"></p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-text="it.table ? ('Meja ' + it.table) : '-'"></p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-text="it.paid_at ? it.paid_at : '-'"></p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white/90" x-text="it.total_formatted"></p>
                                <template x-if="it.detail_url">
                                    <a :href="it.detail_url" class="mt-2 inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                                        Detail
                                    </a>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <div class="mt-3 grid grid-cols-2 gap-2">
                <a href="{{ route('pos.index', [], false) }}" class="shadow-theme-xs inline-flex h-10 items-center justify-center rounded-lg border border-gray-300 bg-white px-3 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                    Buka POS
                </a>
                <button type="button" @click="refresh()" class="bg-brand-600 shadow-theme-xs hover:bg-brand-700 inline-flex h-10 items-center justify-center rounded-lg px-3 text-xs font-semibold text-white transition">
                    Refresh
                </button>
            </div>
        </div>
    </div>
@endif
