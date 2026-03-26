<?php
    $customerName = (string) ($transaction->member?->name ?? $transaction->name ?? '-');
    $orderType = (string) ($transaction->order_type ?? '');
    $paymentMethodKey = (string) ($transaction->payment_method ?? '');
    $paymentMethodLabel = \App\Helpers\DataLabelHelper::enum($paymentMethodKey !== '' ? $paymentMethodKey : null, 'payment_method');
    $paymentStatusKey = (string) ($transaction->payment_status ?? '');
    $paymentStatusLabel = \App\Helpers\DataLabelHelper::enum($paymentStatusKey !== '' ? $paymentStatusKey : null, 'payment_status');
    $inventoryApplied = $transaction->inventory_applied_at !== null;
    $user = auth()->user();
    $voidQuickMaxCount = (int) ($correctionRules['void_quick_max_count_per_day'] ?? 0);
    $voidWindowMinutes = (int) ($correctionRules['void_quick_window_minutes'] ?? 0);
    $voidQuickUsedToday = (int) ($voidQuickUsedToday ?? 0);
    $refundQuickMaxAmount = (int) ($correctionRules['refund_quick_max_amount'] ?? 0);
    $refundQuickMaxCount = (int) ($correctionRules['refund_quick_max_count_per_day'] ?? 0);
    $refundQuickUsedToday = (int) ($refundQuickUsedToday ?? 0);
    $voidNeedsApproval = (bool) ($voidNeedsApproval ?? false);
    $refundNeedsApproval = (bool) ($refundNeedsApproval ?? false);
    $fmtCurrency = fn ($value) => 'Rp'.number_format((float) $value, 0, ',', '.');

    $voucherDiscount = (int) ($transaction->voucher_discount_amount ?? 0);
    $manualDiscount = (int) ($transaction->manual_discount_amount ?? 0);
    $pointDiscount = (int) ($transaction->point_discount_amount ?? 0);
    $discountTotal = (int) ($transaction->discount_total_amount ?? ($voucherDiscount + $manualDiscount + $pointDiscount));
    $netSubtotal = max(0, (int) ($transaction->subtotal ?? 0) - $discountTotal);

    $displayItems = $transaction->transactionItems->whereNull('parent_transaction_item_id')->values();
?>

