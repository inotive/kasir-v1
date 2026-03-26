<div>
    <div class="space-y-3">
        @foreach ($items as $index => $item)
            <div wire:key="item-{{ $index }}" class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
                @php
                    $packageContents = $packageContentsByProductId[(int) ($item['id'] ?? 0)] ?? [];
                    $hasDiscount = isset($item["price_afterdiscount"]) && (int) $item["price_afterdiscount"] > 0 && (int) $item["price_afterdiscount"] < (int) $item["price"];
                    $displayPrice = $hasDiscount ? (int) $item["price_afterdiscount"] : (int) $item["price"];
                    $qty = (int) ($item['quantity'] ?? 0);
                    $lineTotal = max(0, $displayPrice) * max(0, $qty);
                @endphp

                <div class="flex items-start gap-3">
                    <img
                        src="{{ Storage::url($item["image"]) }}"
                        alt="{{ $item["name"] }}"
                        class="h-16 w-16 shrink-0 rounded-xl object-cover"
                    />

                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold text-gray-900">{{ $item["name"] }}</p>
                                @if ($packageContents !== [])
                                    <div class="mt-1 flex flex-wrap gap-1.5 text-[11px] text-gray-600">
                                        @foreach ($packageContents as $row)
                                            @php
                                                $packageQty = (int) ($row['quantity'] ?? 0) * (int) ($item['quantity'] ?? 0);
                                                $productName = (string) ($row['product_name'] ?? '');
                                                $variantName = (string) ($row['variant_name'] ?? '');
                                            @endphp
                                            <span class="rounded-full border border-gray-200 bg-gray-50 px-2 py-0.5">
                                                {{ $packageQty }}x {{ $productName }}@if ($variantName !== '') ({{ $variantName }})@endif
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <button
                                type="button"
                                aria-label="Hapus item"
                                class="grid h-9 w-9 shrink-0 place-content-center rounded-xl bg-red-50 text-red-600 transition-colors hover:bg-red-100"
                                wire:click="$parent.removeItem({{ $index }})"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>

                        <div class="mt-2 flex items-end justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-[11px] text-gray-500">Harga</p>
                                <div class="flex items-baseline gap-2">
                                    <span class="text-sm font-bold text-primary-70">
                                        Rp{{ number_format($displayPrice, 0, ",", ".") }}
                                    </span>
                                    @if ($hasDiscount)
                                        <span class="text-[11px] text-gray-400 line-through">
                                            Rp{{ number_format($item["price"], 0, ",", ".") }}
                                        </span>
                                    @endif
                                </div>
                                <p class="mt-0.5 text-[11px] text-gray-500">
                                    Total: <span class="font-semibold text-gray-700">Rp{{ number_format($lineTotal, 0, ",", ".") }}</span>
                                </p>
                            </div>

                            <div class="flex items-center gap-2 rounded-xl bg-gray-100 p-1">
                                <button
                                    type="button"
                                    aria-label="Kurangi jumlah"
                                    class="grid h-9 w-9 place-content-center rounded-lg bg-white text-gray-700 shadow-sm transition hover:bg-gray-50"
                                    wire:click="$parent.decrement({{ $index }})"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                    </svg>
                                </button>

                                <span class="w-8 text-center text-sm font-bold text-gray-900">{{ $qty }}</span>

                                <button
                                    type="button"
                                    aria-label="Tambah jumlah"
                                    class="grid h-9 w-9 place-content-center rounded-lg bg-white text-gray-700 shadow-sm transition hover:bg-gray-50"
                                    wire:click="$parent.increment({{ $index }})"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="sr-only" for="cart-item-note-{{ $index }}">Catatan</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.536L16.732 3.732z"></path>
                                    </svg>
                                </span>
                                <input
                                    id="cart-item-note-{{ $index }}"
                                    type="text"
                                    wire:model="$parent.cartItems.{{ $index }}.note"
                                    placeholder="Tambah catatan (opsional)"
                                    class="w-full rounded-xl border border-gray-200 bg-gray-50 pl-10 pr-3 py-2.5 text-xs text-gray-800 placeholder:text-gray-400 focus:border-primary-60 focus:ring-1 focus:ring-primary-60"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
