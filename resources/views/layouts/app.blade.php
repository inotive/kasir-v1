<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script>
        window.APP_AUTHENTICATED = @json(auth()->check());
        window.APP_CAN_CASHIER_ORDERS = @json(auth()->user()?->can('pos.access') ?? false);
    </script>

    <title>{{ $title ?? 'Dashboard' }} | Sellera - Seleranya UMKM</title>
    <link rel="icon" href="{{ asset('images/logo/pngtree-pools-icon-logo-design-activity-beach-summer-vector-png-image_12898075.png') }}" type="image/png">
    <link rel="manifest" href="{{ route('admin.manifest') }}">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <script>
        window.PRINTER_DEBUG = false;
    </script>

    <script defer src="{{ asset('js/notification-system/notification-manager.js') }}"></script>

    <script defer src="{{ asset('js/printer-system/bluetooth-service.js') }}"></script>
    <script defer src="{{ asset('js/printer-system/receipt-templates.js') }}"></script>
    <script defer src="{{ asset('js/printer-system/print-queue.js') }}"></script>
    <script defer src="{{ asset('js/printer-system/printer-manager.js') }}"></script>
    <script defer src="{{ asset('js/printer-system/printer-ui.js') }}"></script>

    <!-- Alpine.js -->
    {{-- <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script> --}}

    <!-- Theme Store -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('theme', {
                init() {
                    const savedTheme = localStorage.getItem('theme');
                    const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' :
                        'light';
                    this.theme = savedTheme || systemTheme;
                    this.updateTheme();
                },
                theme: 'light',
                toggle() {
                    this.theme = this.theme === 'light' ? 'dark' : 'light';
                    localStorage.setItem('theme', this.theme);
                    this.updateTheme();
                },
                updateTheme() {
                    const html = document.documentElement;
                    const body = document.body;
                    if (this.theme === 'dark') {
                        html.classList.add('dark');
                        body.classList.add('dark', 'bg-gray-900');
                    } else {
                        html.classList.remove('dark');
                        body.classList.remove('dark', 'bg-gray-900');
                    }
                }
            });

            Alpine.store('sidebar', {
                // Initialize based on screen size
                isExpanded: window.innerWidth >= 1280, // true for desktop, false for mobile
                isMobileOpen: false,
                isHovered: false,

                toggleExpanded() {
                    this.isExpanded = !this.isExpanded;
                    // When toggling desktop sidebar, ensure mobile menu is closed
                    this.isMobileOpen = false;
                },

                toggleMobileOpen() {
                    this.isMobileOpen = !this.isMobileOpen;
                    // Don't modify isExpanded when toggling mobile menu
                },

                setMobileOpen(val) {
                    this.isMobileOpen = val;
                },

                setHovered(val) {
                    // Only allow hover effects on desktop when sidebar is collapsed
                    if (window.innerWidth >= 1280 && !this.isExpanded) {
                        this.isHovered = val;
                    }
                }
            });
        });
    </script>

    <!-- Apply dark mode immediately to prevent flash -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme');
            const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            const theme = savedTheme || systemTheme;
            const apply = () => {
                const body = document.body;
                if (!body) {
                    return false;
                }

                if (theme === 'dark') {
                    document.documentElement.classList.add('dark');
                    body.classList.add('dark', 'bg-gray-900');
                } else {
                    document.documentElement.classList.remove('dark');
                    body.classList.remove('dark', 'bg-gray-900');
                }

                return true;
            };

            if (!apply()) {
                if (theme === 'dark') {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }

                document.addEventListener('DOMContentLoaded', apply);
            }
        })();
    </script>
    
</head>

