<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Performa Voucher</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Persentase transaksi yang memakai voucher dan dampaknya pada rata-rata nilai transaksi.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?php echo e(route('vouchers.index')); ?>" wire:navigate class="shadow-theme-xs inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                Kembali
            </a>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-6">
            <?php if (isset($component)) { $__componentOriginal0f75a9e682f4dfdf6a00b8cfac5a7028 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0f75a9e682f4dfdf6a00b8cfac5a7028 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.common.date-range-picker','data' => ['preset' => $rangePreset,'from' => $from,'to' => $to,'wireFromModel' => 'from','wireToModel' => 'to','class' => 'flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center lg:col-span-4','selectClass' => 'shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-white px-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 sm:w-auto','inputClass' => 'h-11 w-full rounded-lg border border-gray-200 bg-white py-2.5 pl-[42px] pr-4 text-sm font-medium text-gray-700 shadow-theme-xs focus:outline-hidden focus:ring-0 focus-visible:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('common.date-range-picker'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['preset' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($rangePreset),'from' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($from),'to' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($to),'wire-from-model' => 'from','wire-to-model' => 'to','class' => 'flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center lg:col-span-4','select-class' => 'shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-white px-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 sm:w-auto','input-class' => 'h-11 w-full rounded-lg border border-gray-200 bg-white py-2.5 pl-[42px] pr-4 text-sm font-medium text-gray-700 shadow-theme-xs focus:outline-hidden focus:ring-0 focus-visible:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0f75a9e682f4dfdf6a00b8cfac5a7028)): ?>
<?php $attributes = $__attributesOriginal0f75a9e682f4dfdf6a00b8cfac5a7028; ?>
<?php unset($__attributesOriginal0f75a9e682f4dfdf6a00b8cfac5a7028); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0f75a9e682f4dfdf6a00b8cfac5a7028)): ?>
<?php $component = $__componentOriginal0f75a9e682f4dfdf6a00b8cfac5a7028; ?>
<?php unset($__componentOriginal0f75a9e682f4dfdf6a00b8cfac5a7028); ?>
<?php endif; ?>
            <div class="lg:col-span-2 flex items-end justify-end">
                <a href="<?php echo e(route('vouchers.redemptions')); ?>" wire:navigate class="shadow-theme-xs inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                    Lihat Riwayat Pakai
                </a>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-950">
                <p class="text-xs text-gray-500 dark:text-gray-400">Total transaksi</p>
                <p class="mt-1 text-lg font-semibold text-gray-800 dark:text-white/90"><?php echo e(number_format((int) $totalTx, 0, ',', '.')); ?></p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-950">
                <p class="text-xs text-gray-500 dark:text-gray-400">Transaksi pakai voucher</p>
                <p class="mt-1 text-lg font-semibold text-gray-800 dark:text-white/90"><?php echo e(number_format((int) $txWithVoucher, 0, ',', '.')); ?></p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Persentase pakai: <?php echo e(number_format((float) $conversionAll, 2, ',', '.')); ?>%</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-950">
                <p class="text-xs text-gray-500 dark:text-gray-400">Total diskon voucher</p>
                <p class="mt-1 text-lg font-semibold text-gray-800 dark:text-white/90">Rp<?php echo e(number_format((int) $discountTotal, 0, ',', '.')); ?></p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-950">
                <p class="text-xs text-gray-500 dark:text-gray-400">Rata-rata nilai transaksi (AOV)</p>
                <p class="mt-1 text-sm font-semibold text-gray-800 dark:text-white/90">Rp<?php echo e(number_format((int) $avgWithVoucher, 0, ',', '.')); ?> <span class="text-xs font-medium text-gray-500 dark:text-gray-400">vs</span> Rp<?php echo e(number_format((int) $avgWithoutVoucher, 0, ',', '.')); ?></p>
                <?php
                    $impactAll = (float) $avgWithVoucher - (float) $avgWithoutVoucher;
                ?>
                <p class="mt-1 text-xs font-semibold <?php echo e($impactAll >= 0 ? 'text-success-600 dark:text-success-500' : 'text-error-600 dark:text-error-500'); ?>">
                    Selisih: <?php echo e($impactAll >= 0 ? '+' : ''); ?>Rp<?php echo e(number_format($impactAll, 0, ',', '.')); ?>

                </p>
            </div>
        </div>

        <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">AOV dihitung dari nilai transaksi setelah refund (jika ada). Data transaksi dihitung dari status terbayar dan refund.</p>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="custom-scrollbar overflow-x-auto">
            <table class="w-full table-auto">
                <thead>
                    <tr class="border-b border-gray-200 dark:divide-gray-800 dark:border-gray-800">
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Program</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Transaksi</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Persentase Pakai</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Diskon Total</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Rata-rata (Voucher)</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Rata-rata (Tanpa Voucher)</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Selisih</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                        <tr>
                            <td class="px-5 py-4">
                                <p class="text-sm font-medium text-gray-800 dark:text-white/90"><?php echo e($row['campaign_name']); ?></p>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <p class="text-sm text-gray-800 dark:text-white/90"><?php echo e(number_format((int) $row['tx_count'], 0, ',', '.')); ?></p>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <p class="text-sm text-gray-800 dark:text-white/90"><?php echo e(number_format((float) $row['conversion_rate'], 2, ',', '.')); ?>%</p>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <p class="text-sm text-gray-800 dark:text-white/90">Rp<?php echo e(number_format((int) $row['discount_sum'], 0, ',', '.')); ?></p>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <p class="text-sm text-gray-800 dark:text-white/90">Rp<?php echo e(number_format((float) $row['avg_total'], 0, ',', '.')); ?></p>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <p class="text-sm text-gray-800 dark:text-white/90">Rp<?php echo e(number_format((float) $row['avg_without_voucher'], 0, ',', '.')); ?></p>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <?php
                                    $impact = (float) $row['aov_impact'];
                                ?>
                                <p class="text-sm font-semibold <?php echo e($impact >= 0 ? 'text-success-600 dark:text-success-500' : 'text-error-600 dark:text-error-500'); ?>">
                                    <?php echo e($impact >= 0 ? '+' : ''); ?>Rp<?php echo e(number_format($impact, 0, ',', '.')); ?>

                                </p>
                            </td>
                        </tr>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        <?php if (isset($component)) { $__componentOriginal8333c7520247d01ca05cd625bf80e31f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8333c7520247d01ca05cd625bf80e31f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.common.empty-table-row','data' => ['colspan' => '7','message' => 'Belum ada data.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('common.empty-table-row'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['colspan' => '7','message' => 'Belum ada data.']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8333c7520247d01ca05cd625bf80e31f)): ?>
<?php $attributes = $__attributesOriginal8333c7520247d01ca05cd625bf80e31f; ?>
<?php unset($__attributesOriginal8333c7520247d01ca05cd625bf80e31f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8333c7520247d01ca05cd625bf80e31f)): ?>
<?php $component = $__componentOriginal8333c7520247d01ca05cd625bf80e31f; ?>
<?php unset($__componentOriginal8333c7520247d01ca05cd625bf80e31f); ?>
<?php endif; ?>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php /**PATH /home/u592471275/domains/ciyemon.com/public_html/resources/views/livewire/vouchers/voucher-performance-page.blade.php ENDPATH**/ ?>