<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-3">
                <a href="<?php echo e(route('transactions.index')); ?>" wire:navigate class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                    Kembali
                </a>
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90"><?php echo e($transaction->code); ?></h2>
            </div>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                <?php echo e(optional($transaction->created_at)->format('d M Y, H:i')); ?> · <?php echo e($paymentMethodLabel); ?> · <?php echo e($paymentStatusLabel); ?>

            </p>
        </div>
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('transactions.print')): ?>
                <button type="button" wire:click="printReceipt" class="shadow-theme-xs inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                    Cetak Struk
                </button>
            <?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($paymentStatusKey === 'pending'): ?>
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('transactions.void')): ?>
                    <button type="button" wire:click="openVoidModal" class="shadow-theme-xs inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                        Void
                    </button>
                <?php endif; ?>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(in_array($paymentStatusKey, ['paid', 'partial_refund'], true)): ?>
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('transactions.refund')): ?>
                    <button type="button" wire:click="openRefundModal" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex h-11 items-center justify-center rounded-lg px-4 text-sm font-semibold text-white transition">
                        Refund
                    </button>
                <?php endif; ?>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4 md:gap-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 md:p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-theme-sm text-gray-500 dark:text-gray-400">Subtotal</p>
            <h4 class="mt-3 text-2xl font-bold text-gray-800 dark:text-white/90"><?php echo e($fmtCurrency((int) $transaction->subtotal)); ?></h4>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 md:p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-theme-sm text-gray-500 dark:text-gray-400">Total Diskon</p>
            <h4 class="mt-3 text-2xl font-bold text-gray-800 dark:text-white/90"><?php echo e($discountTotal > 0 ? '-'.$fmtCurrency($discountTotal) : '-'); ?></h4>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 md:p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-theme-sm text-gray-500 dark:text-gray-400">Pajak PB1</p>
            <h4 class="mt-3 text-2xl font-bold text-gray-800 dark:text-white/90"><?php echo e($fmtCurrency((int) ($transaction->tax_amount ?? 0))); ?></h4>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 md:p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-theme-sm text-gray-500 dark:text-gray-400">Total</p>
            <h4 class="mt-3 text-2xl font-bold text-gray-800 dark:text-white/90"><?php echo e($fmtCurrency((int) $transaction->total)); ?></h4>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] lg:col-span-1">
            <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Ringkasan</h3>

            <dl class="mt-4 space-y-3">
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Pelanggan</dt>
                    <dd class="text-sm font-medium text-gray-800 dark:text-white/90 text-right"><?php echo e($customerName); ?></dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Tipe Pesanan</dt>
                    <dd class="text-sm font-medium text-gray-800 dark:text-white/90 text-right"><?php echo e($orderType === 'dine_in' ? 'Dine in' : 'Take away'); ?></dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Meja</dt>
                    <dd class="text-sm font-medium text-gray-800 dark:text-white/90 text-right"><?php echo e($transaction->diningTable?->name ?? '-'); ?></dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Metode</dt>
                    <dd class="text-sm font-medium text-gray-800 dark:text-white/90 text-right"><?php echo e($paymentMethodLabel); ?></dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Status</dt>
                    <dd class="text-sm font-medium text-gray-800 dark:text-white/90 text-right"><?php echo e($paymentStatusLabel); ?></dd>
                </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($discountTotal > 0): ?>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Subtotal Bersih</dt>
                        <dd class="text-sm font-medium text-gray-800 dark:text-white/90 text-right"><?php echo e($fmtCurrency($netSubtotal)); ?></dd>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Refund</dt>
                    <dd class="text-sm font-medium text-gray-800 dark:text-white/90 text-right"><?php echo e($fmtCurrency((int) ($transaction->refunded_amount ?? 0))); ?></dd>
                </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if((int) ($transaction->voucher_discount_amount ?? 0) > 0): ?>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Voucher</dt>
                        <dd class="text-sm font-medium text-gray-800 dark:text-white/90 text-right">
                            <?php echo e((string) ($transaction->voucher_code ?? '-')); ?>

                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                <?php echo e($transaction->voucherCampaign?->name ?? '-'); ?> · -<?php echo e($fmtCurrency((int) ($transaction->voucher_discount_amount ?? 0))); ?>

                            </div>
                        </dd>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if((int) ($transaction->manual_discount_amount ?? 0) > 0): ?>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Diskon Manual</dt>
                        <dd class="text-sm font-medium text-gray-800 dark:text-white/90 text-right">
                            -<?php echo e($fmtCurrency((int) ($transaction->manual_discount_amount ?? 0))); ?>

                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($transaction->manual_discount_type && $transaction->manual_discount_value): ?>
                                    <?php echo e($transaction->manual_discount_type === 'percent' ? ($transaction->manual_discount_value.'%') : ('Rp'.number_format((int) $transaction->manual_discount_value, 0, ',', '.'))); ?>

                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($transaction->manual_discount_note): ?>
                                    · <?php echo e($transaction->manual_discount_note); ?>

                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($transaction->manualDiscountByUser): ?>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Oleh: <?php echo e($transaction->manualDiscountByUser->name); ?></div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </dd>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if((int) ($transaction->point_discount_amount ?? 0) > 0 || (int) ($transaction->points_redeemed ?? 0) > 0 || (int) ($transaction->points_earned ?? 0) > 0): ?>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Poin</dt>
                        <dd class="text-sm font-medium text-gray-800 dark:text-white/90 text-right">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if((int) ($transaction->point_discount_amount ?? 0) > 0): ?>
                                <div>-<?php echo e($fmtCurrency((int) ($transaction->point_discount_amount ?? 0))); ?></div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if((int) ($transaction->points_redeemed ?? 0) > 0): ?>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Dipakai: <?php echo e(number_format((int) ($transaction->points_redeemed ?? 0), 0, ',', '.')); ?></div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if((int) ($transaction->points_earned ?? 0) > 0): ?>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Didapat: <?php echo e(number_format((int) ($transaction->points_earned ?? 0), 0, ',', '.')); ?></div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </dd>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Inventory</dt>
                    <dd class="text-right">
                        <span class="rounded-full px-2 py-0.5 text-theme-xs font-medium <?php echo e($inventoryApplied ? 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500' : 'bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-orange-400'); ?>">
                            <?php echo e($inventoryApplied ? 'Applied' : 'Pending'); ?>

                        </span>
                    </dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Cash diterima</dt>
                    <dd class="text-sm font-medium text-gray-800 dark:text-white/90 text-right"><?php echo e($fmtCurrency((int) ($transaction->cash_received ?? 0))); ?></dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Kembalian</dt>
                    <dd class="text-sm font-medium text-gray-800 dark:text-white/90 text-right"><?php echo e($fmtCurrency((int) ($transaction->cash_change ?? 0))); ?></dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">External ID</dt>
                    <dd class="text-sm font-medium text-gray-800 dark:text-white/90 text-right">
                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('transactions.pii.view')): ?>
                            <?php echo e($transaction->external_id); ?>

                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </dd>
                </div>
            </dl>

            <?php if (isset($component)) { $__componentOriginalee90cf1aab8b8cee8674701eaf7a143f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalee90cf1aab8b8cee8674701eaf7a143f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.common.input-error','data' => ['for' => 'inventory','class' => 'mt-4 text-sm text-error-600']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('common.input-error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'inventory','class' => 'mt-4 text-sm text-error-600']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalee90cf1aab8b8cee8674701eaf7a143f)): ?>
