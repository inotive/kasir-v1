<div class="fixed bottom-0 inset-x-0 z-30 w-full max-w-md mx-auto p-4">
    @if(($count ?? 0) > 0)
        <a
            href="{{ route('self-order.payment.cart') }}"
            wire:navigate
            class="flex items-center justify-between rounded-xl bg-primary-60 text-white px-4 py-3"
            aria-label="Buka Keranjang"
            title="Buka Keranjang"
        >
            <div class="min-w-0">
                <div class="text-[10px] text-white/80">Keranjang</div>
                <div class="text-sm font-bold">{{ $count }} item</div>
            </div>
            <div class="text-right">
                <div class="text-[10px] text-white/80">Subtotal</div>
                <div class="text-sm font-bold">Rp {{ number_format($totalPrice ?? 0, 0, ',', '.') }}</div>
            </div>
        </a>
    @endif
</div>
