<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="space-y-2">
            <h2 class="text-xl font-bold text-gray-800 dark:text-white/90">Peran & Hak Akses</h2>
            <div class="inline-flex items-center rounded-xl border border-gray-200 bg-white p-1 dark:border-gray-800 dark:bg-gray-900">
                <button type="button" wire:click="setTab('list')" class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                    'rounded-lg px-3 py-2 text-sm font-semibold transition',
                    'bg-brand-500 text-white' => ($tab ?? 'list') === 'list',
                    'text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-white/[0.03]' => ($tab ?? 'list') !== 'list',
                ]); ?>">
                    Daftar Peran
                </button>
                <button type="button" wire:click="setTab('guide')" class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                    'rounded-lg px-3 py-2 text-sm font-semibold transition',
                    'bg-brand-500 text-white' => ($tab ?? 'list') === 'guide',
                    'text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-white/[0.03]' => ($tab ?? 'list') !== 'guide',
                ]); ?>">
                    Panduan Peran
                </button>
                <button type="button" wire:click="setTab('permissions')" class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                    'rounded-lg px-3 py-2 text-sm font-semibold transition',
                    'bg-brand-500 text-white' => ($tab ?? 'list') === 'permissions',
                    'text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-white/[0.03]' => ($tab ?? 'list') !== 'permissions',
                ]); ?>">
                    Panduan Hak Akses
                </button>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('roles.manage')): ?>
                <a href="<?php echo e(route('roles.create')); ?>" wire:navigate class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium text-white transition">
                    Buat Peran Baru
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(($tab ?? 'list') === 'permissions'): ?>
        <?php
            $term = trim((string) ($permissionSearch ?? ''));
            $guides = (array) config('rbac_guide.permissions', []);

            $filtered = collect($permissions ?? [])
                ->filter(fn ($p) => $p && trim((string) ($p->name ?? '')) !== '')
                ->filter(function ($p) use ($term, $guides) {
                    $name = (string) $p->name;
                    $label = \App\Helpers\RbacLabelHelper::permission($name);
                    $summary = (string) (($guides[$name]['summary'] ?? '') ?: '');

                    if ($term === '') {
                        return true;
                    }

                    $hay = mb_strtolower($name.' '.$label.' '.$summary);
                    $needle = mb_strtolower($term);

                    return str_contains($hay, $needle);
                })
                ->values();

            $grouped = $filtered->groupBy(function ($p) {
                $name = (string) $p->name;
                $parts = explode('.', $name);

                return (string) ($parts[0] ?? 'other');
            })->sortKeys();
        ?>

        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                <div class="grid gap-3 lg:grid-cols-2 lg:items-center">
                    <div>
                        <div class="text-sm font-semibold text-gray-800 dark:text-white/90">Panduan Hak Akses</div>
                        <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">Daftar hak akses ditampilkan di bawah. Gunakan pencarian jika ingin mempersempit hasil.</div>
                    </div>
                    <div class="relative">
                        <span class="absolute top-1/2 left-4 -translate-y-1/2 text-gray-500 dark:text-gray-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </span>
                        <input wire:model.live.debounce.300ms="permissionSearch" type="text" placeholder="Cari hak akses (contoh: pos, void, inventaris)..." class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent py-2.5 pr-4 pl-11 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                    </div>
                </div>

                <div class="mt-4 grid gap-x-4 gap-y-2 text-xs sm:flex sm:flex-wrap">
                    <div class="flex items-center gap-2">
                        <span class="rounded-full bg-error-50 px-2.5 py-1 font-medium text-error-600 dark:bg-error-500/10 dark:text-error-500">Data sensitif</span>
                        <span class="text-gray-500 dark:text-gray-400">= data pribadi pelanggan/member (mis. nomor telepon)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="rounded-full bg-warning-50 px-2.5 py-1 font-medium text-warning-700 dark:bg-warning-500/10 dark:text-warning-400">Finansial</span>
                        <span class="text-gray-500 dark:text-gray-400">= berdampak pada nominal transaksi/stok</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="rounded-full bg-gray-100 px-2.5 py-1 font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">Sistem</span>
                        <span class="text-gray-500 dark:text-gray-400">= pengaturan & kontrol akses</span>
                    </div>
                </div>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($grouped->isNotEmpty()): ?>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $grouped; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $groupKey => $items): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                            <a href="#perm-group-<?php echo e($groupKey); ?>" class="rounded-full border border-gray-200 bg-white px-3 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                                <?php echo e(\App\Helpers\RbacLabelHelper::permissionGroup((string) $groupKey)); ?>

                            </a>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            <div class="p-5 space-y-6">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $grouped; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $groupKey => $items): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                    <div class="space-y-3">
                        <div id="perm-group-<?php echo e($groupKey); ?>" class="flex items-center justify-between scroll-mt-28">
                            <div class="text-sm font-semibold text-gray-800 dark:text-white/90">
                                <?php echo e(\App\Helpers\RbacLabelHelper::permissionGroup((string) $groupKey)); ?>

                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400"><?php echo e($items->count()); ?> hak akses</div>
                        </div>

                        <div class="space-y-2">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $perm): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                                <?php
                                    $name = (string) $perm->name;
                                    $guide = (array) ($guides[$name] ?? []);
                                    $risk = (array) ($guide['risk'] ?? []);
                                    $grants = (array) ($guide['grants'] ?? []);
                                    $notGrants = (array) ($guide['not_grants'] ?? []);
                                    $areas = (array) ($guide['affected_areas'] ?? []);
                                    $related = (array) ($guide['related_permissions'] ?? []);
                                ?>
                                <details class="group rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900/30">
                                    <summary class="flex cursor-pointer list-none items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <div class="text-sm font-semibold text-gray-800 dark:text-white/90">
                                                <?php echo e(\App\Helpers\RbacLabelHelper::permission($name)); ?>

                                            </div>
                                        </div>
                                        <div class="flex shrink-0 flex-col items-end gap-1">
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if((bool) ($risk['sensitive_data'] ?? false)): ?>
                                                <span class="rounded-full bg-error-50 px-2 py-0.5 text-xs font-medium text-error-600 dark:bg-error-500/10 dark:text-error-500">Data sensitif</span>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if((bool) ($risk['financial_risk'] ?? false)): ?>
                                                <span class="rounded-full bg-warning-50 px-2 py-0.5 text-xs font-medium text-warning-700 dark:bg-warning-500/10 dark:text-warning-400">Finansial</span>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if((bool) ($risk['system_risk'] ?? false)): ?>
                                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">Sistem</span>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </div>
                                    </summary>

                                    <div class="mt-4 grid gap-4 lg:grid-cols-2">
                                        <div class="space-y-2">
                                            <div class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Ringkasan</div>
                                            <div class="text-sm text-gray-700 dark:text-gray-300"><?php echo e((string) ($guide['summary'] ?? '-')); ?></div>
                                        </div>

                                        <div class="space-y-2">
                                            <div class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Area Terdampak</div>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($areas !== []): ?>
                                                <ul class="space-y-1 text-sm text-gray-700 dark:text-gray-300">
                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $areas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                                                        <li>• <?php echo e($a); ?></li>
                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                                </ul>
                                            <?php else: ?>
                                                <div class="text-sm text-gray-700 dark:text-gray-300">-</div>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </div>

                                        <div class="space-y-2">
                                            <div class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Yang Bisa Dilakukan</div>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($grants !== []): ?>
                                                <ul class="space-y-1 text-sm text-gray-700 dark:text-gray-300">
                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $grants; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $g): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                                                        <li>• <?php echo e($g); ?></li>
                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                                </ul>
                                            <?php else: ?>
                                                <div class="text-sm text-gray-700 dark:text-gray-300">-</div>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </div>

                                        <div class="space-y-2">
                                            <div class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Yang Tidak Diberikan</div>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($notGrants !== []): ?>
                                                <ul class="space-y-1 text-sm text-gray-700 dark:text-gray-300">
                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $notGrants; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ng): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                                                        <li>• <?php echo e($ng); ?></li>
                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                                </ul>
                                            <?php else: ?>
                                                <div class="text-sm text-gray-700 dark:text-gray-300">-</div>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </div>

                                        <div class="space-y-2 lg:col-span-2">
                                            <div class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Permission Terkait</div>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($related !== []): ?>
                                                <div class="flex flex-wrap gap-2">
                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $related; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                                                        <span class="rounded-full border border-gray-200 bg-white px-2.5 py-1 text-xs font-medium text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                                                            <?php echo e(\App\Helpers\RbacLabelHelper::permission((string) $r)); ?>

                                                        </span>
                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="text-sm text-gray-700 dark:text-gray-300">-</div>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </div>
                                    </div>
                                </details>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </div>
                    </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    <div class="rounded-2xl border border-gray-200 bg-gray-50 p-6 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-400">
                        Tidak ada hak akses yang cocok dengan pencarian.
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>

    <?php elseif(($tab ?? 'list') === 'guide'): ?>
        <?php
            $approvalPermissions = ['transactions.void.approve', 'transactions.refund.approve'];
        ?>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="grid gap-6 lg:grid-cols-3">
                <div class="lg:col-span-1 space-y-3">
                    <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Prinsip desain peran</h3>
                    <div class="space-y-3 text-sm text-gray-600 dark:text-gray-400">
                        <div>Peran adalah kumpulan hak akses. Nama peran bisa disesuaikan dengan SOP bisnis Anda.</div>
                        <ol class="space-y-2">
                            <li>1) Tentukan jobdesk (kasir, inventory, manajer, akuntansi).</li>
                            <li>2) Beri akses minimal dulu, tambah bertahap saat dibutuhkan.</li>
                            <li>3) Pisahkan akses sensitif: Data pribadi (PII), approval void/refund, dan pengaturan sistem.</li>
                            <li>4) Uji role dengan akun dummy: buka menu, coba aksi penting, pastikan sesuai SOP.</li>
                        </ol>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-300">
                            Untuk detail dampak setiap hak akses, gunakan tab <span class="font-semibold">Panduan Hak Akses</span>.
                        </div>
                    </div>
                    <div class="rounded-xl border border-warning-200 bg-warning-50 p-4 text-sm text-warning-700 dark:border-warning-500/20 dark:bg-warning-500/10 dark:text-warning-400">
                        Peran yang punya akses persetujuan pembatalan/refund sebaiknya menggunakan akun dengan PIN Manager dan pengguna terlatih.
                    </div>
                </div>
                <div class="lg:col-span-2">
                    <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Peran di sistem Anda</h3>
                    <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">Ringkasan di bawah dihitung dari hak akses yang dimiliki peran saat ini.</div>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $guideRoles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                            <?php
                                $name = (string) $role->name;
                                $needsPin = method_exists($role, 'hasAnyPermission') ? $role->hasAnyPermission($approvalPermissions) : false;
                                $hasPii = method_exists($role, 'hasAnyPermission') ? $role->hasAnyPermission(['transactions.pii.view', 'members.pii.view']) : false;

                                $permList = method_exists($role, 'permissions') ? $role->permissions->pluck('name')->map(fn ($p) => (string) $p)->all() : [];
                                $has = fn (string $p) => in_array($p, $permList, true);
                                $hasAny = function (array $ps) use ($permList): bool {
                                    foreach ($ps as $p) {
                                        if (in_array((string) $p, $permList, true)) {
                                            return true;
                                        }
                                    }

                                    return false;
                                };

                                $capabilities = [];
                                if ($has('pos.access')) {
                                    $capabilities[] = 'Akses POS';
                                }
                                if ($hasAny(['transactions.view', 'transactions.details', 'transactions.print'])) {
                                    $capabilities[] = 'Akses transaksi (lihat/detail/cetak sesuai izin)';
                                }
                                if ($hasAny(['transactions.void', 'transactions.refund'])) {
                                    $capabilities[] = 'Koreksi transaksi (void/refund sesuai izin)';
                                }
                                if ($hasAny($approvalPermissions)) {
                                    $capabilities[] = 'Approval koreksi transaksi';
                                }
                                if ($hasAny(['members.view', 'members.create', 'members.edit', 'members.delete'])) {
                                    $capabilities[] = 'Akses member';
                                }
                                if ($hasAny(['reports.view', 'reports.sales', 'reports.performance'])) {
                                    $capabilities[] = 'Akses laporan';
                                }
                                if ($hasAny(['inventory.view', 'inventory.manage'])) {
                                    $capabilities[] = 'Akses inventory';
                                }
                                if ($hasAny(['users.view', 'users.create', 'users.edit', 'users.delete'])) {
                                    $capabilities[] = 'Manajemen pengguna';
                                }
                                if ($hasAny(['roles.view', 'roles.manage'])) {
                                    $capabilities[] = 'Manajemen peran & hak akses';
                                }
                                if ($hasAny(['settings.view', 'settings.edit'])) {
                                    $capabilities[] = 'Akses pengaturan';
                                }
                            ?>
                            <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900/30">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <div class="text-sm font-semibold text-gray-800 dark:text-white/90">
                                            <?php echo e(\App\Helpers\RbacLabelHelper::role($name)); ?>

                                        </div>
                                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            <?php echo e($role->permissions->count()); ?> hak akses
                                        </div>
                                    </div>
                                    <div class="flex flex-col items-end gap-1">
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($needsPin): ?>
                                            <span class="rounded-full bg-warning-50 px-2 py-0.5 text-xs font-medium text-warning-700 dark:bg-warning-500/10 dark:text-warning-400">Perlu PIN</span>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hasPii): ?>
                                            <span class="rounded-full bg-error-50 px-2 py-0.5 text-xs font-medium text-error-600 dark:bg-error-500/10 dark:text-error-500">Akses Data Pribadi (PII)</span>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </div>
                                </div>

                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($capabilities !== []): ?>
                                    <ul class="mt-3 space-y-1 text-sm text-gray-600 dark:text-gray-400">
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $capabilities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cap): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                                            <li>• <?php echo e($cap); ?></li>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                    </ul>
                                <?php else: ?>
                                    <p class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                                        Peran ini belum memiliki hak akses atau hanya untuk kebutuhan khusus.
                                    </p>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                <div class="relative max-w-sm">
                    <span class="absolute top-1/2 left-4 -translate-y-1/2 text-gray-500 dark:text-gray-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </span>
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari peran..." class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent py-2.5 pr-4 pl-11 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-900 dark:text-gray-400">
                        <tr>
                            <th class="px-6 py-3">Nama Peran</th>
                            <th class="px-6 py-3">Jumlah Pengguna</th>
                            <th class="px-6 py-3">Jumlah Hak Akses</th>
                            <th class="px-6 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoop($loop->index); ?><?php endif; ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                <td class="px-6 py-4 font-medium text-gray-900 dark:text-white"><?php echo e(\App\Helpers\RbacLabelHelper::role((string) $role->name)); ?></td>
                                <td class="px-6 py-4">
                                    <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-800 dark:text-gray-300">
                                        <?php echo e($role->users_count); ?>

                                    </span>
                                </td>
                                <td class="px-6 py-4"><?php echo e($role->permissions_count); ?></td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('roles.manage')): ?>
                                            <a href="<?php echo e(route('roles.edit', $role->id)); ?>" wire:navigate class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                                                Ubah
                                            </a>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($role->name !== 'owner'): ?>
                                                <button wire:click="delete(<?php echo e($role->id); ?>)"
                                                        wire:confirm="Yakin ingin menghapus peran ini? Pengguna yang memakai peran ini akan kehilangan akses."
                                                        class="rounded-lg bg-error-50 px-3 py-2 text-xs font-medium text-error-600 hover:bg-error-100 dark:bg-error-500/10 dark:text-error-500 dark:hover:bg-error-500/20">
                                                    Hapus
                                                </button>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            <tr>
                                <td colspan="4" class="px-6 py-10 text-center text-gray-500">
                                    Tidak ada data peran.
                                </td>
                            </tr>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-200 p-4 dark:border-gray-800">
                <?php echo e($roles->links('livewire.pagination.admin')); ?>

            </div>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>
<?php /**PATH /home/u592471275/domains/ciyemon.com/public_html/resources/views/livewire/roles/role-index.blade.php ENDPATH**/ ?>