<?php $attributes = $__attributesOriginalee90cf1aab8b8cee8674701eaf7a143f; ?>
<?php unset($__attributesOriginalee90cf1aab8b8cee8674701eaf7a143f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalee90cf1aab8b8cee8674701eaf7a143f)): ?>
<?php $component = $__componentOriginalee90cf1aab8b8cee8674701eaf7a143f; ?>
<?php unset($__componentOriginalee90cf1aab8b8cee8674701eaf7a143f); ?>
<?php endif; ?>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! $inventoryApplied): ?>
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('inventory.manage')): ?>
                    <button type="button" wire:click="processInventory" class="mt-4 bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex h-11 w-full items-center justify-center rounded-lg px-4 text-sm font-medium text-white transition">
                        Proses Inventory
                    </button>
                <?php endif; ?>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>

        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] lg:col-span-2">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Item Transaksi</h3>
            </div>

            <div class="custom-scrollbar overflow-x-auto">
                <table class="w-full table-auto">
                    <thead>
                        <tr class="border-b border-gray-200 dark:divide-gray-800 dark:border-gray-800">
                            <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Produk</th>
                            <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Varian</th>
                            <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Qty</th>
                            <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Harga</th>
                            <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Subtotal</th>
                            <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">HPP</th>
                            <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Laba</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $displayItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                            <?php
                                $hppTotal = (float) ($item->hpp_total ?? 0);
                                $voucherItemDiscount = (int) ($item->voucher_discount_amount ?? 0);
                                $manualItemDiscount = (int) ($item->manual_discount_amount ?? 0);
                                $netLineSubtotal = (float) $item->subtotal - $voucherItemDiscount - $manualItemDiscount;
                                $profit = $netLineSubtotal - $hppTotal;
                                $inventoryApplied = $transaction->inventory_applied_at !== null;
                                $children = $transaction->transactionItems->where('parent_transaction_item_id', (int) $item->id)->values();
                                $variantDisplay = \App\Support\Products\ItemNameFormatter::displayVariantName((int) $item->product_id, $item->variant?->name);
                            ?>
                            <tr>
                                <td class="px-5 py-4">
                                    <div class="space-y-1">
                                        <p class="text-sm font-medium text-gray-800 dark:text-white/90"><?php echo e($item->product?->name ?? '-'); ?></p>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->note): ?>
                                            <p class="text-xs text-gray-500 dark:text-gray-400"><?php echo e($item->note); ?></p>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($children->isNotEmpty()): ?>
                                            <div class="space-y-0.5">
                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $children; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $child): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                                                    <?php
                                                        $childVariant = \App\Support\Products\ItemNameFormatter::displayVariantName((int) $child->product_id, $child->variant?->name);
                                                    ?>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                                        • <?php echo e((string) ($child->product?->name ?? 'Produk')); ?><?php echo e($childVariant !== '' ? ' - '.$childVariant : ''); ?> x<?php echo e(number_format((int) ($child->quantity ?? 0), 0, ',', '.')); ?>

                                                    </p>
                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                            </div>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="text-sm text-gray-800 dark:text-white/90"><?php echo e($variantDisplay !== '' ? $variantDisplay : '-'); ?></p>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <p class="text-sm font-medium text-gray-800 dark:text-white/90"><?php echo e(number_format((int) $item->quantity, 0, ',', '.')); ?></p>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <p class="text-sm text-gray-800 dark:text-white/90"><?php echo e($fmtCurrency((float) $item->price)); ?></p>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <p class="text-sm font-semibold text-gray-800 dark:text-white/90"><?php echo e($fmtCurrency((float) $item->subtotal)); ?></p>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($voucherItemDiscount > 0 || $manualItemDiscount > 0): ?>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            Diskon:
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($voucherItemDiscount > 0): ?>
                                                Voucher <?php echo e($fmtCurrency($voucherItemDiscount)); ?>

                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($manualItemDiscount > 0): ?>
                                                <?php echo e($voucherItemDiscount > 0 ? '·' : ''); ?> Manual <?php echo e($fmtCurrency($manualItemDiscount)); ?>

                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Net: <?php echo e($fmtCurrency($netLineSubtotal)); ?></p>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <p class="text-sm text-gray-800 dark:text-white/90"><?php echo e($inventoryApplied ? $fmtCurrency($hppTotal) : '-'); ?></p>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <p class="text-sm font-semibold <?php echo e($hppTotal > 0 && $profit < 0 ? 'text-error-600 dark:text-error-500' : 'text-gray-800 dark:text-white/90'); ?>">
                                        <?php echo e($inventoryApplied ? $fmtCurrency($profit) : '-'); ?>

                                    </p>
                                </td>
                            </tr>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-200 px-5 py-4 dark:border-gray-800">
                <div class="flex flex-col gap-2 text-sm">
                    <?php
                        $totalHpp = (float) $displayItems->sum('hpp_total');
                        $totalProfit = (float) $netSubtotal - $totalHpp;
                        $inventoryApplied = $transaction->inventory_applied_at !== null;
                        $feeAmount = (int) ($transaction->payment_fee_amount ?? 0);
                        $pointDiscountAmount = (int) ($transaction->point_discount_amount ?? 0);
                        $roundingAmount = (int) ($transaction->rounding_amount ?? 0);
                    ?>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">Subtotal</span>
                        <span class="font-medium text-gray-800 dark:text-white/90"><?php echo e($fmtCurrency((int) $transaction->subtotal)); ?></span>
                    </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($discountTotal > 0): ?>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Total Diskon</span>
                            <span class="font-medium text-gray-800 dark:text-white/90">-<?php echo e($fmtCurrency($discountTotal)); ?></span>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if((int) ($transaction->voucher_discount_amount ?? 0) > 0): ?>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Diskon Voucher</span>
                            <span class="font-medium text-gray-800 dark:text-white/90">-<?php echo e($fmtCurrency((int) $transaction->voucher_discount_amount)); ?></span>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if((int) ($transaction->manual_discount_amount ?? 0) > 0): ?>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Diskon Manual</span>
                            <span class="font-medium text-gray-800 dark:text-white/90">-<?php echo e($fmtCurrency((int) $transaction->manual_discount_amount)); ?></span>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($pointDiscountAmount > 0): ?>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Diskon Poin</span>
                            <span class="font-medium text-gray-800 dark:text-white/90">-<?php echo e($fmtCurrency($pointDiscountAmount)); ?></span>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($discountTotal > 0): ?>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Subtotal Bersih</span>
                            <span class="font-medium text-gray-800 dark:text-white/90"><?php echo e($fmtCurrency((int) $netSubtotal)); ?></span>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if((int) ($transaction->points_redeemed ?? 0) > 0 || (int) ($transaction->points_earned ?? 0) > 0): ?>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Poin Dipakai</span>
                            <span class="font-medium text-gray-800 dark:text-white/90"><?php echo e(number_format((int) ($transaction->points_redeemed ?? 0), 0, ',', '.')); ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Poin Didapat</span>
                            <span class="font-medium text-gray-800 dark:text-white/90"><?php echo e(number_format((int) ($transaction->points_earned ?? 0), 0, ',', '.')); ?></span>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">Total HPP</span>
                        <span class="font-medium text-gray-800 dark:text-white/90"><?php echo e($inventoryApplied ? $fmtCurrency($totalHpp) : 'Belum diproses'); ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">Estimasi Laba</span>
                        <span class="font-medium text-gray-800 dark:text-white/90"><?php echo e($inventoryApplied ? $fmtCurrency($totalProfit) : '-'); ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">Pajak PB1</span>
                        <span class="font-medium text-gray-800 dark:text-white/90"><?php echo e($fmtCurrency((int) ($transaction->tax_amount ?? 0))); ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">Biaya Admin</span>
                        <span class="font-medium text-gray-800 dark:text-white/90"><?php echo e($feeAmount > 0 ? $fmtCurrency($feeAmount) : '-'); ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">Pembulatan</span>
                        <span class="font-medium text-gray-800 dark:text-white/90"><?php echo e($fmtCurrency($roundingAmount)); ?></span>
                    </div>
                    <div class="flex items-center justify-between pt-2 border-t border-gray-100 dark:border-gray-800">
                        <span class="text-gray-800 dark:text-white/90 font-semibold">Total</span>
                        <span class="text-gray-800 dark:text-white/90 font-semibold"><?php echo e($fmtCurrency((int) $transaction->total)); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
            <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Riwayat Aktivitas</h3>
        </div>
        <div class="divide-y divide-gray-100 dark:divide-gray-800">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $transaction->events; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $event): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                <div class="px-5 py-4">
                    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-2">
                            <span class="rounded-full px-2 py-0.5 text-theme-xs font-medium <?php echo e($event->action === 'refund' ? 'bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-orange-400' : ($event->action === 'void' ? 'bg-gray-50 text-gray-700 dark:bg-gray-500/15 dark:text-gray-300' : 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500')); ?>">
                                <?php echo e(strtoupper((string) $event->action)); ?>

                            </span>
                            <p class="text-sm font-medium text-gray-800 dark:text-white/90">
                                <?php echo e($event->actor?->name ?? 'System'); ?>

                            </p>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400"><?php echo e(optional($event->created_at)->format('d M Y, H:i')); ?></p>
                    </div>

                    <?php
                        $meta = (array) ($event->meta ?? []);
                    ?>
                    <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! empty($meta['reason'])): ?>
                            <p class="text-sm text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">Alasan:</span> <?php echo e($meta['reason']); ?></p>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! empty($meta['amount'])): ?>
                            <p class="text-sm text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">Nominal:</span> <?php echo e($fmtCurrency((int) $meta['amount'])); ?></p>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($event->action === 'voucher_redeem'): ?>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! empty($meta['voucher_code'])): ?>
                                <p class="text-sm text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">Voucher:</span> <?php echo e((string) $meta['voucher_code']); ?></p>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! empty($meta['discount_amount'])): ?>
                                <p class="text-sm text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">Diskon:</span> <?php echo e($fmtCurrency((int) $meta['discount_amount'])); ?></p>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($event->action === 'manual_discount'): ?>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! empty($meta['amount'])): ?>
                                <p class="text-sm text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">Diskon:</span> <?php echo e($fmtCurrency((int) $meta['amount'])); ?></p>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! empty($meta['type']) && ! empty($meta['value'])): ?>
                                <p class="text-sm text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">Aturan:</span> <?php echo e($meta['type'] === 'percent' ? ((int) $meta['value']).'%' : $fmtCurrency((int) $meta['value'])); ?></p>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($event->action === 'point_redeem'): ?>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! empty($meta['points_redeemed'])): ?>
                                <p class="text-sm text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">Poin dipakai:</span> <?php echo e(number_format((int) $meta['points_redeemed'], 0, ',', '.')); ?></p>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! empty($meta['point_discount_amount'])): ?>
                                <p class="text-sm text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">Diskon poin:</span> <?php echo e($fmtCurrency((int) $meta['point_discount_amount'])); ?></p>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! empty($meta['approval_required'])): ?>
                            <?php ($approvedName = ! empty($meta['approved_by_user_id']) ? ($approvedBy[(int) $meta['approved_by_user_id']] ?? null) : null); ?>
                            <p class="text-sm text-gray-700 dark:text-gray-300">
                                <span class="text-gray-500 dark:text-gray-400">Approval:</span>
                                <?php echo e($approvedName ? 'Disetujui oleh '.$approvedName : 'Dibutuhkan'); ?>

                            </p>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! empty($meta['previous_payment_status']) || ! empty($meta['new_payment_status'])): ?>
                            <p class="text-sm text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">Status:</span> <?php echo e(\App\Helpers\DataLabelHelper::enum($meta['previous_payment_status'] ?? null, 'payment_status')); ?> → <?php echo e(\App\Helpers\DataLabelHelper::enum($meta['new_payment_status'] ?? null, 'payment_status')); ?></p>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(array_key_exists('revert_inventory', $meta)): ?>
                            <p class="text-sm text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">Revert stok:</span> <?php echo e((bool) $meta['revert_inventory'] ? 'Ya' : 'Tidak'); ?></p>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                <div class="px-5 py-10">
                    <p class="text-center text-sm text-gray-500 dark:text-gray-400">Belum ada koreksi.</p>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($voidModalOpen): ?>
        <div class="fixed inset-0 z-[100000] flex items-center justify-center p-4" aria-modal="true" role="dialog">
            <div class="absolute inset-0 bg-black/50" wire:click="closeVoidModal"></div>
            <div class="relative w-full max-w-2xl overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <div>
                        <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Void Transaksi</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Void hanya untuk transaksi pending.</p>
                    </div>
                    <button type="button" wire:click="closeVoidModal" class="text-sm font-medium text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
                        Tutup
                    </button>
                </div>
                <form wire:submit="voidTransaction" class="space-y-4 p-5">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Alasan</label>
                        <input wire:model.live="correctionReason" type="text" aria-invalid="<?php echo e($errors->has('correctionReason') ? 'true' : 'false'); ?>" aria-describedby="<?php echo e($errors->has('correctionReason') ? 'error-correctionReason' : ''); ?>" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                        <?php if (isset($component)) { $__componentOriginalee90cf1aab8b8cee8674701eaf7a143f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalee90cf1aab8b8cee8674701eaf7a143f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.common.input-error','data' => ['for' => 'correctionReason']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('common.input-error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'correctionReason']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalee90cf1aab8b8cee8674701eaf7a143f)): ?>
