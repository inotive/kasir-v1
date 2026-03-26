@props([
    'preset' => 'custom',
    'from' => null,
    'to' => null,
    'wireFromModel' => null,
    'wireToModel' => null,
    'methodPreset' => 'setRange',
    'methodRange' => 'setTransactionsRange',
    'dataClass' => 'flatpickr-right',
    'placeholder' => 'Pilih tanggal',
    'showPresets' => true,
    'selectClass' => 'shadow-theme-xs h-11 rounded-lg border border-gray-300 bg-white px-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400',
    'inputClass' => 'h-11 w-full rounded-lg border border-gray-200 bg-white py-2.5 pl-[42px] pr-4 text-sm font-medium text-gray-700 shadow-theme-xs focus:outline-hidden focus:ring-0 focus-visible:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400',
])

<div
    wire:ignore
    x-data="{
        preset: @js($preset),
        applyingPreset: false,
        fp: null,
        fromValue: @js($wireFromModel) ? $wire.entangle(@js($wireFromModel)).live : null,
        toValue: @js($wireToModel) ? $wire.entangle(@js($wireToModel)).live : null,
        safeCall(methodName, ...args) {
            try {
                const fn = $wire?.[methodName]
                if (typeof fn !== 'function') {
                    return false
                }
                fn(...args)
                return true
            } catch (e) {
                return false
            }
        },
        setModels(from, to) {
            if (this.fromValue === null || this.toValue === null) {
                return
            }

            this.fromValue = from
            this.toValue = to
        },
        init() {
            if (this.$refs.datepicker?._flatpickr) {
                this.$refs.datepicker._flatpickr.destroy()
            }

            const defaultFrom = @js($from) ?? this.fromValue;
            const defaultTo = @js($to) ?? this.toValue;
            const defaultDates = (defaultFrom && defaultTo)
                ? [new Date(defaultFrom), new Date(defaultTo)]
                : [new Date(), new Date()];

            this.fp = flatpickr(this.$refs.datepicker, {
                mode: 'range',
                monthSelectorType: 'static',
                appendTo: document.body,
                disableMobile: true,
                dateFormat: 'M j',
                defaultDate: defaultDates,
                onReady: (selectedDates, dateStr, instance) => {
                    instance.element.value = dateStr.replace('to', '-');
                    const customClass = instance.element.getAttribute('data-class');
                    if (instance.calendarContainer) {
                        instance.calendarContainer.classList.add(customClass);
                    }
                },
                onChange: (selectedDates, dateStr, instance) => {
                    instance.element.value = dateStr.replace('to', '-');

                    if (this.applyingPreset) {
                        this.applyingPreset = false;
                        return;
                    }

                    if (! this.applyingPreset) {
                        this.preset = 'custom';
                    }

                    this.applyingPreset = false;

                    if (selectedDates.length === 2) {
                        const ok = this.safeCall(
                            @js($methodRange),
                            selectedDates[0].toISOString().slice(0, 10),
                            selectedDates[1].toISOString().slice(0, 10),
                        )
                        if (! ok) {
                            this.setModels(
                                selectedDates[0].toISOString().slice(0, 10),
                                selectedDates[1].toISOString().slice(0, 10),
                            )
                        }
                    }
                },
            })

            if (this.preset !== 'custom') {
                this.applyPreset(false)
            }
        },
        applyPreset(syncServer = true) {
            const today = new Date();
            let from = null;
            let to = null;

            if (this.preset === 'today') {
                from = new Date(today);
                to = new Date(today);
            } else if (this.preset === '7d') {
                from = new Date(today);
                from.setDate(from.getDate() - 6);
                to = new Date(today);
            } else if (this.preset === '30d') {
                from = new Date(today);
                from.setDate(from.getDate() - 29);
                to = new Date(today);
            } else {
                return;
            }

            this.applyingPreset = true;
            this.fp?.setDate([from, to], true);

            if (syncServer) {
                const ok = this.safeCall(@js($methodPreset), this.preset);
                if (! ok) {
                    this.setModels(
                        from.toISOString().slice(0, 10),
                        to.toISOString().slice(0, 10),
                    )
                }
            }
        }
    }"
    {{ $attributes }}
>
    @if ($showPresets)
        <select
            x-model="preset"
            x-on:change="applyPreset()"
            class="{{ $selectClass }}"
        >
            <option value="today">Hari ini</option>
            <option value="7d">7 hari terakhir</option>
            <option value="30d">1 bulan terakhir</option>
            <option value="custom">Custom</option>
        </select>
    @endif

    <div class="relative">
        <input
            x-ref="datepicker"
            class="{{ $inputClass }}"
            placeholder="{{ $placeholder }}"
            data-class="{{ $dataClass }}"
            readonly="readonly"
        />
        <div class="absolute inset-0 right-auto flex items-center pointer-events-none left-4">
            <svg class="fill-gray-700 dark:fill-gray-400" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M6.66683 1.54199C7.08104 1.54199 7.41683 1.87778 7.41683 2.29199V3.00033H12.5835V2.29199C12.5835 1.87778 12.9193 1.54199 13.3335 1.54199C13.7477 1.54199 14.0835 1.87778 14.0835 2.29199V3.00033L15.4168 3.00033C16.5214 3.00033 17.4168 3.89576 17.4168 5.00033V7.50033V15.8337C17.4168 16.9382 16.5214 17.8337 15.4168 17.8337H4.5835C3.47893 17.8337 2.5835 16.9382 2.5835 15.8337V7.50033V5.00033C2.5835 3.89576 3.47893 3.00033 4.5835 3.00033L5.91683 3.00033V2.29199C5.91683 1.87778 6.25262 1.54199 6.66683 1.54199ZM6.66683 4.50033H4.5835C4.30735 4.50033 4.0835 4.72418 4.0835 5.00033V6.75033H15.9168V5.00033C15.9168 4.72418 15.693 4.50033 15.4168 4.50033H13.3335H6.66683ZM15.9168 8.25033H4.0835V15.8337C4.0835 16.1098 4.30735 16.3337 4.5835 16.3337H15.4168C15.693 16.3337 15.9168 16.1098 15.9168 15.8337V8.25033Z" fill="" />
            </svg>
        </div>
    </div>
</div>
