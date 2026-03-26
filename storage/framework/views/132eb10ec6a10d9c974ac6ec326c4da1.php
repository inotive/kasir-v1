<div
    x-data="toastCenter()"
    x-init="init()"
    class="pointer-events-none fixed right-4 z-[100000] flex w-[min(420px,calc(100vw-2rem))] flex-col gap-2"
    :style="`top: ${topOffset}px`"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            class="pointer-events-auto overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-lg dark:border-gray-800 dark:bg-gray-900"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-2 scale-[0.98]"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-2 scale-[0.98]"
        >
            <div class="flex items-start gap-3 px-4 py-3">
                <div
                    class="mt-0.5 inline-flex h-9 w-9 flex-none items-center justify-center rounded-xl"
                    :class="iconBg(toast.type)"
                >
                    <div class="h-5 w-5" x-html="icon(toast.type)"></div>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-gray-800 dark:text-white/90" x-text="toast.title"></p>
                    <p class="mt-0.5 text-sm text-gray-600 dark:text-gray-300" x-text="toast.message"></p>
                </div>
                <button
                    type="button"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                    @click="remove(toast.id)"
                    aria-label="Tutup"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none">
                        <path d="M6 6L18 18M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </button>
            </div>
            <div class="h-1 w-full bg-gray-100 dark:bg-gray-800">
                <div
                    class="h-1"
                    :class="barBg(toast.type)"
                    :style="`width: 100%; animation: toastProgress ${toast.timeout}ms linear forwards;`"
                ></div>
            </div>
        </div>
    </template>
</div>

<style>
@keyframes toastProgress { from { width: 100%; } to { width: 0%; } }
</style>

<script>
    window.toastCenter = window.toastCenter || function () {
        return {
            toasts: [],
            topOffset: 16,
            init() {
                window.__toastRecent = window.__toastRecent || {};

                const setTop = () => {
                    const header = document.querySelector('header');
                    const safeTop = (typeof window !== 'undefined' && window.visualViewport && Number.isFinite(window.visualViewport.offsetTop))
                        ? window.visualViewport.offsetTop
                        : 0;
                    const headerH = header ? header.getBoundingClientRect().height : 0;
                    const gap = 16;
                    this.topOffset = Math.max(16, Math.round(safeTop + headerH + gap));
                };

                setTop();
                if (window.ResizeObserver) {
                    const header = document.querySelector('header');
                    if (header) new ResizeObserver(() => setTop()).observe(header);
                }
                window.addEventListener('resize', setTop);

                const handler = (data) => {
                    const payload = data?.detail ?? (Array.isArray(data) ? data[0] : data) ?? {};
                    if (typeof payload === 'string') {
                        this.push({ message: payload });
                        return;
                    }
                    this.push(payload);
                };

                window.addEventListener('toast', handler);
                if (window.Livewire?.on) {
                    window.Livewire.on('toast', handler);
                }
            },
            push(input) {
                const type = String(input?.type || 'info');
                const message = String(input?.message || input?.text || '');
                const title = String(input?.title || this.defaultTitle(type));
                const timeout = Number(input?.timeout ?? 3000);

                if (!message) return;

                const sig = `${type}|${title}|${message}`;
                const now = Date.now();
                const recent = window.__toastRecent || {};
                if (recent[sig] && now - recent[sig] < 400) {
                    return;
                }
                recent[sig] = now;
                window.__toastRecent = recent;

                const id = `${Date.now()}_${Math.random().toString(16).slice(2)}`;
                const toast = { id, type, title, message, timeout: Number.isFinite(timeout) ? timeout : 3000 };

                this.toasts = [toast, ...(Array.isArray(this.toasts) ? this.toasts : [])].slice(0, 5);

                setTimeout(() => this.remove(id), toast.timeout);
            },
            remove(id) {
                this.toasts = (Array.isArray(this.toasts) ? this.toasts : []).filter((t) => t.id !== id);
            },
            defaultTitle(type) {
                if (type === 'success') return 'Berhasil';
                if (type === 'error') return 'Gagal';
                if (type === 'warning') return 'Perhatian';
                return 'Informasi';
            },
            iconBg(type) {
                if (type === 'success') return 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-400';
                if (type === 'error') return 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-400';
                if (type === 'warning') return 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400';
                return 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-300';
            },
            barBg(type) {
                if (type === 'success') return 'bg-success-500';
                if (type === 'error') return 'bg-error-500';
                if (type === 'warning') return 'bg-warning-500';
                return 'bg-brand-500';
            },
            icon(type) {
                if (type === 'success') {
                    return '<svg viewBox="0 0 24 24" fill="none" class="h-5 w-5"><path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                }
                if (type === 'error') {
                    return '<svg viewBox="0 0 24 24" fill="none" class="h-5 w-5"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                }
                if (type === 'warning') {
                    return '<svg viewBox="0 0 24 24" fill="none" class="h-5 w-5"><path d="M12 9v4m0 4h.01M10.29 3.86l-8.2 14.2A2 2 0 0 0 3.8 21h16.4a2 2 0 0 0 1.73-2.94l-8.2-14.2a2 2 0 0 0-3.46 0Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                }
                return '<svg viewBox="0 0 24 24" fill="none" class="h-5 w-5"><path d="M12 16v-4m0-4h.01M12 22C6.477 22 2 17.523 2 12S6.477 2 12 2s10 4.477 10 10-4.477 10-10 10Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
            }
        };
    };

    window.toast = window.toast || function (payload) {
        window.dispatchEvent(new CustomEvent('toast', { detail: payload }));
    };
</script>
<?php /**PATH /home/u592471275/domains/ciyemon.com/public_html/resources/views/components/common/toast-center.blade.php ENDPATH**/ ?>