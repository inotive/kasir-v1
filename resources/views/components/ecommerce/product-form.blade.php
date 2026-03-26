@props([
    'categories' => [],
    'printerSources' => [],
    'ingredients' => [],
    'componentVariants' => [],
    'componentProducts' => [],
    'ingredientUnits' => [],
    'ingredientCosts' => [],
    'productId' => null,
    'existingImage' => '',
    'image' => null,
    'isPackage' => false,
    'packageType' => 'simple',
    'packageItems' => [],
    'complexPackageItems' => [],
    'variants' => [],
    'variantRecipes' => [],
    'hppByVariantKey' => [],
])

@php
    $isEdit = (bool) ($productId ?? null);
    $currentImage = (string) ($existingImage ?? '');
    $currentImageUrl = $currentImage === '' ? '' : (str_starts_with($currentImage, '/') || str_starts_with($currentImage, 'http') ? asset($currentImage) : asset('storage/'.$currentImage));
@endphp

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ $isEdit ? 'Edit Produk' : 'Tambah Produk' }}</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Isi detail produk dan simpan perubahan.</p>
        </div>
        <a wire:navigate href="{{ route('products.index') }}" class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">Kembali</a>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
                <h3 class="text-lg font-medium text-gray-800 dark:text-white">Deskripsi Produk</h3>
            </div>
            <div class="p-4 sm:p-6">
                <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Nama Produk</label>
                        <input wire:model.live="name" type="text" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" placeholder="Masukkan nama produk" />
                        <x-common.input-error for="name" />
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Kategori</label>
                        <div class="relative z-20 bg-transparent">
                            <select wire:model.live="categoryId" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 pr-11 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
                                <option value="">Pilih Kategori</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                            <span class="pointer-events-none absolute top-1/2 right-4 z-30 -translate-y-1/2 text-gray-700 dark:text-gray-400">
                                <svg class="stroke-current" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M4.79175 7.396L10.0001 12.6043L15.2084 7.396" stroke="" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                        </div>
                        <x-common.input-error for="categoryId" />
                    </div>

                    @if (! $isPackage)
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Printer Dapur</label>
                            <div class="relative z-20 bg-transparent">
                                <select wire:model.live="printerSourceId" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 pr-11 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
                                    <option value="">Pilih Printer Dapur</option>
                                    @foreach ($printerSources as $source)
                                        <option value="{{ $source->id }}">{{ $source->name }}</option>
                                    @endforeach
                                </select>
                                <span class="pointer-events-none absolute top-1/2 right-4 z-30 -translate-y-1/2 text-gray-700 dark:text-gray-400">
                                    <svg class="stroke-current" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M4.79175 7.396L10.0001 12.6043L15.2084 7.396" stroke="" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </span>
                            </div>
                            <x-common.input-error for="printerSourceId" />
                        </div>
                    @else
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Printer Dapur</label>
                            <div class="h-11 w-full rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                                Mengikuti sumber printer komponen paket
                            </div>
                        </div>
                    @endif

                    <div class="md:col-span-3">
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Deskripsi</label>
                        <textarea wire:model.live="description" rows="5" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-3 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" placeholder="Masukkan deskripsi produk"></textarea>
                        <x-common.input-error for="description" />
                    </div>

                    <div class="md:col-span-3">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                            <label class="relative flex items-center justify-between gap-4 overflow-hidden rounded-lg border border-gray-200 bg-white px-4 py-3 shadow-theme-xs dark:border-gray-700 dark:bg-gray-900">
                                <input wire:model.live="isAvailable" type="checkbox" class="peer sr-only" />
                                <span class="pointer-events-none absolute inset-0 rounded-lg bg-brand-50 opacity-0 transition peer-checked:opacity-100 dark:bg-brand-500/10"></span>
                                <span class="pointer-events-none absolute inset-0 rounded-lg ring-1 ring-transparent transition peer-checked:ring-brand-200 dark:peer-checked:ring-brand-500/20"></span>
                                <span class="relative z-10 text-sm font-medium text-gray-700 transition peer-checked:text-brand-700 dark:text-gray-300 dark:peer-checked:text-brand-200">Tersedia</span>
                                <span class="relative z-10 inline-flex h-6 w-11 items-center rounded-full bg-gray-200 transition peer-checked:bg-brand-500 dark:bg-gray-700 dark:peer-checked:bg-brand-500 after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow-sm after:transition peer-checked:after:translate-x-5"></span>
                            </label>
                            <label class="relative flex items-center justify-between gap-4 overflow-hidden rounded-lg border border-gray-200 bg-white px-4 py-3 shadow-theme-xs dark:border-gray-700 dark:bg-gray-900">
                                <input wire:model.live="isPromo" type="checkbox" class="peer sr-only" />
                                <span class="pointer-events-none absolute inset-0 rounded-lg bg-brand-50 opacity-0 transition peer-checked:opacity-100 dark:bg-brand-500/10"></span>
                                <span class="pointer-events-none absolute inset-0 rounded-lg ring-1 ring-transparent transition peer-checked:ring-brand-200 dark:peer-checked:ring-brand-500/20"></span>
                                <span class="relative z-10 text-sm font-medium text-gray-700 transition peer-checked:text-brand-700 dark:text-gray-300 dark:peer-checked:text-brand-200">Promo</span>
                                <span class="relative z-10 inline-flex h-6 w-11 items-center rounded-full bg-gray-200 transition peer-checked:bg-brand-500 dark:bg-gray-700  dark:peer-checked:bg-brand-500  after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow-sm after:transition peer-checked:after:translate-x-5"></span>
                            </label>
                            <label class="relative flex items-center justify-between gap-4 overflow-hidden rounded-lg border border-gray-200 bg-white px-4 py-3 shadow-theme-xs dark:border-gray-700 dark:bg-gray-900">
                                <input wire:model.live="isFavorite" type="checkbox" class="peer sr-only" />
                                <span class="pointer-events-none absolute inset-0 rounded-lg bg-brand-50 opacity-0 transition peer-checked:opacity-100 dark:bg-brand-500/10"></span>
                                <span class="pointer-events-none absolute inset-0 rounded-lg ring-1 ring-transparent transition peer-checked:ring-brand-200 dark:peer-checked:ring-brand-500/20"></span>
                                <span class="relative z-10 text-sm font-medium text-gray-700 transition peer-checked:text-brand-700 dark:text-gray-300 dark:peer-checked:text-brand-200">Favorit</span>
                                <span class="relative z-10 inline-flex h-6 w-11 items-center rounded-full bg-gray-200 transition peer-checked:bg-brand-500 dark:bg-gray-700  dark:peer-checked:bg-brand-500  after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow-sm after:transition peer-checked:after:translate-x-5"></span>
                            </label>
                            <label class="relative flex items-center justify-between gap-4 overflow-hidden rounded-lg border border-gray-200 bg-white px-4 py-3 shadow-theme-xs dark:border-gray-700 dark:bg-gray-900">
                                <input wire:model.live="isPackage" type="checkbox" class="peer sr-only" />
                                <span class="pointer-events-none absolute inset-0 rounded-lg bg-brand-50 opacity-0 transition peer-checked:opacity-100 dark:bg-brand-500/10"></span>
                                <span class="pointer-events-none absolute inset-0 rounded-lg ring-1 ring-transparent transition peer-checked:ring-brand-200 dark:peer-checked:ring-brand-500/20"></span>
                                <span class="relative z-10 text-sm font-medium text-gray-700 transition peer-checked:text-brand-700 dark:text-gray-300 dark:peer-checked:text-brand-200">Paket</span>
                                <span class="relative z-10 inline-flex h-6 w-11 items-center rounded-full bg-gray-200 transition peer-checked:bg-brand-500 dark:bg-gray-700  dark:peer-checked:bg-brand-500  after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow-sm after:transition peer-checked:after:translate-x-5"></span>
                            </label>
                        </div>
                        <x-common.input-error for="isAvailable" />
                        <x-common.input-error for="isPromo" />
                        <x-common.input-error for="isFavorite" />
                        <x-common.input-error for="isPackage" />
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-800">
                <div>
                    <h3 class="text-lg font-medium text-gray-800 dark:text-white">Isi Paket</h3>
                    @if ($isPackage && $packageType === 'complex')
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Pilih produk yang termasuk di dalam paket. Varian dipilih saat input di POS.</p>
                    @else
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Pilih varian menu yang termasuk di dalam paket.</p>
                    @endif
                </div>
                @if ($packageType === 'complex')
                    <button
                        type="button"
                        wire:click="addComplexPackageItem"
                        class="shadow-theme-xs inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-brand-600"
                        @disabled(! $isPackage)
                    >
                        Tambah Item
                    </button>
                @else
                    <button
                        type="button"
                        wire:click="addPackageItem"
                        class="shadow-theme-xs inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-brand-600"
                        @disabled(! $isPackage)
                    >
                        Tambah Item
                    </button>
                @endif
            </div>

            <div class="p-4 sm:p-6 space-y-4">
                <x-common.input-error for="packageItems" class="text-xs text-error-600" />
                <x-common.input-error for="complexPackageItems" class="text-xs text-error-600" />

                @if ($isPackage)
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Tipe Paket</label>
                            <div class="relative z-20 bg-transparent">
                                <select
                                    wire:model.live="packageType"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 pr-11 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                >
                                    <option value="simple">Paket Simpel</option>
                                    <option value="complex">Paket Kompleks</option>
                                </select>
                                <span class="pointer-events-none absolute top-1/2 right-4 z-30 -translate-y-1/2 text-gray-700 dark:text-gray-400">
                                    <svg class="stroke-current" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M4.79175 7.396L10.0001 12.6043L15.2084 7.396" stroke="" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </span>
                            </div>
                            <x-common.input-error for="packageType" />
                        </div>
                    </div>

                    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800">
                        <table class="min-w-full">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-900">
                                    @if ($packageType === 'complex')
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400">Produk Komponen</th>
                                    @else
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400">Varian Komponen</th>
                                    @endif
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400">Qty</th>
                                    @if ($packageType === 'complex')
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400">Pecah di POS</th>
                                    @endif
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-400">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                                @if ($packageType === 'complex')
                                    @foreach ($complexPackageItems as $index => $row)
                                        @php
                                            $rowKey = (string) ($row['key'] ?? $index);
                                        @endphp
                                        <tr wire:key="complex-package-item-row-{{ $rowKey }}" class="bg-white dark:bg-gray-950/30">
                                            <td class="px-4 py-3 align-top">
                                                <select
                                                    wire:model.live="complexPackageItems.{{ $index }}.component_product_id"
                                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-10 w-full appearance-none rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                                >
                                                    <option value="">Pilih produk</option>
                                                    @foreach ($componentProducts as $product)
                                                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                                                    @endforeach
                                                </select>
                                                <x-common.input-error :for="'complexPackageItems.'.$index.'.component_product_id'" />
                                            </td>
                                            <td class="px-4 py-3 align-top">
                                                <input
                                                    wire:model.live="complexPackageItems.{{ $index }}.quantity"
                                                    type="number"
                                                    min="1"
                                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                                    placeholder="1"
                                                />
                                                <x-common.input-error :for="'complexPackageItems.'.$index.'.quantity'" />
                                            </td>
                                            <td class="px-4 py-3 align-top">
                                                <label class="inline-flex items-center gap-2">
                                                    <input wire:model.live="complexPackageItems.{{ $index }}.is_splitable" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900" />
                                                    <span class="text-sm text-gray-700 dark:text-gray-300">Ya</span>
                                                </label>
                                            </td>
                                            <td class="px-4 py-3 align-top text-right">
                                                <button
                                                    type="button"
                                                    wire:click="removeComplexPackageItem('{{ $rowKey }}')"
                                                    class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]"
                                                >
                                                    Hapus
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    @foreach ($packageItems as $index => $row)
                                        @php
                                            $rowKey = (string) ($row['key'] ?? $index);
                                        @endphp
                                        <tr wire:key="package-item-row-{{ $rowKey }}" class="bg-white dark:bg-gray-950/30">
                                            <td class="px-4 py-3 align-top">
                                                <select
                                                    wire:model.live="packageItems.{{ $index }}.component_variant_id"
                                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-10 w-full appearance-none rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                                >
                                                    <option value="">Pilih varian</option>
                                                    @foreach ($componentVariants as $variant)
                                                        <option value="{{ $variant->id }}">{{ $variant->product->name }} - {{ $variant->name }}</option>
                                                    @endforeach
                                                </select>
                                                <x-common.input-error :for="'packageItems.'.$index.'.component_variant_id'" />
                                            </td>
                                            <td class="px-4 py-3 align-top">
                                                <input
                                                    wire:model.live="packageItems.{{ $index }}.quantity"
                                                    type="number"
                                                    min="1"
                                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                                    placeholder="1"
                                                />
                                                <x-common.input-error :for="'packageItems.'.$index.'.quantity'" />
                                            </td>
                                            <td class="px-4 py-3 align-top text-right">
                                                <button
                                                    type="button"
                                                    wire:click="removePackageItem('{{ $rowKey }}')"
                                                    class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]"
                                                >
                                                    Hapus
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">Aktifkan opsi Paket untuk mengatur isi paket.</p>
                @endif
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-800">
                <div>
                    <h3 class="text-lg font-medium text-gray-800 dark:text-white">Varian Produk</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Tambah varian seperti Regular/Large atau Level pedas.</p>
                </div>
                <button
                    type="button"
                    wire:click="addVariant"
                    class="shadow-theme-xs inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-brand-600"
                >
                    Tambah Varian
                </button>
            </div>

            <div class="p-4 sm:p-6 space-y-4">
                <x-common.input-error for="variants" class="text-xs text-error-600" />
                <x-common.input-error for="variantRecipes" class="text-xs text-error-600" />

                <div class="hidden sm:block overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-900">
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400">Nama Varian</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400">Harga</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400">Diskon %</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400">Harga Setelah Diskon</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400">HPP</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400">Margin</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-400">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            @foreach ($variants as $index => $variant)
                                @php
                                    $price = (float) ($variant['price'] ?? 0);
                                    $percent = $variant['percent'] === null || $variant['percent'] === '' ? null : (int) $variant['percent'];
                                    $priceAfterDiscount = $percent === null ? null : round($price - ($price * ($percent / 100)), 2);
                                    $variantKey = (string) ($variant['key'] ?? $index);
                                    $hpp = (float) ($hppByVariantKey[$variantKey] ?? 0);
                                    $sellingPrice = $priceAfterDiscount === null ? $price : (float) $priceAfterDiscount;
                                    $margin = $sellingPrice - $hpp;
                                @endphp
                                <tr wire:key="variant-row-{{ $variantKey }}" class="bg-white dark:bg-gray-950/30">
                                    <td class="px-4 py-3 align-top">
                                        <input
                                            wire:model.live="variants.{{ $index }}.name"
                                            type="text"
                                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                            placeholder="Contoh: Regular"
                                        />
                                        <x-common.input-error :for="'variants.'.$index.'.name'" />
                                    </td>
                                    <td class="px-4 py-3 align-top">
                                        <input
                                                x-data="currencyInput($wire.entangle('variants.{{ $index }}.price'))"
                                                wire:model.live="variants.{{ $index }}.price"
                                                x-model="displayValue"
                                                @input="handleInput"
                                                type="text"
                                                inputmode="numeric"
                                                class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                                placeholder="0"
                                            />
                                        <x-common.input-error :for="'variants.'.$index.'.price'" />
                                    </td>
                                    <td class="px-4 py-3 align-top">
                                        <input
                                            wire:model.live="variants.{{ $index }}.percent"
                                            type="number"
                                            min="0"
                                            max="100"
                                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                            placeholder="-"
                                        />
                                        <x-common.input-error :for="'variants.'.$index.'.percent'" />
                                    </td>
                                    <td class="px-4 py-3 align-top">
                                        <div class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                                            {{ $priceAfterDiscount === null ? '-' : number_format($priceAfterDiscount, 2, ',', '.') }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 align-top">
                                        <div class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                                            Rp{{ number_format($hpp, 0, ',', '.') }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 align-top">
                                        <div class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm {{ $margin < 0 ? 'text-error-600 dark:text-error-500' : 'text-gray-700 dark:text-gray-300' }} dark:border-gray-800 dark:bg-gray-900">
                                            Rp{{ number_format($margin, 0, ',', '.') }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 align-top text-right">
                                        <button
                                            type="button"
                                            wire:click="removeVariant('{{ $variantKey }}')"
                                            class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]"
                                        >
                                            Hapus
                                        </button>
                                    </td>
                                </tr>
                                @php
                                    $recipes = (array) ($variantRecipes[$variantKey] ?? []);
                                @endphp
                                @if (! $isPackage)
                                    <tr wire:key="variant-recipes-{{ $variantKey }}" class="bg-gray-50/50 dark:bg-gray-950/10">
                                        <td colspan="7" class="px-4 py-4">
                                            <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-950/30">
                                                <div class="flex flex-col gap-2 border-b border-gray-200 px-4 py-3 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
                                                    <div>
                                                        <p class="text-sm font-semibold text-gray-800 dark:text-white/90">Bahan Varian</p>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400">Atur bahan baku untuk menghitung HPP dan pemakaian stok.</p>
                                                    </div>
                                                    <div class="text-right">
                                                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total HPP</p>
                                                        <p class="text-sm font-semibold text-gray-800 dark:text-white/90">Rp{{ number_format((float) ($hppByVariantKey[$variantKey] ?? 0), 0, ',', '.') }}</p>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400">Dibulatkan ke rupiah</p>
                                                    </div>
                                                    <button
                                                        type="button"
                                                        wire:click="addRecipe('{{ $variantKey }}')"
                                                        class="shadow-theme-xs inline-flex items-center justify-center rounded-lg bg-brand-500 px-3 py-2 text-xs font-medium text-white transition hover:bg-brand-600"
                                                    >
                                                        Tambah Bahan
                                                    </button>
                                                </div>

                                                <div class="overflow-x-auto">
                                                    <table class="min-w-full">
                                                        <thead>
                                                            <tr class="bg-gray-50 dark:bg-gray-900">
                                                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400">Bahan Baku</th>
                                                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400">Qty / Porsi</th>
                                                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-400">Aksi</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                                                            @forelse ($recipes as $recipeIndex => $recipe)
                                                                @php
                                                                    $recipeKey = (string) ($recipe['key'] ?? $recipeIndex);
                                                                    $ingredientId = (int) ($recipe['ingredient_id'] ?? 0);
                                                                    $unit = (string) ($ingredientUnits[$ingredientId] ?? '');
                                                                    $unitCost = (float) ($ingredientCosts[$ingredientId] ?? 0);
                                                                    $qty = (float) (\App\Support\Number\QuantityParser::parse($recipe['quantity'] ?? null) ?? 0);
                                                                    $lineSubtotal = $unitCost * $qty;
                                                                @endphp
                                                                <tr wire:key="variant-recipe-row-{{ $variantKey }}-{{ $recipeKey }}" class="bg-white dark:bg-gray-950/30">
                                                                    <td class="px-4 py-3 align-top">
                                                                        <select
                                                                            wire:model.live="variantRecipes.{{ $variantKey }}.{{ $recipeIndex }}.ingredient_id"
                                                                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-10 w-full appearance-none rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                                                        >
                                                                            <option value="">Pilih bahan</option>
                                                                            @foreach ($ingredients as $ingredient)
                                                                                <option value="{{ $ingredient->id }}">{{ $ingredient->name }} ({{ $ingredient->unit }})</option>
                                                                            @endforeach
                                                                        </select>
                                                                        <x-common.input-error :for="'variantRecipes.'.$variantKey.'.'.$recipeIndex.'.ingredient_id'" />
                                                                    </td>
                                                                    <td class="px-4 py-3 align-top">
                                                                        <div class="flex items-center gap-2">
                                                                            <input
                                                                                wire:model.live.debounce.200ms="variantRecipes.{{ $variantKey }}.{{ $recipeIndex }}.quantity"
                                                                                type="text"
                                                                                inputmode="decimal"
                                                                                class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                                                                placeholder="0"
                                                                            />
                                                                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $unit }}</span>
                                                                        </div>
                                                                        <div class="mt-1 space-y-0.5 text-xs text-gray-500 dark:text-gray-400">
                                                                            <p>HPP/Unit: Rp{{ number_format($unitCost, 0, ',', '.') }}</p>
                                                                            <p>Subtotal: Rp{{ number_format((float) round($lineSubtotal), 0, ',', '.') }}</p>
                                                                        </div>
                                                                        @if ($ingredientId > 0 && $unitCost <= 0)
                                                                            <p class="mt-1 text-xs font-medium text-warning-700 dark:text-warning-400">Harga bahan belum diset. Isi HPP/Unit di menu Inventory → Bahan Baku.</p>
                                                                        @endif
                                                                        <x-common.input-error :for="'variantRecipes.'.$variantKey.'.'.$recipeIndex.'.quantity'" />
                                                                    </td>
                                                                    <td class="px-4 py-3 align-top text-right">
                                                                        <button
                                                                            type="button"
                                                                            wire:click="removeRecipe('{{ $variantKey }}', '{{ $recipeKey }}')"
                                                                            class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]"
                                                                        >
                                                                            Hapus
                                                                        </button>
                                                                    </td>
                                                                </tr>
                                                            @empty
                                                                <tr class="bg-white dark:bg-gray-950/30">
                                                                    <td colspan="3" class="px-4 py-6">
                                                                        <p class="text-sm text-gray-500 dark:text-gray-400">Resep varian ini belum diatur.</p>
                                                                    </td>
                                                                </tr>
                                                            @endforelse
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="sm:hidden space-y-3">
                    @forelse ($variants as $index => $variant)
                        @php
                            $price = (float) ($variant['price'] ?? 0);
                            $percent = $variant['percent'] === null || $variant['percent'] === '' ? null : (int) $variant['percent'];
                            $priceAfterDiscount = $percent === null ? null : round($price - ($price * ($percent / 100)), 2);
                            $variantKey = (string) ($variant['key'] ?? $index);
                            $recipes = (array) ($variantRecipes[$variantKey] ?? []);
                            $hpp = (float) ($hppByVariantKey[$variantKey] ?? 0);
                            $sellingPrice = $priceAfterDiscount === null ? $price : (float) $priceAfterDiscount;
                            $margin = $sellingPrice - $hpp;
                        @endphp
                        <div wire:key="variant-card-{{ $variantKey }}" class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-semibold text-gray-800 dark:text-white/90">Varian {{ number_format($index + 1, 0, ',', '.') }}</p>
                                <button type="button" wire:click="removeVariant('{{ $variantKey }}')" class="text-sm font-semibold text-error-700 hover:text-error-800 dark:text-error-400 dark:hover:text-error-300">
                                    Hapus
                                </button>
                            </div>

                            <div class="mt-3 space-y-3">
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Nama Varian</label>
                                    <input
                                        wire:model.live="variants.{{ $index }}.name"
                                        type="text"
                                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                        placeholder="Contoh: Regular"
                                    />
                                    <x-common.input-error :for="'variants.'.$index.'.name'" />
                                </div>

                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Harga</label>
                                    <input
                                        x-data="currencyInput($wire.entangle('variants.{{ $index }}.price'))"
                                        x-model="displayValue"
                                        @input="handleInput"
                                        type="text"
                                        inputmode="numeric"
                                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 text-right"
                                        placeholder="0"
                                    />
                                    <x-common.input-error :for="'variants.'.$index.'.price'" />
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Diskon %</label>
                                        <input
                                            wire:model.live="variants.{{ $index }}.percent"
                                            type="number"
                                            min="0"
                                            max="100"
                                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 text-right"
                                            placeholder="-"
                                        />
                                        <x-common.input-error :for="'variants.'.$index.'.percent'" />
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Harga Diskon</label>
                                        <div class="shadow-theme-xs h-11 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700 dark:border-gray-800 dark:bg-gray-950/30 dark:text-gray-300 text-right">
                                            {{ $priceAfterDiscount === null ? '-' : number_format($priceAfterDiscount, 2, ',', '.') }}
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div class="shadow-theme-xs rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm dark:border-gray-800 dark:bg-gray-950/30">
                                        <p class="text-xs text-gray-500 dark:text-gray-400">HPP</p>
                                        <p class="font-semibold text-gray-800 dark:text-white/90">Rp{{ number_format($hpp, 0, ',', '.') }}</p>
                                    </div>
                                    <div class="shadow-theme-xs rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm dark:border-gray-800 dark:bg-gray-950/30">
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Margin</p>
                                        <p class="font-semibold {{ $margin < 0 ? 'text-error-700 dark:text-error-400' : 'text-gray-800 dark:text-white/90' }}">Rp{{ number_format($margin, 0, ',', '.') }}</p>
                                    </div>
                                </div>

                                @if (! $isPackage)
                                    <div class="rounded-2xl border border-gray-200 bg-white p-3 dark:border-gray-800 dark:bg-gray-950/30">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <p class="text-sm font-semibold text-gray-800 dark:text-white/90">Bahan Varian</p>
                                                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Atur bahan baku untuk HPP & stok.</p>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-[11px] font-medium text-gray-500 dark:text-gray-400">Total HPP</p>
                                                <p class="text-sm font-semibold text-gray-800 dark:text-white/90">Rp{{ number_format((float) ($hppByVariantKey[$variantKey] ?? 0), 0, ',', '.') }}</p>
                                            </div>
                                        </div>

                                        <button
                                            type="button"
                                            wire:click="addRecipe('{{ $variantKey }}')"
                                            class="mt-3 shadow-theme-xs inline-flex h-11 w-full items-center justify-center rounded-lg bg-brand-500 px-4 text-sm font-semibold text-white transition hover:bg-brand-600"
                                        >
                                            Tambah Bahan
                                        </button>

                                        <div class="mt-3 space-y-3">
                                            @forelse ($recipes as $recipeIndex => $recipe)
                                                @php
                                                    $recipeKey = (string) ($recipe['key'] ?? $recipeIndex);
                                                    $ingredientId = (int) ($recipe['ingredient_id'] ?? 0);
                                                    $unit = (string) ($ingredientUnits[$ingredientId] ?? '');
                                                    $unitCost = (float) ($ingredientCosts[$ingredientId] ?? 0);
                                                    $qty = (float) (\App\Support\Number\QuantityParser::parse($recipe['quantity'] ?? null) ?? 0);
                                                    $lineSubtotal = $unitCost * $qty;
                                                @endphp
                                                <div wire:key="variant-recipe-card-{{ $variantKey }}-{{ $recipeKey }}" class="rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-950/30">
                                                    <div class="flex items-center justify-between">
                                                        <p class="text-xs font-semibold text-gray-700 dark:text-gray-300">Bahan {{ number_format($recipeIndex + 1, 0, ',', '.') }}</p>
                                                        <button type="button" wire:click="removeRecipe('{{ $variantKey }}', '{{ $recipeKey }}')" class="text-xs font-semibold text-error-700 hover:text-error-800 dark:text-error-400 dark:hover:text-error-300">
                                                            Hapus
                                                        </button>
                                                    </div>

                                                    <div class="mt-2 space-y-2.5">
                                                        <div>
                                                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Bahan Baku</label>
                                                            <select
                                                                wire:model.live="variantRecipes.{{ $variantKey }}.{{ $recipeIndex }}.ingredient_id"
                                                                class="shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-white px-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                                                            >
                                                                <option value="">Pilih bahan</option>
                                                                @foreach ($ingredients as $ingredient)
                                                                    <option value="{{ $ingredient->id }}">{{ $ingredient->name }} ({{ $ingredient->unit }})</option>
                                                                @endforeach
                                                            </select>
                                                            <x-common.input-error :for="'variantRecipes.'.$variantKey.'.'.$recipeIndex.'.ingredient_id'" />
                                                        </div>

                                                        <div class="grid grid-cols-2 gap-3">
                                                            <div>
                                                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Qty</label>
                                                                <input
                                                                    wire:model.live.debounce.200ms="variantRecipes.{{ $variantKey }}.{{ $recipeIndex }}.quantity"
                                                                    type="text"
                                                                    inputmode="decimal"
                                                                    class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 text-right"
                                                                    placeholder="0"
                                                                />
                                                                <x-common.input-error :for="'variantRecipes.'.$variantKey.'.'.$recipeIndex.'.quantity'" />
                                                            </div>
                                                            <div>
                                                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Unit</label>
                                                                <div class="shadow-theme-xs h-11 w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                                                                    {{ $unit !== '' ? $unit : '-' }}
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="grid grid-cols-2 gap-3 text-xs text-gray-500 dark:text-gray-400">
                                                            <div class="rounded-xl border border-gray-200 bg-white px-3 py-2 dark:border-gray-800 dark:bg-gray-900">
                                                                <p>HPP/Unit</p>
                                                                <p class="mt-0.5 font-semibold text-gray-800 dark:text-white/90">Rp{{ number_format($unitCost, 0, ',', '.') }}</p>
                                                            </div>
                                                            <div class="rounded-xl border border-gray-200 bg-white px-3 py-2 dark:border-gray-800 dark:bg-gray-900">
                                                                <p>Subtotal</p>
                                                                <p class="mt-0.5 font-semibold text-gray-800 dark:text-white/90">Rp{{ number_format((float) round($lineSubtotal), 0, ',', '.') }}</p>
                                                            </div>
                                                        </div>

                                                        @if ($ingredientId > 0 && $unitCost <= 0)
                                                            <p class="text-xs font-medium text-warning-700 dark:text-warning-400">Harga bahan belum diset. Isi HPP/Unit di menu Inventory → Bahan Baku.</p>
                                                        @endif
                                                    </div>
                                                </div>
                                            @empty
                                                <div class="py-2">
                                                    <p class="text-sm text-gray-500 dark:text-gray-400">Resep varian ini belum diatur.</p>
                                                </div>
                                            @endforelse
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="py-10">
                            <p class="text-center text-sm text-gray-500 dark:text-gray-400">Belum ada varian. Klik “Tambah Varian”.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
                <h3 class="text-lg font-medium text-gray-800 dark:text-white">Gambar Produk</h3>
            </div>
            <div class="p-4 sm:p-6 space-y-4">
                <label for="product-image" class="shadow-theme-xs group hover:border-brand-500 block cursor-pointer overflow-hidden rounded-lg border-2 border-dashed border-gray-300 transition dark:border-gray-800">
                    @if ($image)
                        <div class="relative">
                            <img src="{{ $image->temporaryUrl() }}" alt="Preview" class="h-72 w-full object-cover" />
                            <div class="absolute inset-0 bg-black/35"></div>
                            <div class="absolute inset-0 flex items-center justify-center p-6">
                                <p class="text-center text-sm font-semibold text-white">
                                    Klik untuk ganti gambar
                                </p>
                            </div>
                        </div>
                    @elseif ($currentImageUrl !== '')
                        <div class="relative">
                            <img src="{{ $currentImageUrl }}" alt="Current" class="h-72 w-full object-cover" />
                            <div class="absolute inset-0 bg-black/35"></div>
                            <div class="absolute inset-0 flex items-center justify-center p-6">
                                <p class="text-center text-sm font-semibold text-white">
                                    Klik untuk ganti gambar
                                </p>
                            </div>
                        </div>
                    @else
                        <div class="flex justify-center p-10">
                            <div class="flex max-w-[320px] flex-col items-center gap-4">
                                <div class="inline-flex h-13 w-13 items-center justify-center rounded-full border border-gray-200 text-gray-700 transition dark:border-gray-800 dark:text-gray-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                        <path d="M20.0004 16V18.5C20.0004 19.3284 19.3288 20 18.5004 20H5.49951C4.67108 20 3.99951 19.3284 3.99951 18.5V16M12.0015 4L12.0015 16M7.37454 8.6246L11.9994 4.00269L16.6245 8.6246" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </div>
                                <p class="text-center text-sm text-gray-500 dark:text-gray-400">
                                    <span class="font-medium text-gray-800 dark:text-white/90">Klik untuk upload</span>
                                    atau drag & drop JPG/PNG (max 2MB)
                                </p>
                            </div>
                        </div>
                    @endif

                    <input wire:model.live="image" type="file" id="product-image" class="hidden" accept="image/*" />
                </label>

                <x-common.input-error for="image" class="text-xs text-error-600" />
            </div>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
            <a href="{{ route('products.index') }}" class="shadow-theme-xs inline-flex items-center justify-center gap-2 rounded-lg bg-white px-4 py-3 text-sm font-medium text-gray-700 ring-1 ring-gray-300 transition hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700 dark:hover:bg-white/[0.03]">
                Batal
            </a>
            <button type="submit" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center gap-2 rounded-lg px-4 py-3 text-sm font-medium text-white transition">
                Simpan
            </button>
        </div>
    </form>
</div>
