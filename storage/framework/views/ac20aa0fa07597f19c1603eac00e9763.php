<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'confirmLabel' => 'Ya, lanjutkan',
    'cancelLabel' => 'Batal',
    'title' => 'Konfirmasi',
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'confirmLabel' => 'Ya, lanjutkan',
    'cancelLabel' => 'Batal',
    'title' => 'Konfirmasi',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div
    x-data="{
        open: false,
        message: '',
        method: null,
        args: [],
        show(payload) {
            this.message = payload?.message ?? ''
            this.method = payload?.method ?? null
            this.args = payload?.args ?? []
            this.open = true
        },
        close() {
            this.open = false
            this.message = ''
            this.method = null
            this.args = []
        },
        confirm() {
            try {
                const fn = this.method ? $wire?.[this.method] : null
                if (typeof fn === 'function') {
                    fn(...(this.args ?? []))
                }
            } catch (e) {}
            this.close()
        },
    }"
    x-on:confirm.window="show($event.detail)"
>
    <template x-if="open">
        <div class="fixed inset-0 z-[100000] flex items-center justify-center p-4" aria-modal="true" role="dialog">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" x-on:click="close()"></div>

    <div class="relative w-full max-w-sm transform overflow-hidden rounded-2xl bg-white shadow-2xl transition-all dark:bg-gray-900 border border-gray-100 dark:border-gray-800">
        
        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-gray-800">
            <h3 class="text-lg font-bold tracking-tight text-gray-900 dark:text-white">
                <?php echo e($title); ?>

            </h3>
            <button type="button" x-on:click="close()" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors dark:hover:bg-gray-800 dark:hover:text-gray-200">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="px-6 py-8">
            <div class="flex flex-col items-center text-center gap-6">
                <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-full bg-error-50 dark:bg-error-500/10">
                    <svg class="h-10 w-10 text-error-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </div>
                
                <div>
                    <p class="text-lg font-bold text-gray-900 dark:text-white" x-text="message">
                    </p>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-center gap-3 bg-gray-50/50 px-6 py-4 dark:bg-gray-800/50">
            <button type="button" x-on:click="close()" 
                class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 transition-all hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-800">
                <?php echo e($cancelLabel); ?>

            </button>
            
            <button type="button" x-on:click="confirm()" 
                class="inline-flex items-center justify-center rounded-xl bg-error-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-all hover:bg-error-700 focus:outline-none focus:ring-2 focus:ring-error-500 focus:ring-offset-2">
                <?php echo e($confirmLabel); ?>

            </button>
        </div>
    </div>
</div>
    </template>
</div>
<?php /**PATH /home/u592471275/domains/ciyemon.com/public_html/resources/views/components/common/confirm-modal.blade.php ENDPATH**/ ?>