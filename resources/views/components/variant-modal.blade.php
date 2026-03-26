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
        
        selectVariant(variant) {
            this.selectedVariant = variant;
            this.quantity = this.variantQuantities[variant.id] || 1;
            this.$nextTick(() => {
                this.step = 'quantity';
            });
        },

        reset() {
            this.step = 'select';
            this.selectedVariant = null;
            this.quantity = 1;
        }
     }" 
     @open-variant-modal.window="
        open = true; 
        reset();
        isPackage = !!$event.detail.isPackage;
        productName = $event.detail.productName || '';
        packageContents = Array.isArray($event.detail.packageContents) ? $event.detail.packageContents : [];
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
                    <button x-show="step === 'quantity'" @click="step = 'select'" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                    </button>
                    <h3 class="text-lg font-bold text-gray-900" x-text="step === 'select' ? '{{ $title }}' : 'Atur Jumlah'"></h3>
                </div>
                <button @click="open = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

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
                            @click="$wire.addVariantToCart(selectedVariant.id, quantity); open = false"
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
