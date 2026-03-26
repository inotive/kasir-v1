<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'stats' => [
        'totalTransactions' => 0,
        'totalRevenue' => 0,
        'avgRevenue' => 0,
        'totalItemsSold' => 0,
    ],
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
    'stats' => [
        'totalTransactions' => 0,
        'totalRevenue' => 0,
        'avgRevenue' => 0,
        'totalItemsSold' => 0,
    ],
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $totalTransactions = (int) ($stats['totalTransactions'] ?? 0);
    $totalRevenue = (int) ($stats['totalRevenue'] ?? 0);
    $avgRevenue = (int) ($stats['avgRevenue'] ?? 0);
    $totalItemsSold = (int) ($stats['totalItemsSold'] ?? 0);
?>

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4 md:gap-6">
    <div class="rounded-2xl border border-gray-200 bg-white p-5 md:p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <p class="text-theme-sm text-gray-500 dark:text-gray-400">Total Transaksi</p>
        <div class="mt-3 flex items-end justify-between">
            <h4 class="text-2xl font-bold text-gray-800 dark:text-white/90"><?php echo e(number_format($totalTransactions, 0, ',', '.')); ?></h4>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-5 md:p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <p class="text-theme-sm text-gray-500 dark:text-gray-400">Omzet (Net Sales)</p>
        <div class="mt-3 flex items-end justify-between">
            <h4 class="text-2xl font-bold text-gray-800 dark:text-white/90">Rp<?php echo e(number_format($totalRevenue, 0, ',', '.')); ?></h4>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-5 md:p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <p class="text-theme-sm text-gray-500 dark:text-gray-400">Rata-rata Omzet</p>
        <div class="mt-3 flex items-end justify-between">
            <h4 class="text-2xl font-bold text-gray-800 dark:text-white/90">Rp<?php echo e(number_format($avgRevenue, 0, ',', '.')); ?></h4>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-5 md:p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <p class="text-theme-sm text-gray-500 dark:text-gray-400">Item Terjual</p>
        <div class="mt-3 flex items-end justify-between">
            <h4 class="text-2xl font-bold text-gray-800 dark:text-white/90"><?php echo e(number_format($totalItemsSold, 0, ',', '.')); ?></h4>
        </div>
    </div>
</div>
<?php /**PATH /Users/bagusws/Documents/GitHub/kasir-v1/resources/views/components/transaction/history-metrics.blade.php ENDPATH**/ ?>