<?php $attributes = $__attributesOriginalee90cf1aab8b8cee8674701eaf7a143f; ?>
<?php unset($__attributesOriginalee90cf1aab8b8cee8674701eaf7a143f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalee90cf1aab8b8cee8674701eaf7a143f)): ?>
<?php $component = $__componentOriginalee90cf1aab8b8cee8674701eaf7a143f; ?>
<?php unset($__componentOriginalee90cf1aab8b8cee8674701eaf7a143f); ?>
<?php endif; ?>
                    </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($voidNeedsApproval): ?>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-900">
                            <p class="text-sm font-semibold text-gray-800 dark:text-white/90">Approval (PIN)</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Dibutuhkan sesuai aturan sistem. Batas void cepat: maks <?php echo e(number_format($voidQuickMaxCount, 0, ',', '.')); ?>/hari dan window <?php echo e(number_format($voidWindowMinutes, 0, ',', '.')); ?> menit.
                            </p>

                            <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Approver (Opsional)</label>
                                    <select wire:model.live="approverUserId" aria-invalid="<?php echo e($errors->has('approverUserId') ? 'true' : 'false'); ?>" aria-describedby="<?php echo e($errors->has('approverUserId') ? 'error-approverUserId' : ''); ?>" class="shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-white px-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                                        <option value="">Auto (pakai PIN)</option>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $voidApprovers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $approver): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                                            <option value="<?php echo e((int) $approver->id); ?>"><?php echo e($approver->name); ?></option>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                    </select>
                                    <?php if (isset($component)) { $__componentOriginalee90cf1aab8b8cee8674701eaf7a143f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalee90cf1aab8b8cee8674701eaf7a143f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.common.input-error','data' => ['for' => 'approverUserId']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('common.input-error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'approverUserId']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalee90cf1aab8b8cee8674701eaf7a143f)): ?>
