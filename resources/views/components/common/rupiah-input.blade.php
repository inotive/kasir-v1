@props([
    'wireModel',
    'placeholder' => '',
    'disabled' => false,
    'inputClass' => 'dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90',
])

<div x-data="currencyInput($wire.entangle(@js($wireModel)).live)">
    <input
        type="text"
        inputmode="numeric"
        x-model="displayValue"
        x-on:input="handleInput($event)"
        class="{{ $inputClass }}"
        placeholder="{{ $placeholder }}"
        @disabled($disabled)
    />
</div>