<body
    x-data="{ 'loaded': true}"
    x-init="$store.sidebar.isExpanded = window.innerWidth >= 1280;
    const checkMobile = () => {
        if (window.innerWidth < 1280) {
            $store.sidebar.setMobileOpen(false);
            $store.sidebar.isExpanded = false;
        } else {
            $store.sidebar.isMobileOpen = false;
            $store.sidebar.isExpanded = true;
        }
    };
    window.addEventListener('resize', checkMobile);">

    {{-- preloader --}}
    <x-common.preloader />
    {{-- preloader end --}}

    <x-common.loading-bar />

    <div class="min-h-screen xl:flex">
        @include('layouts.backdrop')
        @include('layouts.sidebar')

        <div class="flex-1 transition-all duration-300 ease-in-out"
            :class="{
                'xl:ml-[290px]': $store.sidebar.isExpanded || $store.sidebar.isHovered,
                'xl:ml-[90px]': !$store.sidebar.isExpanded && !$store.sidebar.isHovered,
                'ml-0': $store.sidebar.isMobileOpen
            }">
            <!-- app header start -->
            @include('layouts.app-header')
            <!-- app header end -->
            <div class="p-4 mx-auto max-w-(--breakpoint-2xl) md:p-6">
                <!-- @yield('content') -->
                {{ $slot }}
            </div>
        </div>

    </div>

    <div x-data="posPrintModal" x-init="init()" x-show="open" class="fixed inset-0 z-[100000]" style="display: none;" aria-modal="true" role="dialog">
        <template x-if="open">
            <div class="absolute inset-0">
                <div class="absolute inset-0 bg-black/50" @click="close()"></div>
                <div class="absolute inset-0 flex items-center justify-center p-4">
                    <div class="relative flex max-h-[85vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl dark:border-gray-800 dark:bg-gray-900">
                        <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                            <div>
                                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Cetak Struk</h3>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-text="payload?.order?.code ? 'Kode: ' + payload.order.code : ''"></p>
                            </div>
                            <button type="button" @click="close()" class="text-sm font-medium text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
                                Tutup
                            </button>
                        </div>

                        <div class="min-h-0 flex-1 space-y-4 overflow-y-auto p-5">
                            <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-950">
                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                    <div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Pelanggan</p>
                                        <p class="text-sm font-semibold text-gray-800 dark:text-white/90" x-text="payload?.customer_name || '-'"></p>
                                    </div>
                                    <div class="sm:text-right">
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Total</p>
                                        <p class="text-sm font-semibold text-gray-800 dark:text-white/90" x-text="payload?.order?.total ? ('Rp ' + Number(payload.order.total).toLocaleString('id-ID')) : '-'"></p>
                                    </div>
                                </div>
                                <template x-if="payload?.order?.points_earned > 0">
                                    <div class="mt-2 flex items-center gap-2 rounded-lg bg-success-50 px-3 py-2 text-xs font-medium text-success-700 dark:bg-success-500/15 dark:text-success-300">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"></path></svg>
                                        <span x-text="'Mendapatkan ' + payload.order.points_earned + ' Poin'"></span>
                                    </div>
                                </template>
                                <div class="mt-3 flex flex-wrap items-center gap-2">
                                    <span class="rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700 dark:bg-brand-500/15 dark:text-brand-300" x-text="context === 'pending' ? 'Pending tersimpan' : (context === 'midtrans' ? 'Online dibayar' : 'Checkout berhasil')"></span>
                                    <span class="rounded-full bg-gray-200 px-3 py-1 text-xs font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-300" x-text="payload?.order?.order_type === 'dine_in' ? ('Dine-in' + (payload?.table_number ? (' • Meja ' + payload.table_number) : '')) : 'Take Away'"></span>
                                </div>
                            </div>

                            <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-800 dark:text-white/90">Pilih Printer</p>
                                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Kasir & checker otomatis dipilih. Printer dapur tanpa item disembunyikan.</p>
                                    </div>
                                    <a href="{{ route('settings.index', ['section' => 'printers'], false) }}" wire:navigate @click="close()" class="text-xs font-semibold text-brand-600 hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300">Pengaturan</a>
                                </div>

                                <div class="mt-3 flex items-center justify-between gap-3">
                                    <label class="inline-flex items-center gap-2 text-xs font-semibold text-gray-600 dark:text-gray-400">
                                        <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900" x-model="showAllPrinters" />
                                        Tampilkan semua printer
                                    </label>
                                    <p class="text-xs text-gray-500 dark:text-gray-400" x-text="selectedSourceIds().length + ' dipilih'"></p>
                                </div>

                                <div class="mt-3 space-y-2">
                                    <template x-for="source in visibleSources()" :key="source.id">
                                        <div class="flex items-start justify-between gap-3 rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-950">
                                            <div class="flex min-w-0 items-start gap-3">
                                                <input type="checkbox" class="mt-1 h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900" :checked="!!selected[roleFromSourceId((typeof source === 'undefined' ? null : source)?.id)]" @change="toggle(roleFromSourceId((typeof source === 'undefined' ? null : source)?.id))" />
                                                <div class="min-w-0">
                                                    <p class="truncate text-sm font-semibold text-gray-800 dark:text-white/90" x-text="(typeof source === 'undefined' ? '' : source.name)"></p>
                                                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                                        <span x-text="String((typeof source === 'undefined' ? null : source)?.type || '').toUpperCase()"></span>
                                                        <span> • </span>
                                                        <span x-text="itemsForSource(typeof source === 'undefined' ? null : source).length + ' item'"></span>
                                                    </p>
                                                    <p class="mt-0.5 text-[11px] text-gray-400" x-text="roleFromSourceId((typeof source === 'undefined' ? null : source)?.id)"></p>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span class="whitespace-nowrap rounded-full px-3 py-1 text-xs font-semibold" :class="statusClass(printerStatus(typeof source === 'undefined' ? null : source)?.key)" x-text="printerStatus(typeof source === 'undefined' ? null : source)?.label"></span>
                                                <button type="button" x-show="(printerStatus(typeof source === 'undefined' ? null : source)?.key || '') === 'offline'" :disabled="isReconnecting(roleFromSourceId((typeof source === 'undefined' ? null : source)?.id))" @click="reconnect(roleFromSourceId((typeof source === 'undefined' ? null : source)?.id))" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold text-white transition disabled:opacity-60">
                                                    <svg x-show="isReconnecting(roleFromSourceId((typeof source === 'undefined' ? null : source)?.id))" class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                                    </svg>
                                                    <span x-text="isReconnecting(roleFromSourceId((typeof source === 'undefined' ? null : source)?.id)) ? 'Menyambungkan' : 'Coba Sambung'"></span>
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <template x-if="Array.isArray(issues) && issues.length > 0">
                                <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
                                    <p class="text-sm font-semibold text-gray-800 dark:text-white/90">Perlu Diperbaiki</p>
                                    <div class="mt-3 space-y-2">
                                        <template x-for="issue in issues" :key="issue.key">
                                            <div class="flex items-start justify-between gap-3 rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-950">
                                                <div class="min-w-0">
                                                    <p class="truncate text-sm font-semibold text-gray-800 dark:text-white/90" x-text="issue.label || 'Printer'"></p>
                                                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400" x-text="issue.message"></p>
                                                    <template x-if="issue.type === 'unassigned_items'">
                                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-text="unassignedNames().slice(0, 6).join(', ') + (unassignedNames().length > 6 ? '…' : '')"></p>
                                                    </template>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <span class="whitespace-nowrap rounded-full px-3 py-1 text-xs font-semibold" :class="badgeClass(issue)" x-text="badgeLabel(issue)"></span>
                                                    <button type="button" x-show="issue.role && (issue.type === 'offline')" :disabled="isReconnecting(issue.role)" @click="reconnect(issue.role)" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold text-white transition disabled:opacity-60">
                                                        <svg x-show="isReconnecting(issue.role)" class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                                        </svg>
                                                        <span x-text="isReconnecting(issue.role) ? 'Menyambungkan' : 'Coba Sambung'"></span>
                                                    </button>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div class="border-t border-gray-200 p-5 dark:border-gray-800">
                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                <button type="button" :aria-disabled="!canPrintSelected()" @click="printSelected()" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex h-11 items-center justify-center rounded-lg px-4 text-sm font-semibold text-white transition" :class="!canPrintSelected() ? 'opacity-50' : ''">
                                    Cetak Sesuai Pilihan
                                </button>
                                <button type="button" :aria-disabled="!canPrintKasirOnly()" @click="printKasirOnly()" class="shadow-theme-xs inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]" :class="!canPrintKasirOnly() ? 'opacity-50' : ''">
                                    Cetak Kasir Saja
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    @persist('toast-center')
        <x-common.toast-center />
    @endpersist

    <style>
        .flatpickr-calendar { z-index: 1000000 !important; }
    </style>

    @livewireScripts
    @stack('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sources = @json($printerSourcesForJs ?? []);
            window.PRINTER_SOURCES = Array.isArray(sources) ? sources : [];

            if (window.PrinterManager?.configureSources) {
                window.PrinterManager.configureSources(window.PRINTER_SOURCES);
            }

            window.dispatchEvent(new CustomEvent('printer-sources-updated', { detail: { sources: window.PRINTER_SOURCES } }));
        });

    </script>
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('{{ route('admin.service-worker') }}');
            });
        }
    </script>
    <script></script>
</body>

</html>