<?php $attributes = $__attributesOriginalee90cf1aab8b8cee8674701eaf7a143f; ?>
<?php unset($__attributesOriginalee90cf1aab8b8cee8674701eaf7a143f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalee90cf1aab8b8cee8674701eaf7a143f)): ?>
<?php $component = $__componentOriginalee90cf1aab8b8cee8674701eaf7a143f; ?>
<?php unset($__componentOriginalee90cf1aab8b8cee8674701eaf7a143f); ?>
<?php endif; ?>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Kosongkan jika ingin sistem otomatis mendeteksi approver dari PIN.</p>
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">PIN</label>
                                    <input wire:model.live="approverPin" type="password" inputmode="numeric" aria-invalid="<?php echo e($errors->has('approverPin') ? 'true' : 'false'); ?>" aria-describedby="<?php echo e($errors->has('approverPin') ? 'error-approverPin' : ''); ?>" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" placeholder="PIN approver" />
                                    <?php if (isset($component)) { $__componentOriginalee90cf1aab8b8cee8674701eaf7a143f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalee90cf1aab8b8cee8674701eaf7a143f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.common.input-error','data' => ['for' => 'approverPin']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('common.input-error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'approverPin']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalee90cf1aab8b8cee8674701eaf7a143f)): ?>
<?php $attributes = $__attributesOriginalee90cf1aab8b8cee8674701eaf7a143f; ?>
<?php unset($__attributesOriginalee90cf1aab8b8cee8674701eaf7a143f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalee90cf1aab8b8cee8674701eaf7a143f)): ?>
<?php $component = $__componentOriginalee90cf1aab8b8cee8674701eaf7a143f; ?>
<?php unset($__componentOriginalee90cf1aab8b8cee8674701eaf7a143f); ?>
<?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($inventoryApplied): ?>
                        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <input wire:model.live="revertInventory" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500/20 dark:border-gray-700" />
                            Revert stok (buat pergerakan reversal)
                        </label>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <div class="flex items-center justify-end gap-2">
                        <button type="button" wire:click="closeVoidModal" class="shadow-theme-xs inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                            Batal
                        </button>
                        <button type="submit" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex h-11 items-center justify-center rounded-lg px-4 text-sm font-semibold text-white transition">
                            Void
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($refundModalOpen): ?>
        <div class="fixed inset-0 z-[100000] flex items-center justify-center p-4" aria-modal="true" role="dialog">
            <div class="absolute inset-0 bg-black/50" wire:click="closeRefundModal"></div>
            <div class="relative w-full max-w-2xl overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <div>
                        <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Refund Transaksi</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Refund dicatat untuk kebutuhan audit dan laporan.</p>
                    </div>
                    <button type="button" wire:click="closeRefundModal" class="text-sm font-medium text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
                        Tutup
                    </button>
                </div>
                <form wire:submit="refundTransaction" class="space-y-4 p-5">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Alasan</label>
                            <input wire:model.live="correctionReason" type="text" aria-invalid="<?php echo e($errors->has('correctionReason') ? 'true' : 'false'); ?>" aria-describedby="<?php echo e($errors->has('correctionReason') ? 'error-correctionReason' : ''); ?>" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                            <?php if (isset($component)) { $__componentOriginalee90cf1aab8b8cee8674701eaf7a143f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalee90cf1aab8b8cee8674701eaf7a143f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.common.input-error','data' => ['for' => 'correctionReason']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('common.input-error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'correctionReason']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalee90cf1aab8b8cee8674701eaf7a143f)): ?>
