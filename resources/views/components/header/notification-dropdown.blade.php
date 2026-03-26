<div class="relative" x-data="{
    dropdownOpen: false,
    issues: [],
    reconnectingRoles: {},
    init() {
        const apply = (issues) => {
            this.issues = Array.isArray(issues) ? issues : [];
        };

        apply(window.PrinterManager?._issues ?? []);

        window.addEventListener('printer-issues-updated', (event) => {
            apply(event.detail?.issues ?? []);
        });

        window.addEventListener('printer-job-failed', (event) => {
            const role = event.detail?.printerRole;
            const message = event.detail?.message;
            if (!role) return;

            const next = (Array.isArray(this.issues) ? this.issues : []).filter((i) => i?.role !== role);
            next.push({
                role,
                sourceName: role,
                type: 'print_failed',
                message: message || 'Gagal mencetak.'
            });
            this.issues = next;
        });
    },
    get badgeCount() {
        return Array.isArray(this.issues) ? this.issues.length : 0;
    },
    toggleDropdown() {
        this.dropdownOpen = !this.dropdownOpen;
    },
    closeDropdown() {
        this.dropdownOpen = false;
    },
    statusLabel(issue) {
        const type = String(issue?.type || '');
        if (type === 'missing_config') return 'Belum Setup';
        if (type === 'permission_lost') return 'Izin Hilang';
        if (type === 'connection_failed') return 'Offline';
        if (type === 'disconnected') return 'Terputus';
        if (type === 'print_failed') return 'Gagal Cetak';
        return 'Perlu Aksi';
    },
    statusClass(issue) {
        const type = String(issue?.type || '');
        if (type === 'missing_config' || type === 'permission_lost') return 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400';
        return 'bg-error-50 text-error-700 dark:bg-error-500/15 dark:text-error-400';
    },
    canReconnect(issue) {
        const type = String(issue?.type || '');
        return type === 'connection_failed' || type === 'disconnected' || type === 'print_failed';
    },
    isReconnecting(role) {
        return !!this.reconnectingRoles?.[role];
    },
    async reconnect(role) {
        if (!role || this.isReconnecting(role)) return;
        this.reconnectingRoles = { ...(this.reconnectingRoles || {}), [role]: true };
        try {
            await window.PrinterManager?.reconnectRole(role);
        } finally {
            const next = { ...(this.reconnectingRoles || {}) };
            delete next[role];
            this.reconnectingRoles = next;
        }
    },
}" x-init="init()" @click.away="closeDropdown()">
    <button
        class="relative flex items-center justify-center text-gray-500 transition-colors bg-white border border-gray-200 rounded-full hover:text-dark-900 h-11 w-11 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
        @click="toggleDropdown()"
        type="button"
    >
        <template x-if="badgeCount > 0">
            <span class="absolute -right-1 -top-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-error-600 px-1 text-[11px] font-semibold text-white">
                <span x-text="badgeCount"></span>
            </span>
        </template>

        <svg xmlns="http://www.w3.org/2000/svg" width="21" height="21" fill="currentColor" class="bi bi-printer" viewBox="0 0 16 16">
        <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1"/>
        <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1"/>
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
                <h5 class="text-lg font-semibold text-gray-800 dark:text-white/90">Status Printer</h5>
                <p class="text-sm text-gray-500 dark:text-gray-400" x-text="badgeCount > 0 ? badgeCount + ' masalah terdeteksi' : 'Semua printer OK'"></p>
            </div>

            <button @click="closeDropdown()" class="text-gray-500 dark:text-gray-400" type="button">
                <svg class="fill-current" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M6.21967 7.28131C5.92678 6.98841 5.92678 6.51354 6.21967 6.22065C6.51256 5.92775 6.98744 5.92775 7.28033 6.22065L11.999 10.9393L16.7176 6.22078C17.0105 5.92789 17.4854 5.92788 17.7782 6.22078C18.0711 6.51367 18.0711 6.98855 17.7782 7.28144L13.0597 12L17.7782 16.7186C18.0711 17.0115 18.0711 17.4863 17.7782 17.7792C17.4854 18.0721 17.0105 18.0721 16.7176 17.7792L11.999 13.0607L7.28033 17.7794C6.98744 18.0722 6.51256 18.0722 6.21967 17.7794C5.92678 17.4865 5.92678 17.0116 6.21967 16.7187L10.9384 12L6.21967 7.28131Z" fill="" />
                </svg>
            </button>
        </div>

        <div class="flex flex-col gap-2 overflow-y-auto custom-scrollbar">
            <template x-if="badgeCount === 0">
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                    Tidak ada masalah printer saat ini.
                </div>
            </template>

            <template x-for="issue in issues" :key="issue.role">
                <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-gray-800 dark:text-white/90" x-text="issue.sourceName || issue.role"></p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-text="issue.message || '-'"></p>
                            <p class="mt-1 text-[11px] text-gray-400" x-text="issue.role"></p>
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="statusClass(issue)" x-text="statusLabel(issue)"></span>
                    </div>

                    <div class="mt-3 flex items-center justify-end gap-2">
                        <a href="{{ route('settings.index', ['section' => 'printers'], false) }}" wire:navigate @click="closeDropdown()" class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                            Buka Pengaturan
                        </a>
                        <button type="button" x-show="canReconnect(issue)" :disabled="isReconnecting(issue.role)" @click="reconnect(issue.role)" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold text-white transition disabled:opacity-60">
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
</div>
