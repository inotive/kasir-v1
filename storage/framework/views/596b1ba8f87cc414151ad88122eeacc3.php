<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'products',
    'categories' => [],
    'sortField' => 'created_at',
    'sortAsc' => false,
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
    'products',
    'categories' => [],
    'sortField' => 'created_at',
    'sortAsc' => false,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="flex flex-col justify-between gap-5 border-b border-gray-200 px-5 py-4 sm:flex-row sm:items-center dark:border-gray-800">
        <div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Daftar Produk</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Kelola data produk, kategori, dan ketersediaan.</p>
        </div>
        <div class="flex gap-3">
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('products.create')): ?>
                <a wire:navigate href="<?php echo e(route('products.create')); ?>" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center gap-2 rounded-lg px-4 py-3 text-sm font-medium text-white transition">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                        <path d="M5 10.0002H15.0006M10.0002 5V15.0006" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    Tambah Produk
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
        <div class="flex flex-col gap-3 sm:flex-row sm:justify-between">
            <div class="relative flex-1 sm:flex-auto">
                <span class="absolute top-1/2 left-4 -translate-y-1/2 text-gray-500 dark:text-gray-400">
                    <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M3.04199 9.37363C3.04199 5.87693 5.87735 3.04199 9.37533 3.04199C12.8733 3.04199 15.7087 5.87693 15.7087 9.37363C15.7087 12.8703 12.8733 15.7053 9.37533 15.7053C5.87735 15.7053 3.04199 12.8703 3.04199 9.37363ZM9.37533 1.54199C5.04926 1.54199 1.54199 5.04817 1.54199 9.37363C1.54199 13.6991 5.04926 17.2053 9.37533 17.2053C11.2676 17.2053 13.0032 16.5344 14.3572 15.4176L17.1773 18.238C17.4702 18.5309 17.945 18.5309 18.2379 18.238C18.5308 17.9451 18.5309 17.4703 18.238 17.1773L15.4182 14.3573C16.5367 13.0033 17.2087 11.2669 17.2087 9.37363C17.2087 5.04817 13.7014 1.54199 9.37533 1.54199Z" fill="" />
                    </svg>
                </span>
                <input wire:model.live.debounce.400ms="search" type="text" placeholder="Cari produk..." class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent py-2.5 pr-4 pl-11 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden sm:w-[320px] sm:min-w-[320px] dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
            </div>

            <div class="flex gap-3">
                <div class="relative">
                    <select wire:model.live="categoryId" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-white bg-none px-4 py-2.5 pr-11 text-sm text-gray-700 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:placeholder:text-white/30">
                        <option value="">Semua Kategori</option>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                            <option value="<?php echo e($category->id); ?>"><?php echo e($category->name); ?></option>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </select>
                    <span class="pointer-events-none absolute top-1/2 right-4 -translate-y-1/2 text-gray-700 dark:text-gray-400">
                        <svg class="stroke-current" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M4.79175 7.396L10.0001 12.6043L15.2084 7.396" stroke="" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="custom-scrollbar overflow-x-auto">
        <table class="w-full table-auto">
            <thead>
                <tr class="border-b border-gray-200 dark:divide-gray-800 dark:border-gray-800">
                    <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">
                        <button type="button" wire:click="sortBy('name')" class="flex items-center gap-2">
                            Produk
                            <span class="flex flex-col gap-0.5">
                                <svg width="8" height="5" viewBox="0 0 8 5" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M4.40962 0.585167C4.21057 0.300808 3.78943 0.300807 3.59038 0.585166L1.05071 4.21327C0.81874 4.54466 1.05582 5 1.46033 5H6.53967C6.94418 5 7.18126 4.54466 6.94929 4.21327L4.40962 0.585167Z" fill="currentColor" class="<?php echo e($sortField === 'name' && $sortAsc ? 'text-gray-500 dark:text-gray-400' : 'text-gray-300 dark:text-gray-400/50'); ?>" />
                                </svg>
                                <svg width="8" height="5" viewBox="0 0 8 5" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M4.40962 4.41483C4.21057 4.69919 3.78943 4.69919 3.59038 4.41483L1.05071 0.786732C0.81874 0.455343 1.05582 0 1.46033 0H6.53967C6.94418 0 7.18126 0.455342 6.94929 0.786731L4.40962 4.41483Z" fill="currentColor" class="<?php echo e($sortField === 'name' && ! $sortAsc ? 'text-gray-500 dark:text-gray-400' : 'text-gray-300 dark:text-gray-400/50'); ?>" />
                                </svg>
                            </span>
                        </button>
                    </th>
                    <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 hidden sm:table-cell">Kategori</th>
                    <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Harga</th>
                    <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">HPP</th>
                    <th class="px-5 py-4 text-center text-xs font-medium text-gray-500 dark:text-gray-400">Status</th>
                    <th class="px-5 py-4 text-center text-xs font-medium text-gray-500 dark:text-gray-400">Promo</th>
                    <th class="px-5 py-4 text-center text-xs font-medium text-gray-500 dark:text-gray-400">Favorit</th>
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['products.edit', 'products.delete'])): ?>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Aksi</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                    <?php
                        $image = (string) ($product->image ?? '');
                        $imageUrl = $image === '' ? asset('/images/product/product-01.jpg') : (str_starts_with($image, '/') || str_starts_with($image, 'http') ? asset($image) : asset('storage/'.$image));
                    ?>
                    <tr>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="h-11 w-11 overflow-hidden rounded-lg bg-gray-100 dark:bg-gray-800">
                                    <img src="<?php echo e($imageUrl); ?>" alt="<?php echo e($product->name); ?>" class="h-full w-full object-cover" />
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-gray-800 dark:text-white/90"><?php echo e($product->name); ?></p>
                                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400 sm:hidden"><?php echo e($product->category?->name ?? '-'); ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4 hidden sm:table-cell">
                            <p class="text-sm text-gray-500 dark:text-gray-400"><?php echo e($product->category?->name ?? '-'); ?></p>
                        </td>
                        <td class="px-5 py-4">
                            <?php
                                $priceMin = $product->variants_min_price === null ? null : (float) $product->variants_min_price;
                                $priceMax = $product->variants_max_price === null ? null : (float) $product->variants_max_price;

                                $promoPrices = [];
                                foreach (($product->variants ?? []) as $v) {
                                    $base = (float) ($v->price ?? 0);
                                    $percent = (int) ($v->percent ?? 0);
                                    $after = (float) ($v->price_afterdiscount ?? 0);

                                    if ($base <= 0) {
                                        continue;
                                    }

                                    $final = $base;
                                    if ($percent > 0) {
                                        $final = max(0, round($base - ($base * ($percent / 100))));
                                    } elseif ($after > 0 && $after < $base) {
                                        $final = $after;
                                    }

                                    if ($final < $base) {
                                        $promoPrices[] = $final;
                                    }
                                }

                                $promoMin = $promoPrices === [] ? null : (float) min($promoPrices);
                                $promoMax = $promoPrices === [] ? null : (float) max($promoPrices);
                            ?>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($priceMin === null || $priceMax === null): ?>
                                <p class="text-sm text-gray-500 dark:text-gray-400">-</p>
                            <?php elseif(abs($priceMin - $priceMax) < 0.005): ?>
                                <p class="text-sm font-medium text-gray-800 dark:text-white/90">Rp <?php echo e(number_format($priceMin, 0, ',', '.')); ?></p>
                            <?php else: ?>
                                <p class="text-sm font-medium text-gray-800 dark:text-white/90">Rp <?php echo e(number_format($priceMin, 0, ',', '.')); ?> - <?php echo e(number_format($priceMax, 0, ',', '.')); ?></p>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($promoMin !== null && $promoMax !== null): ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(abs($promoMin - $promoMax) < 0.005): ?>
                                    <p class="mt-1 text-xs font-semibold text-success-700 dark:text-success-400">Promo Rp <?php echo e(number_format($promoMin, 0, ',', '.')); ?></p>
                                <?php else: ?>
                                    <p class="mt-1 text-xs font-semibold text-success-700 dark:text-success-400">Promo Rp <?php echo e(number_format($promoMin, 0, ',', '.')); ?> - <?php echo e(number_format($promoMax, 0, ',', '.')); ?></p>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </td>
                        <td class="px-5 py-4">
                            <?php
                                $hppMin = $product->variants_min_hpp === null ? null : (float) $product->variants_min_hpp;
                                $hppMax = $product->variants_max_hpp === null ? null : (float) $product->variants_max_hpp;
                            ?>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hppMin === null || $hppMax === null): ?>
                                <p class="text-sm text-gray-500 dark:text-gray-400">-</p>
                            <?php elseif(abs($hppMin - $hppMax) < 0.005): ?>
                                <p class="text-sm font-medium text-gray-800 dark:text-white/90">Rp <?php echo e(number_format($hppMin, 0, ',', '.')); ?></p>
                            <?php else: ?>
                                <p class="text-sm font-medium text-gray-800 dark:text-white/90">Rp <?php echo e(number_format($hppMin, 0, ',', '.')); ?> - <?php echo e(number_format($hppMax, 0, ',', '.')); ?></p>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(($product->variants_with_recipes_count ?? 0) > 0 && ($hppMin ?? 0) <= 0.005 && ($hppMax ?? 0) <= 0.005): ?>
                                <p class="mt-1 text-xs font-medium text-warning-700 dark:text-warning-400">HPP masih 0. Cek HPP/Unit bahan di Inventory → Bahan Baku</p>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <p class="text-xs text-gray-500 dark:text-gray-400"><?php echo e((int) ($product->variants_count ?? 0)); ?> varian</p>
                        </td>
                        <td class="px-5 py-4 text-center">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($product->is_available): ?>
                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-400" title="Tersedia">
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.25 7.29a1 1 0 0 1-1.42.003L3.29 9.24a1 1 0 1 1 1.42-1.4l4.04 4.1 6.54-6.57a1 1 0 0 1 1.414-.006Z" clip-rule="evenodd"/>
                                    </svg>
                                </span>
                            <?php else: ?>
                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-gray-100 text-gray-500 dark:bg-white/[0.06] dark:text-white/50" title="Tidak tersedia">
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 0 1 1.414 0L10 8.586l4.293-4.293a1 1 0 1 1 1.414 1.414L11.414 10l4.293 4.293a1 1 0 0 1-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 0 1-1.414-1.414L8.586 10 4.293 5.707a1 1 0 0 1 0-1.414Z" clip-rule="evenodd"/>
                                    </svg>
                                </span>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </td>
                        <td class="px-5 py-4 text-center">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($product->is_promo): ?>
                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-400" title="Promo">
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.25 7.29a1 1 0 0 1-1.42.003L3.29 9.24a1 1 0 1 1 1.42-1.4l4.04 4.1 6.54-6.57a1 1 0 0 1 1.414-.006Z" clip-rule="evenodd"/>
                                    </svg>
                                </span>
                            <?php else: ?>
                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-gray-100 text-gray-500 dark:bg-white/[0.06] dark:text-white/50" title="Tidak promo">
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 0 1 1.414 0L10 8.586l4.293-4.293a1 1 0 1 1 1.414 1.414L11.414 10l4.293 4.293a1 1 0 0 1-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 0 1-1.414-1.414L8.586 10 4.293 5.707a1 1 0 0 1 0-1.414Z" clip-rule="evenodd"/>
                                    </svg>
                                </span>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </td>
                        <td class="px-5 py-4 text-center">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($product->is_favorite): ?>
                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-400" title="Favorit">
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.25 7.29a1 1 0 0 1-1.42.003L3.29 9.24a1 1 0 1 1 1.42-1.4l4.04 4.1 6.54-6.57a1 1 0 0 1 1.414-.006Z" clip-rule="evenodd"/>
                                    </svg>
                                </span>
                            <?php else: ?>
                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-gray-100 text-gray-500 dark:bg-white/[0.06] dark:text-white/50" title="Bukan favorit">
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 0 1 1.414 0L10 8.586l4.293-4.293a1 1 0 1 1 1.414 1.414L11.414 10l4.293 4.293a1 1 0 0 1-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 0 1-1.414-1.414L8.586 10 4.293 5.707a1 1 0 0 1 0-1.414Z" clip-rule="evenodd"/>
                                    </svg>
                                </span>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </td>
                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['products.edit', 'products.delete'])): ?>
                            <td class="px-5 py-4 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('products.edit')): ?>
                                        <a wire:navigate href="<?php echo e(route('products.edit', $product)); ?>" class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                                            Edit
                                        </a>
                                    <?php endif; ?>
                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('products.delete')): ?>
                                        <button type="button" onclick="confirm('Hapus produk ini?') || event.stopImmediatePropagation()" wire:click="deleteProduct(<?php echo e($product->id); ?>)" class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                                            Hapus
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    <tr>
                        <td colspan="<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['products.edit', 'products.delete'])): ?> 8 <?php else: ?> 7 <?php endif; ?>" class="px-5 py-10">
                            <p class="text-center text-sm text-gray-500 dark:text-gray-400">Produk tidak ditemukan.</p>
                        </td>
                    </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="border-t border-gray-200 px-5 py-4 dark:border-gray-800">
        <?php echo e($products->links('livewire.pagination.admin')); ?>

    </div>
</div>
<?php /**PATH /home/u592471275/domains/ciyemon.com/public_html/resources/views/components/ecommerce/product-table.blade.php ENDPATH**/ ?>