<?php $attributes = $__attributesOriginalee90cf1aab8b8cee8674701eaf7a143f; ?>
<?php unset($__attributesOriginalee90cf1aab8b8cee8674701eaf7a143f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalee90cf1aab8b8cee8674701eaf7a143f)): ?>
<?php $component = $__componentOriginalee90cf1aab8b8cee8674701eaf7a143f; ?>
<?php unset($__componentOriginalee90cf1aab8b8cee8674701eaf7a143f); ?>
<?php endif; ?>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Nominal Refund</label>
                            <?php if (isset($component)) { $__componentOriginald69271f1dfc0ddf0507c6c7ef7e709e0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald69271f1dfc0ddf0507c6c7ef7e709e0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.common.rupiah-input','data' => ['wireModel' => 'refundAmount','placeholder' => '0']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('common.rupiah-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire-model' => 'refundAmount','placeholder' => '0']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald69271f1dfc0ddf0507c6c7ef7e709e0)): ?>
<?php $attributes = $__attributesOriginald69271f1dfc0ddf0507c6c7ef7e709e0; ?>
<?php unset($__attributesOriginald69271f1dfc0ddf0507c6c7ef7e709e0); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald69271f1dfc0ddf0507c6c7ef7e709e0)): ?>
<?php $component = $__componentOriginald69271f1dfc0ddf0507c6c7ef7e709e0; ?>
<?php unset($__componentOriginald69271f1dfc0ddf0507c6c7ef7e709e0); ?>
<?php endif; ?>
                            <?php if (isset($component)) { $__componentOriginalee90cf1aab8b8cee8674701eaf7a143f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalee90cf1aab8b8cee8674701eaf7a143f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.common.input-error','data' => ['for' => 'refundAmount']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('common.input-error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'refundAmount']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalee90cf1aab8b8cee8674701eaf7a143f)): ?>
