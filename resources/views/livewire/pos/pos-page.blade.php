<div class="space-y-6">
    <div>
        <div class="px-2 pb-4 rounded-2xl">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex justify-center mx-auto items-center gap-3">
                    <div class="inline-flex items-center rounded-xl border border-gray-200 bg-white p-1 dark:border-gray-800 dark:bg-gray-900">
                        <button type="button" wire:click="setTab('pos')" @class([
                            'rounded-lg px-3 py-2 text-sm font-semibold transition',
                            'bg-brand-500 text-white' => $activeTab === 'pos',
                            'text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-white/[0.03]' => $activeTab !== 'pos',
                        ])>
                            POS Order
                        </button>
                        <button type="button" wire:click="setTab('self_order')" @class([
                            'rounded-lg px-3 py-2 text-sm font-semibold transition inline-flex items-center gap-2',
                            'bg-brand-500 text-white' => $activeTab === 'self_order',
                            'text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-white/[0.03]' => $activeTab !== 'self_order',
                        ])>
                            Self Order
                            @php
                                $inboxCount = (int) $this->selfOrderPaidUnprocessed->count() + (int) $this->selfOrderCashPending->count();
                            @endphp
                            @if ($inboxCount > 0)
                                <span class="inline-flex h-5 min-w-[20px] items-center justify-center rounded-full bg-error-600 px-1 text-[11px] font-bold leading-none text-white">
                                    {{ $inboxCount > 99 ? '99+' : $inboxCount }}
                                </span>
                            @endif
                        </button>
                    </div>
                </div>
            </div>

            @if ($activeTab === 'pos')
            <div class="mt-4 rounded-2xl border border-gray-200 bg-gray-50 p-2 dark:border-gray-800 dark:bg-gray-950">
                <div class="grid grid-cols-1 gap-3 lg:grid-cols-12 lg:items-center">
                    <div class="lg:col-span-3">
                        <label class="sr-only" for="pos-search">Cari Produk</label>
                        <div class="relative">
                            <span class="absolute top-1/2 left-4 -translate-y-1/2 text-gray-500 dark:text-gray-400">
                                <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M3.04199 9.37363C3.04199 5.87693 5.87735 3.04199 9.37533 3.04199C12.8733 3.04199 15.7087 5.87693 15.7087 9.37363C15.7087 12.8703 12.8733 15.7053 9.37533 15.7053C5.87735 15.7053 3.04199 12.8703 3.04199 9.37363ZM9.37533 1.54199C5.04926 1.54199 1.54199 5.04817 1.54199 9.37363C1.54199 13.6991 5.04926 17.2053 9.37533 17.2053C11.2676 17.2053 13.0032 16.5344 14.3572 15.4176L17.1773 18.238C17.4702 18.5309 17.945 18.5309 18.2379 18.238C18.5308 17.9451 18.5309 17.4703 18.238 17.1773L15.4182 14.3573C16.5367 13.0033 17.2087 11.2669 17.2087 9.37363C17.2087 5.04817 13.7014 1.54199 9.37533 1.54199Z" fill="" />
                                </svg>
                            </span>
                            <input id="pos-search" wire:model.live.debounce.300ms="search" type="text" placeholder="Cari produk..." class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-white py-2.5 pr-4 pl-11 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                        </div>
                    </div>

                    <div class="lg:col-span-2">
                        <label class="sr-only" for="pos-category">Kategori</label>
                        <div class="relative">
                            <select id="pos-category" wire:model.live="selectedCategoryId" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-white px-4 py-2.5 pr-11 text-sm text-gray-700 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                <option value="">Semua Kategori</option>
                                @foreach ($this->categories as $category)
                                    <option value="{{ (int) $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                            <span class="pointer-events-none absolute top-1/2 right-4 -translate-y-1/2 text-gray-700 dark:text-gray-400">
                                <svg class="stroke-current" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M4.79175 7.396L10.0001 12.6043L15.2084 7.396" stroke="" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                        </div>
                    </div>

                    <div class="lg:col-span-4">
                        <div class="flex flex-wrap items-center justify-start gap-2">
                            <div class="inline-flex items-center rounded-xl border border-gray-200 bg-white p-1 dark:border-gray-800 dark:bg-gray-900">
                                <button type="button" wire:click="chooseOrderType('take_away')" @class([
                                    'rounded-lg px-3 py-2 text-sm font-semibold transition',
                                    'bg-brand-500 text-white' => $orderType === 'take_away',
                                    'text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-white/[0.03]' => $orderType !== 'take_away',
                                ])>
                                    Take Away
                                </button>
                                <button type="button" wire:click="chooseOrderType('dine_in')" @class([
                                    'rounded-lg px-3 py-2 text-sm font-semibold transition',
                                    'bg-brand-500 text-white' => $orderType === 'dine_in',
                                    'text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-white/[0.03]' => $orderType !== 'dine_in',
                                ])>
                                    Dine In
                                </button>
                            </div>

                            @if ($orderType === 'dine_in')
                                <button type="button" wire:click="$set('tableModalOpen', true)" class="shadow-theme-xs inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-3 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                                    @php
                                        $label = null;
                                        if ($selectedTableId) {
                                            $table = collect($this->tables)->firstWhere('id', (int) $selectedTableId);
                                            $label = is_array($table) ? ($table['label'] ?? null) : null;
                                        }
                                    @endphp
                                    {{ $label ?? 'Pilih Meja' }}
                                </button>
                            @endif
                        </div>
                    </div>

                    <div class="lg:col-span-3">
                        <label class="sr-only" for="pos-scan-code">Kode Transaksi</label>
                        <div class="flex items-stretch gap-2 w-full">
                                <input id="pos-scan-code" wire:model.live="scanCode" type="text" inputmode="text" placeholder="Masukkan kode" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 w-full rounded-lg border border-gray-300 bg-white py-2.5 px-3 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                            <button type="button" wire:click="importTransactionCode" @disabled(trim((string) ($scanCode ?? '')) === '') class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex h-11 items-center justify-center rounded-lg px-4 text-sm font-semibold text-white transition disabled:opacity-50">
                                Proses
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>

        @if ($activeTab === 'pos')
            <div class="grid grid-cols-1 gap-6 p-2 lg:grid-cols-12">
                <div class="lg:col-span-8">
                    <div wire:init="loadVariantStockStatuses" class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5">
                        @forelse ($this->productCards as $product)
                            @php
                                $image = (string) ($product['image'] ?? '');
                                $imageUrl = $image !== '' ? asset('storage/'.$image) : null;
                                $variants = (array) ($product['variants'] ?? []);
                                $firstVariant = $variants[0] ?? null;
                                $variantCount = count($variants);
                                $hasVariant = is_array($firstVariant) && ((int) ($firstVariant['id'] ?? 0) > 0);
                                $basePrice = $hasVariant ? (int) round((float) ($firstVariant['price'] ?? 0)) : null;
                                $after = $hasVariant && ($firstVariant['price_afterdiscount'] ?? null) !== null ? (int) round((float) ($firstVariant['price_afterdiscount'] ?? 0)) : null;
                                $percent = $hasVariant && ($firstVariant['percent'] ?? null) !== null ? (int) ($firstVariant['percent'] ?? 0) : 0;
                                $computed = ($hasVariant && $basePrice !== null && $percent > 0 && $percent < 100) ? max(0, (int) round($basePrice - ($basePrice * ($percent / 100)))) : null;
                                $final = null;
                                if ($after !== null && $basePrice !== null && $after > 0 && $after < $basePrice) {
                                    $final = $after;
                                } elseif ($computed !== null && $basePrice !== null && $computed < $basePrice) {
                                    $final = $computed;
                                } elseif ($basePrice !== null) {
                                    $final = $basePrice;
                                }
                                $isPromo = $final !== null && $basePrice !== null && $final < $basePrice;
                                $isPackage = (bool) ($product['is_package'] ?? false);
                                $packageType = (string) ($product['package_type'] ?? 'simple');
                                $packageComponentVariantIds = (array) ($product['package_component_variant_ids'] ?? []);

                                $statusVariantId = $hasVariant ? (int) ($firstVariant['id'] ?? 0) : 0;
                                $stockStatus = null;

                                if ($isPackage && $packageType !== 'complex' && $packageComponentVariantIds !== []) {
                                    $componentStatuses = [];
                                    foreach ($packageComponentVariantIds as $componentVariantId) {
                                        $componentVariantId = (int) $componentVariantId;
                                        if ($componentVariantId <= 0) {
                                            continue;
                                        }
                                        $componentStatuses[] = $this->variantStockStatuses[$componentVariantId] ?? null;
                                    }

                                    if (in_array('missing_bom', $componentStatuses, true)) {
                                        $stockStatus = 'missing_bom';
                                    } elseif (in_array('insufficient', $componentStatuses, true)) {
                                        $stockStatus = 'insufficient';
                                    } elseif (in_array('low', $componentStatuses, true)) {
                                        $stockStatus = 'low';
                                    } elseif ($componentStatuses !== []) {
                                        $stockStatus = 'ok';
                                    }
                                } elseif (! $isPackage && $statusVariantId > 0) {
                                    $stockStatus = $this->variantStockStatuses[$statusVariantId] ?? null;
                                }
                                $stockBadge = null;
                                if ($stockStatus === 'missing_bom') {
                                    $stockBadge = ['label' => 'Resep belum diatur', 'class' => 'bg-gray-900/70 text-white'];
                                } elseif ($stockStatus === 'insufficient') {
                                    $stockBadge = ['label' => 'Stok bahan kurang', 'class' => 'bg-error-600 text-white'];
                                } elseif ($stockStatus === 'low') {
                                    $stockBadge = ['label' => 'Stok bahan menipis', 'class' => 'bg-warning-600 text-white'];
                                }
                            @endphp
                            <button type="button" wire:click="addToCart({{ (int) ($product['id'] ?? 0) }})" class="group flex h-full flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs transition hover:-translate-y-0.5 hover:shadow-lg dark:border-gray-800 dark:bg-white/[0.03]">
                                <div class="relative">
                                    @if ($imageUrl)
                                        <img src="{{ $imageUrl }}" alt="{{ $product['name'] ?? '' }}" class="h-36 w-full object-cover transition duration-300 group-hover:scale-105" />
                                    @else
                                        <div class="flex h-36 w-full items-center justify-center bg-gray-100 text-sm text-gray-500 dark:bg-gray-800 dark:text-gray-400">No Image</div>
                                    @endif
                                    @if ($variantCount > 1)
                                        <div class="absolute top-2 right-2 rounded-full bg-gray-900/70 px-2 py-1 text-xs font-semibold text-white">
                                            {{ $variantCount }} varian
                                        </div>
                                    @endif
                                    @if ($isPromo && $percent > 0)
                                        <div class="absolute top-2 left-2 rounded-full bg-error-600 px-2 py-1 text-xs font-semibold text-white">
                                            -{{ $percent }}%
                                        </div>
                                    @endif
                                    @if ($stockBadge)
                                        <div class="absolute bottom-2 left-2 rounded-full px-2 py-1 text-xs font-semibold {{ $stockBadge['class'] }}">
                                            {{ $stockBadge['label'] }}
                                        </div>
                                    @endif
                                </div>
                                <div class="flex flex-1 flex-col gap-2 p-3 text-left">
                                    <p class="line-clamp-2 text-sm font-semibold text-gray-800 dark:text-white/90">{{ $product['name'] ?? '-' }}</p>
                                    <div class="mt-auto">
                                        @if ($final !== null)
                                            <div class="flex flex-col">
                                                <span class="text-base font-bold text-brand-600 dark:text-brand-400">Rp {{ number_format($final, 0, ',', '.') }}</span>
                                                @if ($isPromo)
                                                    <span class="text-xs text-gray-400 line-through">Rp {{ number_format($basePrice, 0, ',', '.') }}</span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Belum ada varian</span>
                                        @endif
                                    </div>
                                </div>
                            </button>
                        @empty
                            <div class="col-span-full py-20 text-center">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Tidak ada produk.</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="lg:col-span-4">
                    <div class="lg:sticky lg:top-20 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]">
                        <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                            <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Keranjang</h3>
                            <div class="inline-flex items-center gap-2">
                                @if ($editingTransactionId)
                                    <span class="rounded-full bg-warning-50 px-2 py-0.5 text-xs font-medium text-warning-700 dark:bg-warning-500/15 dark:text-warning-400">Edit Pending</span>
                                @endif
                            </div>
                            <button wire:click="$set('pendingOrdersModalOpen', true)" class="relative p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition" title="Pesanan Pending">
                            <svg class="w-6 h-6 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            @if($this->pendingTransactions->count() > 0)
                            <span class="absolute top-1 right-1 w-4 h-4 bg-red-500 text-white text-[10px] font-bold flex items-center justify-center rounded-full">
                                {{ $this->pendingTransactions->count() }}
                            </span>
                            @endif
                        </button>
                        </div>

                        <div class="space-y-4 p-5">
                            <div class="custom-scrollbar max-h-64 space-y-2 overflow-y-auto">
                                @forelse ($cartItems as $idx => $item)
                                    @php
                                        $qty = (int) ($item['quantity'] ?? 0);
                                        $price = (int) ($item['price'] ?? 0);
                                        $original = (int) ($item['original_price'] ?? $price);
                                        $isPromo = $original > 0 && $price > 0 && $price < $original;
                                        $isComplexPackage = (string) ($item['package_type'] ?? '') === 'complex';
                                    @endphp
                                    <div class="rounded-xl border border-gray-100 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-900">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0 flex-1">
                                                <p class="truncate text-sm font-semibold text-gray-800 dark:text-white/90">{{ $item['name'] ?? '-' }}</p>
                                                @if (! empty($item['variant_name']))
                                                    <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $item['variant_name'] }}</p>
                                                @endif
                                                <div class="mt-1 flex flex-wrap items-center gap-2">
                                                    <span class="text-sm font-semibold text-brand-600 dark:text-brand-400">Rp {{ number_format($price, 0, ',', '.') }}</span>
                                                    @if ($isPromo)
                                                        <span class="text-xs text-gray-400 line-through">Rp {{ number_format($original, 0, ',', '.') }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                @if ($isComplexPackage)
                                                    <button type="button" wire:click="editComplexPackageInCart({{ (int) $idx }})" class="shadow-theme-xs inline-flex h-8 items-center justify-center rounded-lg border border-gray-200 bg-white px-3 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                                                        Edit
                                                    </button>
                                                @endif
                                                <button type="button" wire:click="removeItem({{ (int) $idx }})" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M6 7H18M10 11V17M14 11V17M9 7V5C9 4.44772 9.44772 4 10 4H14C14.5523 4 15 4.44772 15 5V7M19 7L18.2 20.2C18.154 20.7196 17.7189 21.125 17.197 21.125H6.803C6.28108 21.125 5.84601 20.7196 5.8 20.2L5 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="mt-3 flex items-center justify-between gap-3">
                                            <div class="inline-flex items-center gap-2">
                                                <button type="button" wire:click="decrement({{ (int) $idx }})" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">-</button>
                                                <span class="w-8 text-center text-sm font-semibold text-gray-800 dark:text-white/90">{{ $qty }}</span>
                                                <button type="button" wire:click="increment({{ (int) $idx }})" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">+</button>
                                            </div>
                                            <span class="text-sm font-semibold text-gray-800 dark:text-white/90">Rp {{ number_format($qty * $price, 0, ',', '.') }}</span>
                                        </div>

                                        <div class="mt-3">
                                            <input wire:model.defer="cartItems.{{ $idx }}.note" type="text" placeholder="Catatan item (opsional)" class="dark:bg-dark-900 shadow-theme-xs h-9 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-xs text-gray-800 placeholder:text-gray-400 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                                        </div>
                                    </div>
                                @empty
                                    <div class="py-10 text-center">
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Keranjang masih kosong.</p>
                                    </div>
                                @endforelse
                            </div>

                            <div class="rounded-2xl bg-gray-50 p-4 dark:bg-gray-900">
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-300">
                                        <span>Subtotal</span>
                                        <span class="font-medium">Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                                    </div>
                                    @if ($taxAmount > 0)
                                        <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-300">
                                            <span>Pajak PB1 ({{ number_format((float) $taxRate, 2, ',', '.') }}%)</span>
                                            <span class="font-medium">Rp {{ number_format($taxAmount, 0, ',', '.') }}</span>
                                        </div>
                                    @endif
                                    @if ($roundingAmount !== 0)
                                        <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-300">
                                            <span>Pembulatan</span>
                                            <span class="font-medium">Rp {{ number_format($roundingAmount, 0, ',', '.') }}</span>
                                        </div>
                                    @endif
                                    <div class="flex items-center justify-between border-t border-gray-200 pt-3 text-base font-semibold text-gray-800 dark:border-gray-800 dark:text-white/90">
                                        <span>Total</span>
                                        <span class="text-brand-600 dark:text-brand-400">Rp {{ number_format($total, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-2">
                                <button type="button" wire:click="clearCart" class="shadow-theme-xs inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                                    Reset
                                </button>
                                @if (! $cartLocked)
                                    <button type="button" wire:click="openSavePending" @disabled(count($cartItems) === 0) class="shadow-theme-xs inline-flex h-11 items-center justify-center rounded-lg border border-warning-300 bg-warning-50 text-sm font-semibold text-warning-800 hover:bg-warning-100 disabled:opacity-50 dark:border-warning-500/30 dark:bg-warning-500/10 dark:text-warning-300">
                                        Simpan
                                    </button>
                                @else
                                    <div></div>
                                @endif
                                <button type="button" wire:click="openCheckout" @disabled(count($cartItems) === 0) class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex h-11 items-center justify-center rounded-lg text-sm font-semibold text-white transition disabled:opacity-50">
                                    Checkout
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="p-2">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Online Lunas</h3>
                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                {{ (int) $this->selfOrderPaidUnprocessed->count() }}
                            </span>
                        </div>

                        <div class="mt-4 space-y-2">
                            @can('transactions.view')
                                @forelse ($this->selfOrderPaidUnprocessed as $trx)
                                    <div class="flex items-center justify-between gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 dark:border-gray-800 dark:bg-gray-900">
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2">
                                                <span class="truncate text-sm font-semibold text-gray-900 dark:text-white">{{ $trx->code }}</span>
                                                @if ($trx->diningTable)
                                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                                        {{ $trx->diningTable->name }}
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                Rp{{ number_format((int) $trx->total, 0, ',', '.') }}
                                                · {{ (int) $trx->transaction_items_count }} item
                                                @if ($trx->paid_at)
                                                    · {{ $trx->paid_at->format('d M Y, H:i') }}
                                                @elseif ($trx->created_at)
                                                    · {{ $trx->created_at->format('d M Y, H:i') }}
                                                @endif
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            @can('transactions.details')
                                                <a href="{{ route('transactions.show', (int) $trx->id) }}" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-white/[0.03]">
                                                    Detail
                                                </a>
                                            @endcan
                                            @can('transactions.print')
                                                <button type="button" wire:click="markSelfOrderProcessed({{ (int) $trx->id }})" class="rounded-lg bg-brand-500 px-3 py-2 text-xs font-semibold text-white hover:bg-brand-600">
                                                    Proses
                                                </button>
                                            @endcan
                                        </div>
                                    </div>
                                @empty
                                    <div class="rounded-xl border border-dashed border-gray-200 p-6 text-center text-sm text-gray-500 dark:border-gray-800 dark:text-gray-400">
                                        Belum ada transaksi online lunas.
                                    </div>
                                @endforelse
                            @else
                                <div class="rounded-xl border border-dashed border-gray-200 p-6 text-center text-sm text-gray-500 dark:border-gray-800 dark:text-gray-400">
                                    Anda tidak memiliki akses untuk melihat transaksi.
                                </div>
                            @endcan
                        </div>
                    </div>

                    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Bayar di Kasir</h3>
                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                {{ (int) $this->selfOrderCashPending->count() }}
                            </span>
                        </div>

                        <div class="mt-4 space-y-2">
                            @can('transactions.view')
                                @forelse ($this->selfOrderCashPending as $trx)
                                    <div class="flex items-center justify-between gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 dark:border-gray-800 dark:bg-gray-900">
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2">
                                                <span class="truncate text-sm font-semibold text-gray-900 dark:text-white">{{ $trx->code }}</span>
                                                @if ($trx->diningTable)
                                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                                        {{ $trx->diningTable->name }}
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                Rp{{ number_format((int) $trx->total, 0, ',', '.') }}
                                                · {{ (int) $trx->transaction_items_count }} item
                                                @if ($trx->created_at)
                                                    · {{ $trx->created_at->format('d M Y, H:i') }}
                                                @endif
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            @can('transactions.details')
                                                <a href="{{ route('transactions.show', (int) $trx->id) }}" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-white/[0.03]">
                                                    Detail
                                                </a>
                                                <button type="button" wire:click="takeSelfOrderPending({{ (int) $trx->id }})" class="rounded-lg bg-brand-500 px-3 py-2 text-xs font-semibold text-white hover:bg-brand-600">
                                                    Ambil ke POS
                                                </button>
                                            @endcan
                                        </div>
                                    </div>
                                @empty
                                    <div class="rounded-xl border border-dashed border-gray-200 p-6 text-center text-sm text-gray-500 dark:border-gray-800 dark:text-gray-400">
                                        Belum ada pesanan bayar di kasir.
                                    </div>
                                @endforelse
                            @else
                                <div class="rounded-xl border border-dashed border-gray-200 p-6 text-center text-sm text-gray-500 dark:border-gray-800 dark:text-gray-400">
                                    Anda tidak memiliki akses untuk melihat transaksi.
                                </div>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    @if ($tableModalOpen)
        <div class="fixed inset-0 z-[100000] flex items-center justify-center p-4" aria-modal="true" role="dialog">
            <div class="absolute inset-0 bg-black/50" wire:click="$set('tableModalOpen', false)"></div>
            <div class="relative w-full max-w-2xl overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Pilih Meja</h3>
                    <button type="button" wire:click="$set('tableModalOpen', false)" class="text-sm font-medium text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
                        Tutup
                    </button>
                </div>
                <div class="custom-scrollbar max-h-[70vh] overflow-y-auto p-5">
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8">
                        @foreach ($this->tables as $t)
                            <button type="button" wire:click="selectTable({{ (int) $t['id'] }})" @class([
                                'rounded-xl border px-3 py-3 text-sm font-semibold transition',
                                'bg-brand-500 text-white border-brand-600 shadow-theme-xs' => (int) $selectedTableId === (int) $t['id'],
                                'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700 dark:hover:bg-white/[0.03]' => (int) $selectedTableId !== (int) $t['id'],
                            ])>
                                {{ $t['label'] }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($variantModalOpen)
        <div class="fixed inset-0 z-[100000] flex items-center justify-center p-4" aria-modal="true" role="dialog">
            <div class="fixed inset-0 bg-black/50" wire:click="$set('variantModalOpen', false)"></div>
            <div class="relative w-full max-w-lg overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Pilih Varian</h3>
                    <button type="button" wire:click="$set('variantModalOpen', false)" class="text-sm font-medium text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
                        Tutup
                    </button>
                </div>
                <div class="p-5">
                    <div class="space-y-2">
                        @foreach ($variantOptions as $v)
                            @php
                                $isPromo = (int) $v['final_price'] < (int) $v['price'];
                                $variantId = (int) ($v['id'] ?? 0);
                                $stockStatus = $variantId > 0 ? ($this->variantStockStatuses[$variantId] ?? null) : null;
                                $stockBadge = null;
                                if ($stockStatus === 'missing_bom') {
                                    $stockBadge = ['label' => 'BOM belum diatur', 'class' => 'bg-gray-900/70 text-white'];
                                } elseif ($stockStatus === 'insufficient') {
                                    $stockBadge = ['label' => 'Stok bahan kurang', 'class' => 'bg-error-600 text-white'];
                                } elseif ($stockStatus === 'low') {
                                    $stockBadge = ['label' => 'Stok menipis', 'class' => 'bg-warning-600 text-white'];
                                }
                            @endphp
                            <button type="button" wire:click="addVariantToCart({{ (int) $v['id'] }})" class="flex w-full items-center justify-between rounded-xl border border-gray-200 bg-white px-4 py-3 text-left shadow-theme-xs hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:hover:bg-white/[0.03]">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-gray-800 dark:text-white/90">{{ $v['name'] }}</p>
                                    <div class="mt-0.5 flex flex-wrap items-center gap-2">
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Rp {{ number_format((int) $v['final_price'], 0, ',', '.') }}</p>
                                        @if ($stockBadge)
                                            <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $stockBadge['class'] }}">{{ $stockBadge['label'] }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if ($isPromo)
                                        <span class="text-xs text-gray-400 line-through">Rp {{ number_format((int) $v['price'], 0, ',', '.') }}</span>
                                    @endif
                                    <span class="rounded-lg bg-brand-500 px-2 py-1 text-xs font-semibold text-white">Tambah</span>
                                </div>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($complexPackageModalOpen)
        @php
            $invalid = false;
            foreach ($complexPackageComponents as $component) {
                $baseQty = (int) ($component['base_quantity'] ?? 0);
                $allocations = (array) ($component['allocations'] ?? []);
                if ($baseQty <= 0 || $allocations === []) {
                    $invalid = true;
                    break;
                }

                $sum = 0;
                foreach ($allocations as $alloc) {
                    $qty = (int) ($alloc['quantity'] ?? 0);
                    $variantId = (int) ($alloc['variant_id'] ?? 0);
                    if ($qty <= 0 || $variantId <= 0) {
                        $invalid = true;
                        break 2;
                    }
                    $sum += $qty;
                }

                if ($sum !== $baseQty) {
                    $invalid = true;
                    break;
                }
            }
        @endphp
        <div class="fixed inset-0 z-[100000] flex items-center justify-center p-4" aria-modal="true" role="dialog">
            <div class="fixed inset-0 bg-black/50" wire:click="closeComplexPackageModal"></div>
            <div class="relative w-full max-w-2xl overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <div>
                        <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Konfigurasi Paket</h3>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Pilih varian dan catatan untuk setiap komponen.</p>
                    </div>
                    <button type="button" wire:click="closeComplexPackageModal" class="text-sm font-medium text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
                        Tutup
                    </button>
                </div>
                <div class="custom-scrollbar max-h-[70vh] overflow-y-auto p-5">
                    <div class="space-y-3">
                        @foreach ($complexPackageComponents as $index => $row)
                            @php
                                $rowKey = (string) ($row['key'] ?? $index);
                                $productName = (string) ($row['component_product_name'] ?? '');
                                $qty = (int) ($row['base_quantity'] ?? 0);
                                $isSplitable = (bool) ($row['is_splitable'] ?? false);
                                $options = (array) ($row['variant_options'] ?? []);
                                $allocations = (array) ($row['allocations'] ?? []);
                                $sumQty = collect($allocations)->sum(fn ($a) => (int) ($a['quantity'] ?? 0));
                                $canAddRow = $isSplitable && collect($allocations)->contains(fn ($a) => (int) ($a['quantity'] ?? 0) > 1);
                            @endphp
                            <div wire:key="complex-package-config-row-{{ $rowKey }}" class="rounded-xl border border-gray-200 bg-white p-4 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-gray-800 dark:text-white/90">{{ $productName }}</p>
                                        <div class="mt-0.5 flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                            <span>Qty: {{ $qty }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-4 space-y-3">
                                    @foreach ($allocations as $aIndex => $alloc)
                                        @php
                                            $allocKey = (string) ($alloc['key'] ?? $aIndex);
                                            $canRemove = $isSplitable && count($allocations) > 1;
                                        @endphp
                                        <div wire:key="complex-package-allocation-{{ $rowKey }}-{{ $allocKey }}" class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-950/30">
                                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-12 sm:items-end">
                                                <div class="sm:col-span-3">
                                                    <label class="mb-1 block text-[11px] font-medium text-gray-600 dark:text-gray-400">Qty</label>
                                                    <input
                                                        wire:model.live="complexPackageComponents.{{ $index }}.allocations.{{ $aIndex }}.quantity"
                                                        type="number"
                                                        min="1"
                                                        @disabled(! $isSplitable)
                                                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 disabled:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:disabled:bg-gray-900"
                                                    />
                                                </div>
                                                <div class="sm:col-span-5">
                                                    <label class="mb-1 block text-[11px] font-medium text-gray-600 dark:text-gray-400">Varian</label>
                                                    <div class="relative z-20 bg-transparent">
                                                        <select
                                                            wire:model.live="complexPackageComponents.{{ $index }}.allocations.{{ $aIndex }}.variant_id"
                                                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-10 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-3 py-2 pr-10 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                                        >
                                                            <option value="">Pilih varian</option>
                                                            @foreach ($options as $opt)
                                                                <option value="{{ (int) ($opt['id'] ?? 0) }}">{{ (string) ($opt['name'] ?? '') }}</option>
                                                            @endforeach
                                                        </select>
                                                        <span class="pointer-events-none absolute top-1/2 right-3 z-30 -translate-y-1/2 text-gray-700 dark:text-gray-400">
                                                            <svg class="stroke-current" width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                <path d="M4.79175 7.396L10.0001 12.6043L15.2084 7.396" stroke="" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                            </svg>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="sm:col-span-4">
                                                    <label class="mb-1 block text-[11px] font-medium text-gray-600 dark:text-gray-400">Catatan (opsional)</label>
                                                    <input
                                                        wire:model.live="complexPackageComponents.{{ $index }}.allocations.{{ $aIndex }}.note"
                                                        type="text"
                                                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                                                        placeholder="Contoh: tanpa cabe, pisah sambal"
                                                    />
                                                </div>
                                            </div>
                                            @if ($canRemove)
                                                <div class="mt-2 flex justify-end">
                                                    <button type="button" wire:click="removeComplexPackageAllocation('{{ $rowKey }}', '{{ $allocKey }}')" class="text-xs font-semibold text-error-600 hover:text-error-700">
                                                        Hapus Baris
                                                    </button>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach

                                    @if ($canAddRow)
                                        <div class="flex justify-end">
                                            <button type="button" wire:click="addComplexPackageAllocation('{{ $rowKey }}')" class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                                                Tambah Baris
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 border-t border-gray-200 px-5 py-4 dark:border-gray-800">
                    <button type="button" wire:click="closeComplexPackageModal" class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                        Batal
                    </button>
                    <button type="button" wire:click="confirmComplexPackageToCart" @disabled($invalid) class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-semibold text-white transition disabled:opacity-50">
                        Konfirmasi
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if ($pendingOrdersModalOpen)
        <div class="fixed inset-0 z-[100000] flex items-center justify-center p-4" aria-modal="true" role="dialog">
            <div class="fixed inset-0 bg-black/50" wire:click="$set('pendingOrdersModalOpen', false)"></div>
            <div class="relative w-full max-w-2xl overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Pesanan Pending</h3>
                    <button type="button" wire:click="$set('pendingOrdersModalOpen', false)" class="text-sm font-medium text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
                        Tutup
                    </button>
                </div>
                <div class="custom-scrollbar max-h-[70vh] overflow-y-auto">
                    @can('transactions.view')
                        <table class="w-full table-auto">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-gray-800">
                                    <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Jam</th>
                                    <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Nama</th>
                                    <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Kode</th>
                                    <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @forelse ($this->pendingTransactions as $trx)
                                    <tr>
                                        <td class="px-5 py-4">
                                            <p class="text-sm font-semibold text-gray-800 dark:text-white/90">{{ $trx->updated_at?->format('H:i') ?? '-' }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $trx->updated_at?->format('d/m/Y') ?? '-' }}</p>
                                        </td>
                                        <td class="px-5 py-4">
                                            <p class="text-sm text-gray-800 dark:text-white/90">{{ $trx->name }}</p>
                                        </td>
                                        <td class="px-5 py-4">
                                            <p class="text-sm font-semibold text-gray-800 dark:text-white/90">{{ $trx->code }}</p>
                                        </td>
                                        <td class="px-5 py-4 text-right">
                                            @can('transactions.details')
                                                <button type="button" wire:click="loadPending({{ (int) $trx->id }})" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-3 py-2 text-xs font-semibold text-white transition">
                                                    Muat
                                                </button>
                                            @endcan
                                            @can('transactions.void')
                                                <button type="button" wire:click="deletePending({{ (int) $trx->id }})" class="shadow-theme-xs ml-2 inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                                                    Hapus
                                                </button>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-5 py-10">
                                            <p class="text-center text-sm text-gray-500 dark:text-gray-400">Belum ada pesanan pending.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    @else
                        <div class="p-6 text-center text-sm text-gray-500 dark:text-gray-400">
                            Anda tidak memiliki akses untuk melihat transaksi.
                        </div>
                    @endcan
                </div>
            </div>
        </div>
    @endif

    @if ($savePendingModalOpen)
        <div class="fixed inset-0 z-[100000] flex items-center justify-center p-4" aria-modal="true" role="dialog">
            <div class="fixed inset-0 bg-black/50" wire:click="$set('savePendingModalOpen', false)"></div>
            <div class="relative w-full max-w-md overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Simpan Pending</h3>
                    <button type="button" wire:click="$set('savePendingModalOpen', false)" class="text-sm font-medium text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
                        Tutup
                    </button>
                </div>
                <div class="space-y-4 p-5">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Nama Pelanggan</label>
                        <input wire:model.live="customerName" type="text" aria-invalid="{{ $errors->has('customerName') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('customerName') ? 'error-customerName' : '' }}" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" placeholder="Contoh: Walk-in" />
                        <x-common.input-error for="customerName" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Telepon (Opsional)</label>
                        <input wire:model.live="customerPhone" type="text" aria-invalid="{{ $errors->has('customerPhone') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('customerPhone') ? 'error-customerPhone' : '' }}" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" placeholder="08xxxx" />
                        <x-common.input-error for="customerPhone" />
                    </div>
                    <button type="button" wire:click="saveAsPending" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex h-11 w-full items-center justify-center rounded-lg px-4 text-sm font-semibold text-white transition">
                        Simpan
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if ($checkoutModalOpen)
        @php
            $paymentMethods = [
                ['id' => 'cash', 'name' => 'Tunai'],
                ['id' => 'qris', 'name' => 'QRIS'],
                ['id' => 'transfer_bank', 'name' => 'Transfer Bank'],
                ['id' => 'gofood', 'name' => 'GoFood'],
                ['id' => 'grab_food', 'name' => 'GrabFood'],
                ['id' => 'shopee_food', 'name' => 'ShopeeFood'],
            ];
        @endphp
        <div class="fixed inset-0 z-[100000] overflow-y-auto" aria-modal="true" role="dialog">
            <div class="fixed inset-0 bg-black/50" wire:click="$set('checkoutModalOpen', false)"></div>
                <div class="relative flex min-h-full items-center justify-center p-4 sm:items-center">
                    <div class="relative flex w-full max-w-2xl max-h-[85vh] flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl dark:border-gray-800 dark:bg-gray-900">
                        <div class="min-h-0 flex flex-1 flex-col">
                            <div class="min-h-0 flex flex-col">
                            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-800">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Checkout</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        Pembayaran
                                    </p>
                                </div>
                                <button type="button" wire:click="$set('checkoutModalOpen', false)" class="text-sm font-medium text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
                                    Tutup
                                </button>
                            </div>

                            <div class="min-h-0 flex-1 overflow-y-auto p-6 pb-24">
                                <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-950 mb-6">
                                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                        <div>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">Subtotal</p>
                                            <p class="mt-1 text-sm font-semibold text-gray-800 dark:text-white/90">Rp {{ number_format((int) $subtotal, 0, ',', '.') }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">Diskon</p>
                                            <p class="mt-1 text-sm font-semibold text-gray-800 dark:text-white/90">Rp {{ number_format((int) ($discountTotalAmount ?? 0), 0, ',', '.') }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">Pajak PB1</p>
                                            <p class="mt-1 text-sm font-semibold text-gray-800 dark:text-white/90">Rp {{ number_format((int) $taxAmount, 0, ',', '.') }}</p>
                                        </div>
                                        <div class="sm:text-right">
                                            <p class="text-xs text-gray-500 dark:text-gray-400">Total</p>
                                            <p class="mt-1 text-base font-bold text-gray-900 dark:text-white/90">Rp {{ number_format((int) $total, 0, ',', '.') }}</p>
                                        </div>
                                    </div>
                                </div>

                                @if ($checkoutStep === 1)
                                    <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
                                        <p class="text-sm font-semibold text-gray-800 dark:text-white/90">Customer</p>
                                        <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                            <div class="sm:col-span-2">
                                                @can('members.view')
                                                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Member (Opsional)</label>
                                                    <select wire:model.live="memberId" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" @disabled($cartLocked)>
                                                        <option value="">-</option>
                                                        @php
                                                            $user = auth()->user();
                                                            $canViewMemberPii = (bool) (($user && method_exists($user, 'can')) ? $user->can('members.pii.view') : false);
                                                            $maskPhone = function ($phone): string {
                                                                $phone = trim((string) ($phone ?? ''));
                                                                if ($phone === '') {
                                                                    return '';
                                                                }
                                                                $len = strlen($phone);
                                                                if ($len <= 4) {
                                                                    return str_repeat('*', max(0, $len - 1)).substr($phone, -1);
                                                                }

                                                                return substr($phone, 0, 2).str_repeat('*', max(0, $len - 6)).substr($phone, -4);
                                                            };
                                                        @endphp
                                                        @foreach ($this->members as $m)
                                                            @php
                                                                $phoneLabel = '';
                                                                if ($m->phone) {
                                                                    $phoneLabel = $canViewMemberPii ? (string) $m->phone : $maskPhone($m->phone);
                                                                }
                                                            @endphp
                                                            <option value="{{ (int) $m->id }}">{{ $m->name }}{{ $phoneLabel !== '' ? ' ('.$phoneLabel.')' : '' }}</option>
                                                        @endforeach
                                                    </select>
                                                @else
                                                    <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-xs text-gray-600 dark:border-gray-800 dark:bg-white/[0.03] dark:text-gray-400">
                                                        Anda tidak memiliki akses untuk memilih member.
                                                    </div>
                                                @endcan
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Nama</label>
                                            <input wire:model.live="customerName" type="text" aria-invalid="{{ $errors->has('customerName') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('customerName') ? 'error-customerName' : '' }}" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 disabled:opacity-50 disabled:cursor-not-allowed dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" placeholder="Walk-in" @disabled($cartLocked) />
                                                <x-common.input-error for="customerName" />
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Telepon (Opsional)</label>
                                            <input wire:model.live="customerPhone" type="text" aria-invalid="{{ $errors->has('customerPhone') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('customerPhone') ? 'error-customerPhone' : '' }}" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 disabled:opacity-50 disabled:cursor-not-allowed dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" placeholder="08xxxx" @disabled($cartLocked) />
                                                <x-common.input-error for="customerPhone" />
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                @if ($checkoutStep === 2 && false)
                                    <div class="mb-4 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
                                        <p class="text-sm font-semibold text-gray-800 dark:text-white/90">Voucher</p>
                                        <div class="mt-3">
                                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Kode Voucher (Opsional)</label>
                                            <input wire:model.live.debounce.500ms="voucherCodeInput" type="text" aria-invalid="{{ $errors->has('voucherCodeInput') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('voucherCodeInput') ? 'error-voucherCodeInput' : '' }}" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 disabled:opacity-50 disabled:cursor-not-allowed" placeholder="Masukkan kode voucher" @disabled($cartLocked) />
                                            <x-common.input-error for="voucherCodeInput" />
                                            @if (trim((string) ($voucherCodeInput ?? '')) !== '')
                                                <p class="mt-1 text-xs {{ ($voucherValid ?? false) ? 'text-success-600' : 'text-gray-500 dark:text-gray-400' }}">
                                                    {{ $voucherMessage }}
                                                </p>
                                            @endif
                                            @if ($cartLocked && trim((string) ($voucherCodeInput ?? '')) !== '')
                                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Voucher mengikuti pesanan self-order.</p>
                                            @endif
                                        </div>
                                    </div>

                                    @if ($memberId && ($memberPoints > 0 || $pointsToRedeem > 0))
                                        <div class="mb-4 rounded-2xl border border-brand-200 bg-brand-50 p-4 dark:border-brand-800 dark:bg-brand-900/20">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <p class="text-sm font-semibold text-brand-800 dark:text-brand-300">Poin Member</p>
                                                    <p class="text-xs text-brand-600 dark:text-brand-400">
                                                        Tersedia: {{ number_format($memberPoints, 0, ',', '.') }} Poin
                                                    </p>
                                                </div>
                                                <div class="text-right">
                                                    <label class="inline-flex items-center cursor-pointer">
                                                        <input type="checkbox" wire:model.live="redeemPoints" class="sr-only peer" @disabled($cartLocked)>
                                                        <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-brand-300 dark:peer-focus:ring-brand-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-brand-600"></div>
                                                        <span class="ms-3 text-sm font-medium text-gray-900 dark:text-gray-300">Tukar</span>
                                                    </label>
                                                </div>
                                            </div>
                                            @if ($redeemPoints)
                                                @if ($pointsToRedeem > 0)
                                                    <div class="mt-2 text-xs text-brand-700 dark:text-brand-300">
                                                        Menukar <b>{{ number_format($pointsToRedeem, 0, ',', '.') }}</b> poin = Diskon <b>Rp {{ number_format($pointDiscountAmount, 0, ',', '.') }}</b>
                                                    </div>
                                                @elseif ($memberPoints < $minRedemptionPoints)
                                                    <div class="mt-2 text-xs text-error-600">
                                                        Minimal penukaran {{ number_format($minRedemptionPoints, 0, ',', '.') }} poin.
                                                    </div>
                                                @endif
                                            @endif
                                            @if ($cartLocked && $pointsToRedeem > 0)
                                                <div class="mt-2 text-xs text-brand-700 dark:text-brand-300">
                                                    Poin mengikuti pesanan self-order.
                                                </div>
                                            @endif
                                        </div>
                                    @endif

                                    <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-950">
                                        <p class="text-sm font-semibold text-gray-800 dark:text-white/90">Diskon Manual</p>
                                        @can('discounts.manual.apply')
                                            <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                                <div>
                                                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Tipe</label>
                                                    <select wire:model.live="manualDiscountType" aria-invalid="{{ $errors->has('manualDiscountType') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('manualDiscountType') ? 'error-manualDiscountType' : '' }}" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                                        <option value="">-</option>
                                                        <option value="percent">Persen (%)</option>
                                                        <option value="fixed_amount">Nominal (Rp)</option>
                                                    </select>
                                                    <x-common.input-error for="manualDiscountType" />
                                                </div>
                                                <div>
                                                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Nilai</label>
                                                    @if ($manualDiscountType === 'fixed_amount')
                                                        <input 
                                                            x-data="currencyInput($wire.entangle('manualDiscountValue'))"
                                                            x-model="displayValue"
                                                            @input="handleInput"
                                                        type="text" 
                                                        inputmode="numeric"
                                                        aria-invalid="{{ $errors->has('manualDiscountValue') ? 'true' : 'false' }}"
                                                        aria-describedby="{{ $errors->has('manualDiscountValue') ? 'error-manualDiscountValue' : '' }}"
                                                        class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" 
                                                    />
                                                @else
                                                    <input wire:model.live="manualDiscountValue" type="number" min="0" aria-invalid="{{ $errors->has('manualDiscountValue') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('manualDiscountValue') ? 'error-manualDiscountValue' : '' }}" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                                                @endif
                                                <x-common.input-error for="manualDiscountValue" />
                                            </div>
                                            <div class="sm:col-span-2">
                                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Catatan (Opsional)</label>
                                                <textarea wire:model.live="manualDiscountNote" rows="2" aria-invalid="{{ $errors->has('manualDiscountNote') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('manualDiscountNote') ? 'error-manualDiscountNote' : '' }}" class="dark:bg-dark-900 shadow-theme-xs w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" placeholder="Contoh: kompensasi komplain"></textarea>
                                                <x-common.input-error for="manualDiscountNote" />
                                            </div>
                                        </div>
                                        @else
                                            <div class="mt-3 rounded-lg border border-gray-200 bg-white px-4 py-3 text-xs text-gray-600 dark:border-gray-800 dark:bg-white/[0.03] dark:text-gray-400">
                                                Anda tidak memiliki izin untuk memberikan diskon manual.
                                            </div>
                                        @endcan
                                        @if (($manualDiscountAmount ?? 0) > 0)
                                            <div class="mt-4 flex items-center justify-between rounded-xl bg-success-50 p-3 border border-success-100 dark:bg-success-900/20 dark:border-success-900/30">
                                                <div class="flex items-center gap-2">
                                                    <svg class="w-5 h-5 text-success-600 dark:text-success-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    <span class="text-sm font-semibold text-success-700 dark:text-success-300">Total Diskon</span>
                                                </div>
                                                <span class="text-sm font-bold text-success-700 dark:text-success-300">Rp {{ number_format((int) $manualDiscountAmount, 0, ',', '.') }}</span>
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                @if ($checkoutStep === 3)
                                    <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
                                        <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
                                            <p class="text-sm font-semibold text-gray-800 dark:text-white/90">Customer</p>
                                            <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                                <div>
                                                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Nama</label>
                                                    <input wire:model.live="customerName" type="text" aria-invalid="{{ $errors->has('customerName') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('customerName') ? 'error-customerName' : '' }}" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 disabled:opacity-50 disabled:cursor-not-allowed dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" placeholder="Walk-in" @disabled($cartLocked) />
                                                    <x-common.input-error for="customerName" />
                                                </div>
                                                <div>
                                                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Telepon (Opsional)</label>
                                                    <input wire:model.live="customerPhone" type="text" aria-invalid="{{ $errors->has('customerPhone') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('customerPhone') ? 'error-customerPhone' : '' }}" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 disabled:opacity-50 disabled:cursor-not-allowed dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" placeholder="08xxxx" @disabled($cartLocked) />
                                                    <x-common.input-error for="customerPhone" />
                                                </div>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                            <div class="sm:col-span-2">
                                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Metode Bayar</label>
                                                <div class="grid grid-cols-3 gap-3">
                                                    @foreach ($paymentMethods as $pm)
                                                        @continue(in_array($pm['id'], ['gofood', 'grab_food', 'shopee_food'], true))
                                                        <button 
                                                            type="button"
                                                            wire:click="$set('paymentMethod', '{{ $pm['id'] }}')"
                                                            class="flex min-h-[92px] flex-col items-center justify-center rounded-xl border p-3 text-center transition-all duration-200 hover:shadow-md
                                                            {{ $paymentMethod === $pm['id'] 
                                                                ? 'border-brand-500 bg-brand-50 text-brand-700 ring-2 ring-brand-500/20 dark:border-brand-400 dark:bg-brand-900/20 dark:text-brand-300' 
                                                                : 'border-gray-200 bg-white text-gray-600 hover:border-brand-300 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400 dark:hover:border-gray-600 dark:hover:bg-gray-800' 
                                                            }}"
                                                        >
                                                            <div class="mb-2 flex h-8 w-8 items-center justify-center rounded-full 
                                                                {{ $paymentMethod === $pm['id'] ? 'bg-brand-100 text-brand-600 dark:bg-brand-900/40 dark:text-brand-400' : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400' }}">
                                                                @if($pm['id'] === 'cash')
                                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                                                @elseif($pm['id'] === 'qris')
                                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                                                                @elseif(str_contains($pm['id'], 'food'))
                                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                                                                @else
                                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                                                                @endif
                                                            </div>
                                                            <span class="text-xs font-medium">{{ $pm['name'] }}</span>
                                                        </button>
                                                    @endforeach
                                                </div>
                                                <x-common.input-error for="paymentMethod" class="mt-2 text-center text-xs text-error-600" />
                                            </div>

                                            @if ($paymentMethod === 'cash')
                                                <div class="sm:col-span-2">
                                                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Uang Diterima</label>
                                                    <div class="relative flex h-16 w-full items-center rounded-lg border border-gray-300 bg-white dark:border-gray-700 dark:bg-gray-900">
                                                        <div class="flex h-full items-center border-r border-gray-200 px-4 text-lg font-semibold text-gray-600 dark:border-gray-700 dark:text-gray-300">
                                                            Rp
                                                        </div>
                                                        <input 
                                                            x-data="currencyInput($wire.entangle('cashReceived'))"
                                                            x-model="displayValue"
                                                            @input="handleInput"
                                                            type="text"
                                                            inputmode="numeric" 
                                                            aria-invalid="{{ $errors->has('cashReceived') ? 'true' : 'false' }}"
                                                            aria-describedby="{{ $errors->has('cashReceived') ? 'error-cashReceived' : '' }}"
                                                            class="h-full min-w-0 flex-1 bg-transparent px-4 py-3 text-2xl font-bold text-gray-900 outline-hidden dark:text-white" 
                                                            placeholder="0" 
                                                        />
                                                        <div class="flex h-full items-center border-l border-gray-200 px-4 text-sm font-medium text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                                            Pembayaran
                                                        </div>
                                                    </div>
                                                    <x-common.input-error for="cashReceived" />
                                                    <div class="mt-3 rounded-xl bg-gray-100 p-4 dark:bg-gray-800">
                                                        <div class="flex justify-between items-center">
                                                            <span class="text-sm text-gray-600 dark:text-gray-400">Total Tagihan</span>
                                                            <span class="text-sm font-semibold text-gray-900 dark:text-white">Rp {{ number_format($total, 0, ',', '.') }}</span>
                                                        </div>
                                                        <div class="mt-2 flex justify-between items-center pt-2 border-t border-gray-200 dark:border-gray-700">
                                                            <span class="text-base font-medium text-gray-800 dark:text-white/90">Kembalian</span>
                                                            <span class="text-xl font-bold {{ $cashChange >= 0 ? 'text-success-600 dark:text-success-400' : 'text-error-600 dark:text-error-400' }}">
                                                                Rp {{ number_format((int) $cashChange, 0, ',', '.') }}
                                                            </span>
                                                        </div>
                                                    </div>

                                                    <div class="mt-3 flex flex-wrap gap-2">
                                                        @foreach([20000, 50000, 100000, 200000] as $amt)
                                                            @if($amt >= $total)
                                                                <button 
                                                                    type="button"
                                                                    wire:click="$set('cashReceived', '{{ $amt }}')"
                                                                    class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                                                                >
                                                                    Rp {{ number_format($amt, 0, ',', '.') }}
                                                                </button>
                                                            @endif
                                                        @endforeach
                                                        <button 
                                                            type="button"
                                                            wire:click="$set('cashReceived', '{{ $total }}')"
                                                            class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                                                        >
                                                            Uang Pas
                                                        </button>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <div class="border-t border-gray-200 bg-white px-6 py-4 dark:border-gray-800 dark:bg-gray-900">
                                <div class="flex flex-col-reverse items-stretch justify-between gap-2 sm:flex-row sm:items-center">
                                    <button type="button" wire:click="$set('checkoutModalOpen', false)" class="shadow-theme-xs inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                                        Batal
                                    </button>

                                    <button type="button" wire:click="checkout" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex h-11 items-center justify-center rounded-lg px-4 text-sm font-semibold text-white transition">
                                        Simpan Transaksi
                                    </button>
                                </div>
                            </div>
                        
                </div>
            </div>
        </div>
    @endif

</div>
