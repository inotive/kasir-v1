@props([
    'title' => 'Pilih Varian',
    'variantOptions' => [],
    'variantQuantities' => [],
])

<div x-data="{ 
        open: false, 
        step: 'select', 
        selectedVariant: null,
        quantity: 1,
        variantQuantities: {},
        isPackage: false,
        productName: '',
        packageContents: [],
        addonGroups: [],
        allAddons: [],
        selectedAddonsWithQty: [],
        
        selectVariant(variant) {
            this.selectedVariant = variant;
            this.quantity = this.variantQuantities[variant.id] || 1;
            this.selectedAddonsWithQty = [];
            this.$nextTick(() => {
                if (this.allAddons.length > 0) {
                    this.step = 'addons';
                } else {
                    this.step = 'quantity';
                }
            });
        },

        addAddonToSelection(addonId) {
            const existing = this.selectedAddonsWithQty.find(a => a.id === addonId);
            if (existing) {
                existing.quantity = (existing.quantity || 1) + 1;
            } else {
                this.selectedAddonsWithQty.push({ id: addonId, quantity: 1 });
            }
        },

        updateAddonQty(addonId, qty) {
            if (qty <= 0) {
                this.selectedAddonsWithQty = this.selectedAddonsWithQty.filter(a => a.id !== addonId);
                return;
            }
            const existing = this.selectedAddonsWithQty.find(a => a.id === addonId);
            if (existing) {
                existing.quantity = qty;
            }
        },

        removeAddon(addonId) {
            this.selectedAddonsWithQty = this.selectedAddonsWithQty.filter(a => a.id !== addonId);
        },

        goToQuantity() {
            this.step = 'quantity';
        },

        reset() {
            this.step = 'select';
            this.selectedVariant = null;
            this.quantity = 1;
            this.selectedAddonsWithQty = [];
        }
     }" 
     @open-variant-modal.window="
        open = true; 
        reset();
        isPackage = !!$event.detail.isPackage;
        productName = $event.detail.productName || '';
        packageContents = Array.isArray($event.detail.packageContents) ? $event.detail.packageContents : [];
        addonGroups = Array.isArray($event.detail.addonGroups) ? $event.detail.addonGroups : [];
        allAddons = Array.isArray($event.detail.allAddons) ? $event.detail.allAddons : [];
        if ($event.detail.quantities) {
            variantQuantities = $event.detail.quantities;
        }
        if ($event.detail.selectedVariant) {
            selectVariant($event.detail.selectedVariant);
        }
     "
     @close-modal.window="open = false"
     @keydown.escape.window="open = false">
    <div class="fixed inset-0 z-50 flex items-end bg-black/50" x-show="open" aria-hidden="true" @click="open = false"></div>

    <div
        class="fixed inset-x-0 bottom-0 z-50 w-full max-w-md mx-auto"
        x-show="open"
    >
        <div class="rounded-t-3xl bg-white border border-gray-200 border-b-0 shadow-sm font-poppins">
            <div class="flex items-center justify-between p-4 border-b border-gray-200" style="display: none;">
                <div class="flex items-center gap-3">
                    <button x-show="step === 'quantity' || step === 'addons'" @click="step = (step === 'quantity' && allAddons.length > 0) ? 'addons' : 'select'" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                    </button>
                    <h3 class="text-lg font-bold text-gray-900" x-text="step === 'select' ? '{{ $title }}' : (step === 'addons' ? 'Pilih Add-on' : 'Atur Jumlah')"></h3>
                </div>
                <button @click="open = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            {{-- Step 1: Variant Selection --}}
            <div x-show="step === 'select'" class="p-4 space-y-3 max-h-[60vh] overflow-y-auto" style="display: none;">
                <div x-show="isPackage && packageContents.length" class="rounded-xl border border-gray-200 bg-gray-50 p-3">
                    <div class="text-xs font-bold text-gray-900">Isi Paket</div>
                    <div class="mt-2 space-y-1 text-xs text-gray-700">
                        <template x-for="(row, idx) in packageContents" :key="idx">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <span class="font-semibold" x-text="row.product_name"></span>
                                    <span class="text-gray-500" x-show="row.variant_name" x-text="' (' + row.variant_name + ')'"></span>
                                </div>
                                <div class="shrink-0 font-semibold" x-text="row.quantity + 'x'"></div>
                            </div>
                        </template>
                    </div>
                </div>

                @forelse ($variantOptions as $v)
                    @php
                        $vp = (int) ($v['price'] ?? 0);
                        $pct = (int) ($v['percent'] ?? 0);
                        $computed = ($vp > 0 && $pct > 0) ? max(0, (int) round($vp - ($vp * ($pct / 100)))) : null;
                        $fallback = (int) ($v['price_afterdiscount'] ?? 0);
                        $discounted = $computed ?? (($fallback > 0 && $fallback < $vp) ? $fallback : null);
                        $isPromo = ($vp > 0 && !is_null($discounted) && $discounted < $vp);
                        
                        $variantData = $v;
                        $variantData['isPromo'] = $isPromo;
                        $variantData['discounted'] = $discounted;
                    @endphp
                    <div @click="selectVariant(@js($variantData))"
                         class="flex items-center justify-between rounded-xl border p-3 text-sm font-semibold cursor-pointer
                                bg-gray-50 text-gray-800 border-gray-200 hover:border-primary-60">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="font-bold">{{ $v['name'] }}</span>
                                @if($isPromo && $pct > 0)
                                    <span class="text-[10px] font-bold text-red-600 bg-red-100 px-2 py-0.5 rounded-full">-{{ $pct }}%</span>
                                @endif
                            </div>
                            <div class="flex items-baseline gap-2 mt-1">
                                @if($isPromo)
                                    <span class="text-primary-60 font-bold">Rp {{ number_format($discounted, 0, ',', '.') }}</span>
                                    <span class="text-gray-400 line-through text-xs">Rp {{ number_format($vp, 0, ',', '.') }}</span>
                                @else
                                    <span class="text-primary-60 font-bold">Rp {{ number_format($vp, 0, ',', '.') }}</span>
                                @endif
                            </div>
                        </div>
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </div>
                @empty
                    <div class="text-center py-10 text-sm text-gray-500">
                        <p>Belum ada varian untuk produk ini.</p>
                    </div>
                @endforelse
            </div>

            {{-- Step 2: Addon Selection --}}
            <div x-show="step === 'addons'" class="p-4 space-y-4 max-h-[60vh] overflow-y-auto" style="display: none;">
                <template x-if="selectedVariant">
                    <div>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 mb-4">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-sm text-gray-900" x-text="selectedVariant.name"></span>
                                <template x-if="selectedVariant.isPromo">
                                    <span class="text-sm font-bold text-primary-60" x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(selectedVariant.discounted)"></span>
                                </template>
                                <template x-if="!selectedVariant.isPromo">
                                    <span class="text-sm font-bold text-primary-60" x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(selectedVariant.price)"></span>
                                </template>
                            </div>
                        </div>

                        {{-- Dropdown with search inside --}}
                        <div class="relative" x-data="{ dropdownOpen: false, dropdownSearch: '', dropdownLimit: 10 }" @click.away="dropdownOpen = false">
                            <button type="button" @click="dropdownOpen = !dropdownOpen; if(dropdownOpen){ $nextTick(() => { $el.nextElementSibling.querySelector('input')?.focus(); }) }" class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-left text-sm flex items-center justify-between hover:border-gray-300">
                                <span x-show="selectedAddonsWithQty.length === 0" class="text-gray-400">Pilih add-on...</span>
                                <span x-show="selectedAddonsWithQty.length > 0" class="font-medium text-gray-800" x-text="selectedAddonsWithQty.length + ' add-on dipilih'"></span>
                                <svg class="h-4 w-4 text-gray-400 transition" :class="dropdownOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>

                            <div x-show="dropdownOpen"
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0"
                                x-transition:enter-end="opacity-100"
                                x-transition:leave="transition ease-in duration-100"
                                x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0"
                                class="mt-1 rounded-xl border border-gray-200 bg-white shadow-theme-lg">
                                <div class="p-2 border-b border-gray-100">
                                    <div class="relative">
                                        <input type="text"
                                            x-model="dropdownSearch"
                                            @input="dropdownLimit = 10"
                                            @click.stop
                                            placeholder="Cari add-on..."
                                            class="w-full rounded-lg border border-gray-200 bg-gray-50 py-2 pl-8 pr-3 text-sm text-gray-800 placeholder-gray-400 focus:border-primary-60 focus:outline-none focus:ring-1 focus:ring-primary-60">
                                        <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                    </div>
                                </div>
                                <div class="max-h-48 overflow-y-auto">
                                    <template x-for="addon in (() => {
                                        let list = allAddons;
                                        if (dropdownSearch.trim() !== '') {
                                            const q = dropdownSearch.toLowerCase();
                                            list = list.filter(a => a.name.toLowerCase().includes(q));
                                        }
                                        return list.slice(0, dropdownLimit);
                                    })()" :key="addon.id">
                                        <button type="button"
                                            @click="addAddonToSelection(addon.id); dropdownOpen = false"
                                            class="flex w-full items-center justify-between px-4 py-2.5 text-left text-sm transition hover:bg-gray-50"
                                            :class="selectedAddonsWithQty.some(a => a.id === addon.id) ? 'bg-primary-50' : ''">
                                            <div class="min-w-0 flex-1">
                                                <span class="font-medium text-gray-800" x-text="addon.name"></span>
                                                <span class="ml-1 text-[10px] text-gray-400" x-text="addon.category"></span>
                                            </div>
                                            <div class="flex items-center gap-2 shrink-0">
                                                <span class="text-xs font-semibold text-gray-500" x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(addon.price)"></span>
                                                <svg x-show="selectedAddonsWithQty.some(a => a.id === addon.id)" class="h-4 w-4 text-primary-60" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                                            </div>
                                        </button>
                                    </template>

                                    <template x-if="(() => {
                                        let list = allAddons;
                                        if (dropdownSearch.trim() !== '') {
                                            const q = dropdownSearch.toLowerCase();
                                            list = list.filter(a => a.name.toLowerCase().includes(q));
                                        }
                                        return list.length > dropdownLimit;
                                    })()">
                                        <button @click="dropdownLimit += 10" class="w-full border-t border-gray-100 py-2.5 text-center text-xs font-medium text-gray-500 hover:text-primary-60">
                                            Muat lebih banyak...
                                        </button>
                                    </template>

                                    <template x-if="(() => {
                                        let list = allAddons;
                                        if (dropdownSearch.trim() !== '') {
                                            const q = dropdownSearch.toLowerCase();
                                            list = list.filter(a => a.name.toLowerCase().includes(q));
                                        }
                                        return list.length === 0;
                                    })() && dropdownSearch.trim() !== ''">
                                        <p class="px-4 py-6 text-center text-sm text-gray-500">Tidak ada add-on ditemukan.</p>
                                    </template>
                                </div>
                            </div>
                        </div>

                        {{-- Selected addon cards (flex column) with quantity controls --}}
                        <template x-if="selectedAddonsWithQty.length > 0">
                            <div class="flex flex-col gap-2">
                                <template x-for="sa in selectedAddonsWithQty" :key="sa.id">
                                    <template x-for="addon in allAddons.filter(a => a.id === sa.id)" :key="addon.id">
                                        <div class="flex items-center justify-between rounded-xl border border-gray-200 bg-white px-3 py-2.5">
                                            <div class="flex items-center gap-2 min-w-0">
                                                <button @click="removeAddon(addon.id)" class="shrink-0 h-5 w-5 rounded-full bg-red-50 flex items-center justify-center hover:bg-red-100">
                                                    <svg class="h-3 w-3 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                </button>
                                                <span class="font-medium text-sm text-gray-800" x-text="addon.name"></span>
                                            </div>
                                            <div class="flex items-center gap-3">
                                                <div class="flex items-center gap-1.5">
                                                    <button @click="updateAddonQty(addon.id, (sa.quantity || 1) - 1)" class="h-6 w-6 flex items-center justify-center rounded-full bg-gray-100 hover:bg-gray-200 text-gray-600 text-xs font-bold">-</button>
                                                    <span class="w-5 text-center text-sm font-semibold text-gray-800" x-text="sa.quantity || 1"></span>
                                                    <button @click="updateAddonQty(addon.id, (sa.quantity || 1) + 1)" class="h-6 w-6 flex items-center justify-center rounded-full bg-gray-100 hover:bg-gray-200 text-gray-600 text-xs font-bold">+</button>
                                                </div>
                                                <span class="text-sm font-semibold text-gray-600 min-w-[60px] text-right" x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(addon.price * (sa.quantity || 1))"></span>
                                            </div>
                                        </div>
                                    </template>
                                </template>
                            </div>
                        </template>

                        <button @click="goToQuantity()" class="w-full rounded-lg bg-primary-60 hover:bg-primary-70 text-white py-3 font-bold mt-4">
                            Lanjut
                        </button>
                    </div>
                </template>
            </div>

            {{-- Step 3: Quantity Selection --}}
            <div x-show="step === 'quantity'" class="p-4 max-h-[60vh] overflow-y-auto" style="display: none;">
                <template x-if="selectedVariant">
                    <div class="space-y-4">
                        <div x-show="isPackage && packageContents.length" class="rounded-xl border border-gray-200 bg-gray-50 p-3">
                            <div class="text-xs font-bold text-gray-900">Isi Paket</div>
                            <div class="mt-2 space-y-1 text-xs text-gray-700">
                                <template x-for="(row, idx) in packageContents" :key="idx">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <span class="font-semibold" x-text="row.product_name"></span>
                                            <span class="text-gray-500" x-show="row.variant_name" x-text="' (' + row.variant_name + ')'"></span>
                                        </div>
                                        <div class="shrink-0 font-semibold" x-text="(row.quantity * quantity) + 'x'"></div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="flex items-center justify-between rounded-xl border p-3 text-sm font-semibold bg-gray-50 text-gray-800 border-gray-200">
                            <div class="flex-1 min-w-0">
                                <span class="font-bold" x-text="selectedVariant.name"></span>
                                <div class="flex items-baseline gap-2 mt-1">
                                    <template x-if="selectedVariant.isPromo">
                                        <div>
                                            <span class="text-primary-60 font-bold" x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(selectedVariant.discounted)"></span>
                                            <span class="text-gray-400 line-through text-xs" x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(selectedVariant.price)"></span>
                                        </div>
                                    </template>
                                    <template x-if="!selectedVariant.isPromo">
                                        <span class="text-primary-60 font-bold" x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(selectedVariant.price)"></span>
                                    </template>
                                </div>
                            </div>
                        </div>

                        {{-- Selected addons summary --}}
                        <template x-if="selectedAddonsWithQty.length > 0">
                            <div class="rounded-xl border border-gray-200 bg-gray-50 p-3">
                                <div class="text-xs font-bold text-gray-900 mb-2">Add-on</div>
                                <template x-for="sa in selectedAddonsWithQty" :key="sa.id">
                                    <template x-for="addon in allAddons.filter(a => a.id === sa.id)" :key="addon.id">
                                        <div class="flex items-center justify-between py-1 text-xs">
                                            <span class="text-gray-700" x-text="addon.name + ' (' + (sa.quantity || 1) + 'x)'"></span>
                                            <span class="font-semibold text-gray-600" x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(addon.price * (sa.quantity || 1))"></span>
                                        </div>
                                    </template>
                                </template>
                            </div>
                        </template>

                        <div class="flex items-center justify-center gap-4 py-4">
                            <button
                                @click="quantity = Math.max(1, quantity - 1)"
                                class="w-12 h-12 flex items-center justify-center bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-full">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path></svg>
                            </button>
                            <span class="w-16 text-center text-3xl font-bold text-gray-900" x-text="quantity"></span>
                            <button
                                @click="quantity++"
                                class="w-12 h-12 flex items-center justify-center bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-full">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            </button>
                        </div>
                        
                        <button
                            @click="$wire.addVariantToCart(selectedVariant.id, quantity, selectedAddonsWithQty); open = false"
                            class="w-full rounded-lg bg-primary-60 hover:bg-primary-70 text-white py-3 font-bold flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3zM16 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM6.5 18a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"></path></svg>
                            <span>Tambah ke Keranjang</span>
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