<?php $attributes = $__attributesOriginalee90cf1aab8b8cee8674701eaf7a143f; ?>
<?php unset($__attributesOriginalee90cf1aab8b8cee8674701eaf7a143f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalee90cf1aab8b8cee8674701eaf7a143f)): ?>
<?php $component = $__componentOriginalee90cf1aab8b8cee8674701eaf7a143f; ?>
<?php unset($__componentOriginalee90cf1aab8b8cee8674701eaf7a143f); ?>
<?php endif; ?>
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Batas refund cepat: Rp<?php echo e(number_format($refundQuickMaxAmount, 0, ',', '.')); ?> (maks <?php echo e(number_format($refundQuickMaxCount, 0, ',', '.')); ?>/hari/kasir). Jika melebihi, sistem minta PIN.</p>
                        </div>
                        <div class="flex items-end">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($inventoryApplied): ?>
                                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <input wire:model.live="revertInventory" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500/20 dark:border-gray-700" />
                                    Revert stok
                                </label>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($refundNeedsApproval): ?>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-900">
                            <p class="text-sm font-semibold text-gray-800 dark:text-white/90">Approval (PIN)</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Refund ini memerlukan approval (PIN) sesuai aturan sistem.</p>

                            <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Approver (Opsional)</label>
                                    <select wire:model.live="approverUserId" aria-invalid="<?php echo e($errors->has('approverUserId') ? 'true' : 'false'); ?>" aria-describedby="<?php echo e($errors->has('approverUserId') ? 'error-approverUserId' : ''); ?>" class="shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-white px-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                                        <option value="">Auto (pakai PIN)</option>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $refundApprovers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $approver): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                                            <option value="<?php echo e((int) $approver->id); ?>"><?php echo e($approver->name); ?></option>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                    </select>
                                    <?php if (isset($component)) { $__componentOriginalee90cf1aab8b8cee8674701eaf7a143f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalee90cf1aab8b8cee8674701eaf7a143f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.common.input-error','data' => ['for' => 'approverUserId']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('common.input-error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'approverUserId']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalee90cf1aab8b8cee8674701eaf7a143f)): ?>
