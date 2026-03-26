<div class="grid grid-cols-12 gap-4 overflow-x-hidden md:gap-6">
    <div class="col-span-12 min-w-0 space-y-6 xl:col-span-7">
        <?php if (isset($component)) { $__componentOriginalc07b2e1699f32a6c6657e13682549915 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc07b2e1699f32a6c6657e13682549915 = $attributes; } ?>
<?php $component = App\View\Components\Ecommerce\EcommerceMetrics::resolve(['revenueAmount' => $todayRevenueAmount] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ecommerce.ecommerce-metrics'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\Ecommerce\EcommerceMetrics::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['transactions' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($transactionsCount),'transactions-delta-percent' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($transactionsDeltaPercent),'transactions-delta-up' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($transactionsDeltaUp)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc07b2e1699f32a6c6657e13682549915)): ?>
<?php $attributes = $__attributesOriginalc07b2e1699f32a6c6657e13682549915; ?>
<?php unset($__attributesOriginalc07b2e1699f32a6c6657e13682549915); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc07b2e1699f32a6c6657e13682549915)): ?>
<?php $component = $__componentOriginalc07b2e1699f32a6c6657e13682549915; ?>
<?php unset($__componentOriginalc07b2e1699f32a6c6657e13682549915); ?>
<?php endif; ?>
        <?php if (isset($component)) { $__componentOriginal136890e789f0e924cbac157c7abdf091 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal136890e789f0e924cbac157c7abdf091 = $attributes; } ?>
<?php $component = App\View\Components\Ecommerce\StatisticsChart::resolve(['series' => $statisticsSeries,'categories' => $statisticsCategories,'from' => $statisticsFrom,'to' => $statisticsTo] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ecommerce.statistics-chart'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\Ecommerce\StatisticsChart::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal136890e789f0e924cbac157c7abdf091)): ?>
<?php $attributes = $__attributesOriginal136890e789f0e924cbac157c7abdf091; ?>
<?php unset($__attributesOriginal136890e789f0e924cbac157c7abdf091); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal136890e789f0e924cbac157c7abdf091)): ?>
<?php $component = $__componentOriginal136890e789f0e924cbac157c7abdf091; ?>
<?php unset($__componentOriginal136890e789f0e924cbac157c7abdf091); ?>
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
        <?php if (isset($component)) { $__componentOriginalff4232d15220e4e44d6d3b5813985d2d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalff4232d15220e4e44d6d3b5813985d2d = $attributes; } ?>
<?php $component = App\View\Components\Ecommerce\MonthlyTarget::resolve(['progressPercent' => $monthlyTargetProgressPercent,'deltaPercent' => $monthlyTargetDeltaPercent,'deltaUp' => $monthlyTargetDeltaUp,'targetAmount' => $monthlyTargetAmount,'revenueAmount' => $monthlyRevenueAmount,'todayAmount' => $todayRevenueAmount] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ecommerce.monthly-target'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\Ecommerce\MonthlyTarget::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalff4232d15220e4e44d6d3b5813985d2d)): ?>
<?php $attributes = $__attributesOriginalff4232d15220e4e44d6d3b5813985d2d; ?>
<?php unset($__attributesOriginalff4232d15220e4e44d6d3b5813985d2d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalff4232d15220e4e44d6d3b5813985d2d)): ?>
<?php $component = $__componentOriginalff4232d15220e4e44d6d3b5813985d2d; ?>
<?php unset($__componentOriginalff4232d15220e4e44d6d3b5813985d2d); ?>
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
<?php /**PATH /Users/bagusws/Documents/GitHub/kasir-v1/resources/views/livewire/dashboard-page.blade.php ENDPATH**/ ?>