<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'wireModel',
    'placeholder' => '',
    'disabled' => false,
    'inputClass' => 'dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90',
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
    'placeholder' => '',
    'disabled' => false,
    'inputClass' => 'dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div x-data="currencyInput($wire.entangle(<?php echo \Illuminate\Support\Js::from($wireModel)->toHtml() ?>).live)">
    <input
        type="text"
        inputmode="numeric"
        x-model="displayValue"
        x-on:input="handleInput($event)"
        class="<?php echo e($inputClass); ?>"
        placeholder="<?php echo e($placeholder); ?>"
        <?php if($disabled): echo 'disabled'; endif; ?>
    />
</div>
<?php /**PATH /home/u592471275/domains/ciyemon.com/public_html/resources/views/components/common/rupiah-input.blade.php ENDPATH**/ ?>