<div class="min-h-screen bg-gray-50 font-poppins">
    <header class="bg-white border-b border-gray-200">
        <div class="px-4 py-4 space-y-3">
            <div class="flex items-center gap-3">
                <div class="bg-white p-2 rounded-xl border border-gray-200">
                    <img src="{{ asset('storage/' . $setting->store_logo ?? 'logo.png') }}" alt="{{ $setting->store_name }}" class="w-8 h-8">
                </div>
                <div class="min-w-0">
                    <div class="text-sm font-bold text-gray-900 truncate">{{ $setting->store_name }}</div>
                    <div class="text-xs text-gray-500 truncate">Self Order</div>
                </div>
                <div class="ml-auto flex items-center gap-5">
                    <div class="text-right">
                        <div class="text-[10px] text-gray-500">Meja</div>
                        <div class="text-lg font-bold text-primary-60">#{{ $tableNumber ?? '' }}</div>
                    </div>
                    @if ((string) session('customer_type') === 'member' && is_numeric(session('member_id')))
                        <a
                            wire:navigate
                            href="{{ route('self-order.member.account') }}"
                            class="rounded-xl border border-gray-200 bg-white p-2 hover:bg-gray-50">
                            <svg class="w-5 h-5 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </a>
                    @endif
                </div>
            </div>

            <div class="relative">
                <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input
                    wire:model.live="term"
                    type="text"
                    placeholder="Cari menu..."
                    class="w-full pl-11 pr-10 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary-60" />
                <button
                    x-show="$wire.term && $wire.term.length > 0"
                    x-transition
                    wire:click="$set('term', '')"
                    class="absolute inset-y-0 right-2 flex items-center text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
    </header>

    <main class="px-4 py-4 pb-24 space-y-6">
        @if (trim($term))
            @if ($searchResult->isEmpty())
                <div class="text-center py-12">
                    <div class="text-sm font-semibold text-gray-900">Menu tidak ditemukan</div>
                    <div class="text-xs text-gray-500 mt-1">Coba kata kunci lain</div>
                </div>
            @else
                <div class="space-y-3">
                    <div>
                        <div class="text-sm font-bold text-gray-900">Hasil Pencarian</div>
                        <div class="text-xs text-gray-500">{{ $searchResult->count() }} menu ditemukan</div>
                    </div>
                    <div class="space-y-3">
                        @foreach ($searchResult as $result)
                            <livewire:self-order.components.food-card
                                wire:key="{{ 'search-'.$result->id }}"
                                :data="$result"
                                :isGrid="false" />
                        @endforeach
                    </div>
                </div>
            @endif
        @else
            <div class="space-y-4">
                @if (is_null($activeCategoryId))
                    <div class="space-y-6">
                        @if (($promoFoods ?? collect())->count() > 0)
                            <section class="space-y-3">
                                <div class="flex items-end justify-between">
                                    <div>
                                        <div class="text-sm font-bold text-gray-900">Promo</div>
                                        <div class="text-xs text-gray-500">Diskon & penawaran</div>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    @foreach (($promoFoods ?? collect()) as $food)
                                        <livewire:self-order.components.food-card
                                            wire:key="{{ 'promo-food-'.$food->id }}"
                                            :data="$food"
                                            :isGrid="true" />
                                    @endforeach
                                </div>
                            </section>
                        @endif

                        @if (($favoriteFoods ?? collect())->count() > 0)
                            <section class="space-y-3">
                                <div class="flex items-end justify-between">
                                    <div>
                                        <div class="text-sm font-bold text-gray-900">Favorit</div>
                                        <div class="text-xs text-gray-500">Rekomendasi pilihan</div>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    @foreach (($favoriteFoods ?? collect()) as $food)
                                        <livewire:self-order.components.food-card
                                            wire:key="{{ 'favorite-food-'.$food->id }}"
                                            :data="$food"
                                            :isGrid="true" />
                                    @endforeach
                                </div>
                            </section>
                        @endif
                    </div>
                @endif

                <div class="-mx-4 px-4">
                    <div class="flex gap-2 overflow-x-auto pb-1 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                        <button
                            type="button"
                            wire:key="category-pill-all"
                            wire:click="selectCategory"
                            @class([
                                'shrink-0 rounded-full px-4 py-2 text-sm font-semibold border transition-colors',
                                'bg-primary-60 text-white border-primary-60' => is_null($activeCategoryId),
                                'bg-white text-gray-700 border-gray-200 hover:bg-gray-50' => ! is_null($activeCategoryId),
                            ])>
                            Semua
                        </button>
                        @foreach ($categories as $category)
                            <button
                                type="button"
                                wire:key="category-pill-{{ $category->id }}"
                                wire:click="selectCategory({{ (int) $category->id }})"
                                @class([
                                    'shrink-0 rounded-full px-4 py-2 text-sm font-semibold border transition-colors',
                                    'bg-primary-60 text-white border-primary-60' => ! is_null($activeCategoryId) && (int) $activeCategoryId === (int) $category->id,
                                    'bg-white text-gray-700 border-gray-200 hover:bg-gray-50' => is_null($activeCategoryId) || (int) $activeCategoryId !== (int) $category->id,
                                ])>
                                {{ $category->name }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="space-y-6">
                    @php
                        $visibleCategories = $categories;
                        $hasVisibleFoods = false;
                        if (! is_null($activeCategoryId)) {
                            $visibleCategories = $categories->where('id', $activeCategoryId);
                        }
                    @endphp

                    @forelse ($visibleCategories as $category)
                        @php $foodsInCategory = collect($allFoods ?? [])->where('category_id', $category->id); @endphp
                        @if ($foodsInCategory->count() > 0)
                            @php $hasVisibleFoods = true; @endphp
                            <section class="space-y-3">
                                <div class="text-sm font-bold text-gray-900">{{ $category->name }}</div>
                                <div class="space-y-3">
                                    @foreach ($foodsInCategory as $food)
                                        <livewire:self-order.components.food-card
                                            wire:key="{{ 'cat-'.$category->id.'-food-'.$food->id }}"
                                            :data="$food"
                                            :isGrid="false" />
                                    @endforeach
                                </div>
                            </section>
                        @endif
                    @empty
                        <div class="text-center py-12">
                            <div class="text-sm font-semibold text-gray-900">Kategori tidak ditemukan</div>
                            <div class="text-xs text-gray-500 mt-1">Silakan pilih kategori lain</div>
                        </div>
                    @endforelse

                    @if ($visibleCategories->isNotEmpty() && ! $hasVisibleFoods)
                        <div class="text-center py-12">
                            <div class="text-sm font-semibold text-gray-900">Belum ada menu di kategori ini</div>
                            <div class="text-xs text-gray-500 mt-1">Silakan pilih kategori lain</div>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </main>

    <x-variant-modal :variantOptions="$variantOptions" :variantQuantities="$variantQuantities" />
    <livewire:self-order.components.cart-badge />
</div>
