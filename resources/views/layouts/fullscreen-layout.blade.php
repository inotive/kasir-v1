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

    <title>{{ $title ?? 'Dashboard' }} | {{ config('app.name') }}</title>
    <link rel="manifest" href="{{ route('admin.manifest') }}">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles

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

                    document.addEventListener('livewire:navigated', () => {
                        this.updateTheme();
                    });
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

<body x-data="{ 'loaded': true}" x-init="$store.sidebar.isExpanded = window.innerWidth >= 1280;
// Use matchMedia instead of resize: mobile browsers fire resize when the address bar
// shows/hides during touch scroll, which would close the mobile sidebar.
window.matchMedia('(min-width: 1280px)').addEventListener('change', (e) => {
    const root = document.documentElement;
    root.classList.add('sidebar-resizing');
    $store.sidebar.isMobileOpen = false;
    $store.sidebar.isExpanded = e.matches;
    clearTimeout(window.__sidebarResizeTimer);
    window.__sidebarResizeTimer = setTimeout(() => root.classList.remove('sidebar-resizing'), 400);
});">

    {{-- preloader --}}
    <x-common.preloader/>
    {{-- preloader end --}}

    {{ $slot }}

    @livewireScripts
    @stack('scripts')
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('{{ route('admin.service-worker') }}');
            });
        }
    </script>
</body>

</html>
