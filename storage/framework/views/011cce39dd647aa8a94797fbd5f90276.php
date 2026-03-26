<div class="space-y-6">
    <?php if (isset($component)) { $__componentOriginald99009b99056f01c1483975b86b51c14 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald99009b99056f01c1483975b86b51c14 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ecommerce.product-form','data' => ['categories' => $categories,'printerSources' => $printerSources,'ingredients' => $ingredients,'componentVariants' => $componentVariants,'componentProducts' => $componentProducts,'ingredientUnits' => $ingredientUnits,'ingredientCosts' => $ingredientCosts,'productId' => $productId,'existingImage' => $existingImage,'image' => $image,'isPackage' => $isPackage,'packageType' => $packageType,'packageItems' => $packageItems,'complexPackageItems' => $complexPackageItems,'variants' => $variants,'variantRecipes' => $variantRecipes,'hppByVariantKey' => $hppByVariantKey]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ecommerce.product-form'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['categories' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($categories),'printer-sources' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($printerSources),'ingredients' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($ingredients),'component-variants' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($componentVariants),'component-products' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($componentProducts),'ingredient-units' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($ingredientUnits),'ingredient-costs' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($ingredientCosts),'product-id' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($productId),'existing-image' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($existingImage),'image' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($image),'is-package' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($isPackage),'package-type' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($packageType),'package-items' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($packageItems),'complex-package-items' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($complexPackageItems),'variants' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($variants),'variant-recipes' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($variantRecipes),'hpp-by-variant-key' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($hppByVariantKey)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald99009b99056f01c1483975b86b51c14)): ?>
<?php $attributes = $__attributesOriginald99009b99056f01c1483975b86b51c14; ?>
<?php unset($__attributesOriginald99009b99056f01c1483975b86b51c14); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald99009b99056f01c1483975b86b51c14)): ?>
<?php $component = $__componentOriginald99009b99056f01c1483975b86b51c14; ?>
<?php unset($__componentOriginald99009b99056f01c1483975b86b51c14); ?>
<?php endif; ?>
</div>
<?php /**PATH /home/u592471275/domains/ciyemon.com/public_html/resources/views/livewire/products/product-form-page.blade.php ENDPATH**/ ?>