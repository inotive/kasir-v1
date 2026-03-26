<div class="grid grid-cols-12 gap-4 overflow-x-hidden md:gap-6">
    <div class="col-span-12 min-w-0 space-y-6 xl:col-span-7">
        <?php if (isset($component)) { $__componentOriginalcc6f8ad25af541e3fa4cb08458942845 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalcc6f8ad25af541e3fa4cb08458942845 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ecommerce.ecommerce-metrics','data' => ['transactions' => $transactionsCount,'transactionsDeltaPercent' => $transactionsDeltaPercent,'transactionsDeltaUp' => $transactionsDeltaUp,'revenueAmount' => $todayRevenueAmount]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ecommerce.ecommerce-metrics'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['transactions' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($transactionsCount),'transactions-delta-percent' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($transactionsDeltaPercent),'transactions-delta-up' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($transactionsDeltaUp),'revenue-amount' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($todayRevenueAmount)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalcc6f8ad25af541e3fa4cb08458942845)): ?>
<?php $attributes = $__attributesOriginalcc6f8ad25af541e3fa4cb08458942845; ?>
<?php unset($__attributesOriginalcc6f8ad25af541e3fa4cb08458942845); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalcc6f8ad25af541e3fa4cb08458942845)): ?>
<?php $component = $__componentOriginalcc6f8ad25af541e3fa4cb08458942845; ?>
<?php unset($__componentOriginalcc6f8ad25af541e3fa4cb08458942845); ?>
<?php endif; ?>
        <?php if (isset($component)) { $__componentOriginalca5009a9c4c2c136e4109da267fcd9af = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalca5009a9c4c2c136e4109da267fcd9af = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ecommerce.statistics-chart','data' => ['series' => $statisticsSeries,'categories' => $statisticsCategories,'from' => $statisticsFrom,'to' => $statisticsTo]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ecommerce.statistics-chart'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['series' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($statisticsSeries),'categories' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($statisticsCategories),'from' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($statisticsFrom),'to' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($statisticsTo)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalca5009a9c4c2c136e4109da267fcd9af)): ?>
<?php $attributes = $__attributesOriginalca5009a9c4c2c136e4109da267fcd9af; ?>
<?php unset($__attributesOriginalca5009a9c4c2c136e4109da267fcd9af); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalca5009a9c4c2c136e4109da267fcd9af)): ?>
<?php $component = $__componentOriginalca5009a9c4c2c136e4109da267fcd9af; ?>
<?php unset($__componentOriginalca5009a9c4c2c136e4109da267fcd9af); ?>
<?php endif; ?>
        <?php if (isset($component)) { $__componentOriginal217ed44ba64918be147d0473951150f0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal217ed44ba64918be147d0473951150f0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ecommerce.latest-transactions','data' => ['transactions' => $latestTransactions]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ecommerce.latest-transactions'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['transactions' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($latestTransactions)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal217ed44ba64918be147d0473951150f0)): ?>
<?php $attributes = $__attributesOriginal217ed44ba64918be147d0473951150f0; ?>
<?php unset($__attributesOriginal217ed44ba64918be147d0473951150f0); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal217ed44ba64918be147d0473951150f0)): ?>
<?php $component = $__componentOriginal217ed44ba64918be147d0473951150f0; ?>
<?php unset($__componentOriginal217ed44ba64918be147d0473951150f0); ?>
<?php endif; ?>
    </div>

    <div class="col-span-12 min-w-0 space-y-6 xl:col-span-5">
        <?php if (isset($component)) { $__componentOriginal957636d633a7faaa6f525f02160454f5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal957636d633a7faaa6f525f02160454f5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ecommerce.monthly-target','data' => ['progressPercent' => $monthlyTargetProgressPercent,'deltaPercent' => $monthlyTargetDeltaPercent,'deltaUp' => $monthlyTargetDeltaUp,'targetAmount' => $monthlyTargetAmount,'revenueAmount' => $monthlyRevenueAmount,'todayAmount' => $todayRevenueAmount]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ecommerce.monthly-target'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['progress-percent' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($monthlyTargetProgressPercent),'delta-percent' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($monthlyTargetDeltaPercent),'delta-up' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($monthlyTargetDeltaUp),'target-amount' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($monthlyTargetAmount),'revenue-amount' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($monthlyRevenueAmount),'today-amount' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($todayRevenueAmount)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal957636d633a7faaa6f525f02160454f5)): ?>
<?php $attributes = $__attributesOriginal957636d633a7faaa6f525f02160454f5; ?>
<?php unset($__attributesOriginal957636d633a7faaa6f525f02160454f5); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal957636d633a7faaa6f525f02160454f5)): ?>
<?php $component = $__componentOriginal957636d633a7faaa6f525f02160454f5; ?>
<?php unset($__componentOriginal957636d633a7faaa6f525f02160454f5); ?>
<?php endif; ?>
        <?php if (isset($component)) { $__componentOriginalf3d9311b888bb42527789849d0bdf7d8 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf3d9311b888bb42527789849d0bdf7d8 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ecommerce.best-selling-products','data' => ['products' => $bestSellingProducts]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ecommerce.best-selling-products'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['products' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($bestSellingProducts)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf3d9311b888bb42527789849d0bdf7d8)): ?>
<?php $attributes = $__attributesOriginalf3d9311b888bb42527789849d0bdf7d8; ?>
<?php unset($__attributesOriginalf3d9311b888bb42527789849d0bdf7d8); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf3d9311b888bb42527789849d0bdf7d8)): ?>
<?php $component = $__componentOriginalf3d9311b888bb42527789849d0bdf7d8; ?>
<?php unset($__componentOriginalf3d9311b888bb42527789849d0bdf7d8); ?>
<?php endif; ?>
    </div>
</div>
<?php /**PATH /home/u592471275/domains/ciyemon.com/public_html/resources/views/livewire/dashboard-page.blade.php ENDPATH**/ ?>