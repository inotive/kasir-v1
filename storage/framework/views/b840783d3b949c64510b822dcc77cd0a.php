<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['transactions' => []]));

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

foreach (array_filter((['transactions' => []]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $rows = $transactions ?? [];

    $getStatusClasses = function (string $status) {
        $base = 'rounded-full px-2 py-0.5 text-theme-xs font-medium';

        return match ($status) {
            'paid' => $base.' bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500',
            'pending' => $base.' bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-orange-400',
            default => $base.' bg-gray-50 text-gray-600 dark:bg-gray-500/15 dark:text-gray-400',
        };
    };
?>

<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white px-4 pb-3 pt-4 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Transaksi Terakhir</h3>
    </div>

    <div class="max-w-full overflow-x-auto custom-scrollbar">
        <table class="min-w-full">
            <thead>
                <tr class="border-t border-gray-100 dark:border-gray-800">
                    <th class="py-3 text-left">
                        <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Kode</p>
                    </th>
                    <th class="py-3 text-left">
                        <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Customer</p>
                    </th>
                    <th class="py-3 text-right">
                        <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Total</p>
                    </th>
                    <th class="py-3 text-center">
                        <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Status</p>
                    </th>
                    <th class="py-3 text-left">
                        <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Waktu</p>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                    <?php
                        $statusKey = strtolower((string) ($row['payment_status'] ?? ''));
                        $statusLabel = \App\Helpers\DataLabelHelper::enum($statusKey !== '' ? $statusKey : null, 'payment_status');
                    ?>
                    <tr class="border-t border-gray-100 dark:border-gray-800">
                        <td class="py-3 whitespace-nowrap">
                            <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90"><?php echo e($row['code'] ?? '-'); ?></p>
                        </td>
                        <td class="py-3 whitespace-nowrap">
                            <p class="text-gray-700 text-theme-sm dark:text-gray-300"><?php echo e($row['customer'] ?? '-'); ?></p>
                            <p class="text-gray-500 text-theme-xs dark:text-gray-400"><?php echo e($row['phone'] ?? '-'); ?></p>
                        </td>
                        <td class="py-3 text-right whitespace-nowrap">
                            <p class="text-gray-500 text-theme-sm dark:text-gray-400"><?php echo e($row['total'] ?? '-'); ?></p>
                        </td>
                        <td class="py-3 text-center whitespace-nowrap">
                            <span class="<?php echo e($getStatusClasses($statusKey)); ?>"><?php echo e($statusLabel); ?></span>
                        </td>
                        <td class="py-3 whitespace-nowrap">
                            <p class="text-gray-500 text-theme-sm dark:text-gray-400"><?php echo e($row['created_at'] ?? '-'); ?></p>
                        </td>
                    </tr>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    <tr class="border-t border-gray-100 dark:border-gray-800">
                        <td colspan="6" class="py-6">
                            <p class="text-center text-theme-sm text-gray-500 dark:text-gray-400">Belum ada transaksi.</p>
                        </td>
                    </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php /**PATH /Users/bagusws/Documents/GitHub/kasir-v1/resources/views/components/ecommerce/latest-transactions.blade.php ENDPATH**/ ?>