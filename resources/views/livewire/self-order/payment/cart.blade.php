<div x-data="{ open: false }" class="mx-auto flex min-h-screen w-full max-w-md flex-col bg-gray-50 pb-44 font-poppins">
    <livewire:self-order.components.page-title-nav :title="'Keranjang'" :hasBack="true" :hasFilter="false" />

    <div class="w-full px-4 mt-4">
        @if (isset($cartItems) && count($cartItems) > 0)
                 <livewire:self-order.components.menu-item-list
                    :items="$cartItems"
                    wire:key="{{ str()->random(50) }}"
                />

            <!-- Action Area (Fixed Bottom) -->
            <div class="fixed mx-auto max-w-md bottom-0 left-0 right-0 z-40 border-t border-gray-100 bg-white p-4 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.1)]">
                <div class="mx-auto max-w-md w-full space-y-3 pb-[env(safe-area-inset-bottom)]">
                    <x-common.input-error :for="'cartItems'" class="text-xs text-error-600" />
                    
                    <button
                        x-bind:disabled="{{ count($cartItems) }} === 0"
                        wire:click="checkout"
                        class="w-full flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-primary-60 to-primary-70 hover:from-primary-70 hover:to-primary-80 text-white font-bold py-3.5 shadow-lg shadow-primary-60/30 transition-all active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span>Pesan Sekarang</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </button>

                    <!-- Secondary Actions -->
                    <div class="grid grid-cols-1 gap-3">
                         <button x-on:click="open = true" class="flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border border-red-100 text-red-500 font-semibold text-sm hover:bg-red-50 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            Hapus Semua
                        </button>
                    </div>
                </div>
            </div>

        @else
            <!-- Empty State -->
            <div class="flex flex-col items-center justify-center pt-10 px-6">
                <div class="w-64 h-64 mb-6 relative">
                     <img
                        src="{{ asset("assets/images/bg-cart-empty.png") }}"
                        alt="Tidak ada data"
                        class="relative w-full h-full object-contain"
                    />
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">
                    Keranjang Kosong
                </h3>
                <p class="text-gray-500 text-center mb-8 max-w-xs">
                    Sepertinya kamu belum memilih menu apapun. Yuk cari menu favoritmu!
                </p>
                
                <a href="{{ route('self-order.home') }}" 
                   class="flex items-center gap-2 px-8 py-3 bg-primary-60 hover:bg-primary-70 text-white rounded-full font-semibold shadow-lg shadow-primary-60/30 transition-all hover:-translate-y-1">
                    Mulai Pesan
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                </a>
            </div>
        @endif
    </div>

    <livewire:self-order.components.delete-confirm-modal />
</div>
