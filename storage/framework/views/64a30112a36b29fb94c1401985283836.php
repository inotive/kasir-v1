<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'wireModel',
    'defaultToday' => true,
    'clearable' => false,
    'disabled' => false,
    'dataClass' => 'flatpickr-right',
    'inputClass' => 'shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-white px-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400',
    'clearButtonClass' => 'absolute top-1/2 right-2 -translate-y-1/2 rounded-md border border-gray-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]',
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
    'wireModel',
    'defaultToday' => true,
    'clearable' => false,
    'disabled' => false,
    'dataClass' => 'flatpickr-right',
    'inputClass' => 'shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-white px-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400',
    'clearButtonClass' => 'absolute top-1/2 right-2 -translate-y-1/2 rounded-md border border-gray-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div
    wire:ignore
    x-data="{
        value: $wire.entangle(<?php echo \Illuminate\Support\Js::from($wireModel)->toHtml() ?>).live,
        fp: null,
        init() {
            if (this.$refs.input?._flatpickr) {
                this.$refs.input._flatpickr.destroy()
            }

            const defaultDate = this.value
                ? new Date(this.value)
                : (<?php echo \Illuminate\Support\Js::from((bool) $defaultToday)->toHtml() ?> ? new Date() : null);

            this.fp = flatpickr(this.$refs.input, {
                monthSelectorType: 'static',
                appendTo: document.body,
                disableMobile: true,
                dateFormat: 'Y-m-d',
                defaultDate,
                onChange: (selectedDates) => {
                    if (selectedDates?.length) {
                        const d = selectedDates[0];
                        const year = d.getFullYear();
                        const month = String(d.getMonth() + 1).padStart(2, '0');
                        const day = String(d.getDate()).padStart(2, '0');
                        this.value = `${year}-${month}-${day}`;
                    } else {
                        this.value = null;
                    }
                },
                onReady: (selectedDates, dateStr, instance) => {
                    const customClass = instance.element.getAttribute('data-class');
                    if (instance.calendarContainer) {
                        instance.calendarContainer.classList.add(customClass);
                    }
                },
            })

            this.$watch('value', (next) => {
                if (! this.fp) {
                    return
                }

                const current = this.fp.selectedDates?.[0];
                let currentValue = null;
                if (current) {
                    const year = current.getFullYear();
                    const month = String(current.getMonth() + 1).padStart(2, '0');
                    const day = String(current.getDate()).padStart(2, '0');
                    currentValue = `${year}-${month}-${day}`;
                }
                if (currentValue !== next) {
                    this.fp.setDate(next, false)
                }
            })
        },
        clear() {
            this.value = null
            this.fp?.clear()
        }
    }"
    <?php echo e($attributes); ?>

>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($clearable): ?>
        <div class="relative">
            <input
                x-ref="input"
                type="text"
                class="<?php echo e($inputClass); ?>"
                data-class="<?php echo e($dataClass); ?>"
                readonly="readonly"
                <?php if($disabled): echo 'disabled'; endif; ?>
            />
            <button type="button" x-on:click="clear()" class="<?php echo e($clearButtonClass); ?>" <?php if($disabled): echo 'disabled'; endif; ?>>
                Hapus
            </button>
        </div>
    <?php else: ?>
        <input
            x-ref="input"
            type="text"
            class="<?php echo e($inputClass); ?>"
            data-class="<?php echo e($dataClass); ?>"
            readonly="readonly"
            <?php if($disabled): echo 'disabled'; endif; ?>
        />
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>
<?php /**PATH /Users/bagusws/Documents/GitHub/kasir-v1/resources/views/components/common/date-picker.blade.php ENDPATH**/ ?>