<?php $attributes = $__attributesOriginalee90cf1aab8b8cee8674701eaf7a143f; ?>
<?php unset($__attributesOriginalee90cf1aab8b8cee8674701eaf7a143f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalee90cf1aab8b8cee8674701eaf7a143f)): ?>
<?php $component = $__componentOriginalee90cf1aab8b8cee8674701eaf7a143f; ?>
<?php unset($__componentOriginalee90cf1aab8b8cee8674701eaf7a143f); ?>
<?php endif; ?>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Kosongkan jika ingin sistem otomatis mendeteksi approver dari PIN.</p>
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">PIN</label>
                                    <input wire:model.live="approverPin" type="password" inputmode="numeric" aria-invalid="<?php echo e($errors->has('approverPin') ? 'true' : 'false'); ?>" aria-describedby="<?php echo e($errors->has('approverPin') ? 'error-approverPin' : ''); ?>" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" placeholder="PIN approver" />
                                    <?php if (isset($component)) { $__componentOriginalee90cf1aab8b8cee8674701eaf7a143f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalee90cf1aab8b8cee8674701eaf7a143f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.common.input-error','data' => ['for' => 'approverPin']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('common.input-error'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'approverPin']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalee90cf1aab8b8cee8674701eaf7a143f)): ?>
<?php $attributes = $__attributesOriginalee90cf1aab8b8cee8674701eaf7a143f; ?>
<?php unset($__attributesOriginalee90cf1aab8b8cee8674701eaf7a143f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalee90cf1aab8b8cee8674701eaf7a143f)): ?>
<?php $component = $__componentOriginalee90cf1aab8b8cee8674701eaf7a143f; ?>
<?php unset($__componentOriginalee90cf1aab8b8cee8674701eaf7a143f); ?>
<?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <div class="flex items-center justify-end gap-2">
                        <button type="button" wire:click="closeRefundModal" class="shadow-theme-xs inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                            Batal
                        </button>
                        <button type="submit" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex h-11 items-center justify-center rounded-lg px-4 text-sm font-semibold text-white transition">
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>
<?php /**PATH /Users/bagusws/Documents/GitHub/kasir-v1/resources/views/livewire/transactions/transaction-show-page.blade.php ENDPATH**/ ?>