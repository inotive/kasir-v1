<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($paginator->hasPages()): ?>
    <?php
        $current = (int) $paginator->currentPage();
        $last = (int) $paginator->lastPage();
        $pageName = (string) $paginator->getPageName();

        $pages = [1];

        if ($current <= 2) {
            $pages[] = 2;
        } elseif ($current >= $last - 1) {
            $pages[] = max(1, $last - 1);
        } else {
            $pages[] = $current;
        }

        $pages[] = $last;
        $pages = array_values(array_unique(array_filter($pages, fn ($p) => is_int($p) && $p >= 1 && $p <= $last)));
        sort($pages);
    ?>
    <nav role="navigation" aria-label="Pagination" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="text-xs font-medium text-gray-500 dark:text-gray-400">
            Menampilkan <?php echo e(number_format($paginator->firstItem() ?? 0, 0, ',', '.')); ?>–<?php echo e(number_format($paginator->lastItem() ?? 0, 0, ',', '.')); ?> dari <?php echo e(number_format($paginator->total(), 0, ',', '.')); ?>

        </div>

        <div class="flex flex-wrap items-center gap-2">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($paginator->onFirstPage()): ?>
                <span aria-disabled="true" aria-label="Sebelumnya" class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-400 opacity-60 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-500">
                    Sebelumnya
                </span>
            <?php else: ?>
                <button type="button" wire:click="previousPage('<?php echo e($pageName); ?>')" rel="prev" class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                    Sebelumnya
                </button>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <div class="hidden items-center gap-1 sm:flex">
                <?php $prevShown = 0; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $pages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $page): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($prevShown > 0 && $page > $prevShown + 1): ?>
                        <span aria-disabled="true" class="px-2 py-2 text-xs font-medium text-gray-400 dark:text-gray-500">…</span>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($page === $current): ?>
                        <span aria-current="page" class="shadow-theme-xs inline-flex h-9 min-w-9 items-center justify-center rounded-lg border border-brand-600 bg-brand-600 px-3 text-xs font-semibold text-white">
                            <?php echo e($page); ?>

                        </span>
                    <?php else: ?>
                        <button type="button" wire:click="gotoPage(<?php echo e($page); ?>, '<?php echo e($pageName); ?>')" class="shadow-theme-xs inline-flex h-9 min-w-9 items-center justify-center rounded-lg border border-gray-300 bg-white px-3 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                            <?php echo e($page); ?>

                        </button>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    <?php $prevShown = $page; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </div>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($paginator->hasMorePages()): ?>
                <button type="button" wire:click="nextPage('<?php echo e($pageName); ?>')" rel="next" class="shadow-theme-xs inline-flex items-center justify-center rounded-lg bg-brand-600 px-3 py-2 text-xs font-medium text-white hover:bg-brand-700">
                    Berikutnya
                </button>
            <?php else: ?>
                <span aria-disabled="true" aria-label="Berikutnya" class="shadow-theme-xs inline-flex items-center justify-center rounded-lg bg-gray-100 px-3 py-2 text-xs font-medium text-gray-400 dark:bg-gray-800 dark:text-gray-500">
                    Berikutnya
                </span>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </nav>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php /**PATH /Users/bagusws/Documents/GitHub/kasir-v1/resources/views/livewire/pagination/admin.blade.php ENDPATH**/ ?>