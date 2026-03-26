@props([
    'wireModel',
    'defaultToday' => true,
    'clearable' => false,
    'disabled' => false,
    'dataClass' => 'flatpickr-right',
    'inputClass' => 'shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-white px-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400',
    'clearButtonClass' => 'absolute top-1/2 right-2 -translate-y-1/2 rounded-md border border-gray-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]',
])

<div
    wire:ignore
    x-data="{
        value: $wire.entangle(@js($wireModel)).live,
        fp: null,
        init() {
            if (this.$refs.input?._flatpickr) {
                this.$refs.input._flatpickr.destroy()
            }

            const defaultDate = this.value
                ? new Date(this.value)
                : (@js((bool) $defaultToday) ? new Date() : null);

            this.fp = flatpickr(this.$refs.input, {
                monthSelectorType: 'static',
                appendTo: document.body,
                disableMobile: true,
                dateFormat: 'Y-m-d',
                defaultDate,
                onChange: (selectedDates) => {
                    this.value = selectedDates?.length
                        ? selectedDates[0].toISOString().slice(0, 10)
                        : null
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

                const current = this.fp.selectedDates?.[0]
                const currentValue = current ? current.toISOString().slice(0, 10) : null
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
    {{ $attributes }}
>
    @if ($clearable)
        <div class="relative">
            <input
                x-ref="input"
                type="text"
                class="{{ $inputClass }}"
                data-class="{{ $dataClass }}"
                readonly="readonly"
                @disabled($disabled)
            />
            <button type="button" x-on:click="clear()" class="{{ $clearButtonClass }}" @disabled($disabled)>
                Hapus
            </button>
        </div>
    @else
        <input
            x-ref="input"
            type="text"
            class="{{ $inputClass }}"
            data-class="{{ $dataClass }}"
            readonly="readonly"
            @disabled($disabled)
        />
    @endif
</div>
