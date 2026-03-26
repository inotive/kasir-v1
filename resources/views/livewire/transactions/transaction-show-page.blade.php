@php
    $customerName = (string) ($transaction->member?->name ?? $transaction->name ?? '-');
    $orderType = (string) ($transaction->order_type ?? '');
    $paymentMethodKey = (string) ($transaction->payment_method ?? '');
    $paymentMethodLabel = \App\Helpers\DataLabelHelper::enum($paymentMethodKey !== '' ? $paymentMethodKey : null, 'payment_method');
    $paymentStatusKey = (string) ($transaction->payment_status ?? '');
    $paymentStatusLabel = \App\Helpers\DataLabelHelper::enum($paymentStatusKey !== '' ? $paymentStatusKey : null, 'payment_status');
    $inventoryApplied = $transaction->inventory_applied_at !== null;
    $user = auth()->user();
    $voidQuickMaxCount = (int) ($correctionRules['void_quick_max_count_per_day'] ?? 0);
    $voidWindowMinutes = (int) ($correctionRules['void_quick_window_minutes'] ?? 0);
    $voidQuickUsedToday = (int) ($voidQuickUsedToday ?? 0);
    $refundQuickMaxAmount = (int) ($correctionRules['refund_quick_max_amount'] ?? 0);
    $refundQuickMaxCount = (int) ($correctionRules['refund_quick_max_count_per_day'] ?? 0);
    $refundQuickUsedToday = (int) ($refundQuickUsedToday ?? 0);
    $voidNeedsApproval = (bool) ($voidNeedsApproval ?? false);
    $refundNeedsApproval = (bool) ($refundNeedsApproval ?? false);
    $fmtCurrency = fn ($value) => 'Rp'.number_format((float) $value, 0, ',', '.');

    $voucherDiscount = (int) ($transaction->voucher_discount_amount ?? 0);
    $manualDiscount = (int) ($transaction->manual_discount_amount ?? 0);
    $pointDiscount = (int) ($transaction->point_discount_amount ?? 0);
    $discountTotal = (int) ($transaction->discount_total_amount ?? ($voucherDiscount + $manualDiscount + $pointDiscount));
    $netSubtotal = max(0, (int) ($transaction->subtotal ?? 0) - $discountTotal);

    $displayItems = $transaction->transactionItems->whereNull('parent_transaction_item_id')->values();
@endphp

<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-3">
                <a href="{{ route('transactions.index') }}" wire:navigate class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                    Kembali
                </a>
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ $transaction->code }}</h2>
            </div>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                {{ optional($transaction->created_at)->format('d M Y, H:i') }} · {{ $paymentMethodLabel }} · {{ $paymentStatusLabel }}
            </p>
        </div>
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            @can('transactions.print')
                <button type="button" wire:click="printReceipt" class="shadow-theme-xs inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                    Cetak Struk
                </button>
            @endcan
            @if ($paymentStatusKey === 'pending')
                @can('transactions.void')
                    <button type="button" wire:click="openVoidModal" class="shadow-theme-xs inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                        Void
                    </button>
                @endcan
            @endif
            @if (in_array($paymentStatusKey, ['paid', 'partial_refund'], true))
                @can('transactions.refund')
                    <button type="button" wire:click="openRefundModal" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex h-11 items-center justify-center rounded-lg px-4 text-sm font-semibold text-white transition">
                        Refund
                    </button>
                @endcan
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4 md:gap-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 md:p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-theme-sm text-gray-500 dark:text-gray-400">Subtotal</p>
            <h4 class="mt-3 text-2xl font-bold text-gray-800 dark:text-white/90">{{ $fmtCurrency((int) $transaction->subtotal) }}</h4>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 md:p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-theme-sm text-gray-500 dark:text-gray-400">Total Diskon</p>
            <h4 class="mt-3 text-2xl font-bold text-gray-800 dark:text-white/90">{{ $discountTotal > 0 ? '-'.$fmtCurrency($discountTotal) : '-' }}</h4>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 md:p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-theme-sm text-gray-500 dark:text-gray-400">Pajak PB1</p>
            <h4 class="mt-3 text-2xl font-bold text-gray-800 dark:text-white/90">{{ $fmtCurrency((int) ($transaction->tax_amount ?? 0)) }}</h4>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 md:p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-theme-sm text-gray-500 dark:text-gray-400">Total</p>
            <h4 class="mt-3 text-2xl font-bold text-gray-800 dark:text-white/90">{{ $fmtCurrency((int) $transaction->total) }}</h4>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] lg:col-span-1">
            <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Ringkasan</h3>

            <dl class="mt-4 space-y-3">
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Pelanggan</dt>
                    <dd class="text-sm font-medium text-gray-800 dark:text-white/90 text-right">{{ $customerName }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Tipe Pesanan</dt>
                    <dd class="text-sm font-medium text-gray-800 dark:text-white/90 text-right">{{ $orderType === 'dine_in' ? 'Dine in' : 'Take away' }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Meja</dt>
                    <dd class="text-sm font-medium text-gray-800 dark:text-white/90 text-right">{{ $transaction->diningTable?->name ?? '-' }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Metode</dt>
                    <dd class="text-sm font-medium text-gray-800 dark:text-white/90 text-right">{{ $paymentMethodLabel }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Status</dt>
                    <dd class="text-sm font-medium text-gray-800 dark:text-white/90 text-right">{{ $paymentStatusLabel }}</dd>
                </div>
                @if ($discountTotal > 0)
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Subtotal Bersih</dt>
                        <dd class="text-sm font-medium text-gray-800 dark:text-white/90 text-right">{{ $fmtCurrency($netSubtotal) }}</dd>
                    </div>
                @endif
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Refund</dt>
                    <dd class="text-sm font-medium text-gray-800 dark:text-white/90 text-right">{{ $fmtCurrency((int) ($transaction->refunded_amount ?? 0)) }}</dd>
                </div>
                @if ((int) ($transaction->voucher_discount_amount ?? 0) > 0)
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Voucher</dt>
                        <dd class="text-sm font-medium text-gray-800 dark:text-white/90 text-right">
                            {{ (string) ($transaction->voucher_code ?? '-') }}
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $transaction->voucherCampaign?->name ?? '-' }} · -{{ $fmtCurrency((int) ($transaction->voucher_discount_amount ?? 0)) }}
                            </div>
                        </dd>
                    </div>
                @endif
                @if ((int) ($transaction->manual_discount_amount ?? 0) > 0)
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Diskon Manual</dt>
                        <dd class="text-sm font-medium text-gray-800 dark:text-white/90 text-right">
                            -{{ $fmtCurrency((int) ($transaction->manual_discount_amount ?? 0)) }}
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                @if ($transaction->manual_discount_type && $transaction->manual_discount_value)
                                    {{ $transaction->manual_discount_type === 'percent' ? ($transaction->manual_discount_value.'%') : ('Rp'.number_format((int) $transaction->manual_discount_value, 0, ',', '.')) }}
                                @endif
                                @if ($transaction->manual_discount_note)
                                    · {{ $transaction->manual_discount_note }}
                                @endif
                            </div>
                            @if ($transaction->manualDiscountByUser)
                                <div class="text-xs text-gray-500 dark:text-gray-400">Oleh: {{ $transaction->manualDiscountByUser->name }}</div>
                            @endif
                        </dd>
                    </div>
                @endif
                @if ((int) ($transaction->point_discount_amount ?? 0) > 0 || (int) ($transaction->points_redeemed ?? 0) > 0 || (int) ($transaction->points_earned ?? 0) > 0)
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Poin</dt>
                        <dd class="text-sm font-medium text-gray-800 dark:text-white/90 text-right">
                            @if ((int) ($transaction->point_discount_amount ?? 0) > 0)
                                <div>-{{ $fmtCurrency((int) ($transaction->point_discount_amount ?? 0)) }}</div>
                            @endif
                            @if ((int) ($transaction->points_redeemed ?? 0) > 0)
                                <div class="text-xs text-gray-500 dark:text-gray-400">Dipakai: {{ number_format((int) ($transaction->points_redeemed ?? 0), 0, ',', '.') }}</div>
                            @endif
                            @if ((int) ($transaction->points_earned ?? 0) > 0)
                                <div class="text-xs text-gray-500 dark:text-gray-400">Didapat: {{ number_format((int) ($transaction->points_earned ?? 0), 0, ',', '.') }}</div>
                            @endif
                        </dd>
                    </div>
                @endif
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Inventory</dt>
                    <dd class="text-right">
                        <span class="rounded-full px-2 py-0.5 text-theme-xs font-medium {{ $inventoryApplied ? 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500' : 'bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-orange-400' }}">
                            {{ $inventoryApplied ? 'Applied' : 'Pending' }}
                        </span>
                    </dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Cash diterima</dt>
                    <dd class="text-sm font-medium text-gray-800 dark:text-white/90 text-right">{{ $fmtCurrency((int) ($transaction->cash_received ?? 0)) }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Kembalian</dt>
                    <dd class="text-sm font-medium text-gray-800 dark:text-white/90 text-right">{{ $fmtCurrency((int) ($transaction->cash_change ?? 0)) }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">External ID</dt>
                    <dd class="text-sm font-medium text-gray-800 dark:text-white/90 text-right">
                        @can('transactions.pii.view')
                            {{ $transaction->external_id }}
                        @else
                            -
                        @endcan
                    </dd>
                </div>
            </dl>

            <x-common.input-error for="inventory" class="mt-4 text-sm text-error-600" />

            @if (! $inventoryApplied)
                @can('inventory.manage')
                    <button type="button" wire:click="processInventory" class="mt-4 bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex h-11 w-full items-center justify-center rounded-lg px-4 text-sm font-medium text-white transition">
                        Proses Inventory
                    </button>
                @endcan
            @endif
        </div>

        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] lg:col-span-2">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Item Transaksi</h3>
            </div>

            <div class="custom-scrollbar overflow-x-auto">
                <table class="w-full table-auto">
                    <thead>
                        <tr class="border-b border-gray-200 dark:divide-gray-800 dark:border-gray-800">
                            <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Produk</th>
                            <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Varian</th>
                            <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Qty</th>
                            <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Harga</th>
                            <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Subtotal</th>
                            <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">HPP</th>
                            <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Laba</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($displayItems as $item)
                            @php
                                $hppTotal = (float) ($item->hpp_total ?? 0);
                                $voucherItemDiscount = (int) ($item->voucher_discount_amount ?? 0);
                                $manualItemDiscount = (int) ($item->manual_discount_amount ?? 0);
                                $netLineSubtotal = (float) $item->subtotal - $voucherItemDiscount - $manualItemDiscount;
                                $profit = $netLineSubtotal - $hppTotal;
                                $inventoryApplied = $transaction->inventory_applied_at !== null;
                                $children = $transaction->transactionItems->where('parent_transaction_item_id', (int) $item->id)->values();
                                $variantDisplay = \App\Support\Products\ItemNameFormatter::displayVariantName((int) $item->product_id, $item->variant?->name);
                            @endphp
                            <tr>
                                <td class="px-5 py-4">
                                    <div class="space-y-1">
                                        <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $item->product?->name ?? '-' }}</p>
                                        @if ($item->note)
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $item->note }}</p>
                                        @endif
                                        @if ($children->isNotEmpty())
                                            <div class="space-y-0.5">
                                                @foreach ($children as $child)
                                                    @php
                                                        $childVariant = \App\Support\Products\ItemNameFormatter::displayVariantName((int) $child->product_id, $child->variant?->name);
                                                    @endphp
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                                        • {{ (string) ($child->product?->name ?? 'Produk') }}{{ $childVariant !== '' ? ' - '.$childVariant : '' }} x{{ number_format((int) ($child->quantity ?? 0), 0, ',', '.') }}
                                                    </p>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="text-sm text-gray-800 dark:text-white/90">{{ $variantDisplay !== '' ? $variantDisplay : '-' }}</p>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ number_format((int) $item->quantity, 0, ',', '.') }}</p>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <p class="text-sm text-gray-800 dark:text-white/90">{{ $fmtCurrency((float) $item->price) }}</p>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <p class="text-sm font-semibold text-gray-800 dark:text-white/90">{{ $fmtCurrency((float) $item->subtotal) }}</p>
                                    @if ($voucherItemDiscount > 0 || $manualItemDiscount > 0)
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            Diskon:
                                            @if ($voucherItemDiscount > 0)
                                                Voucher {{ $fmtCurrency($voucherItemDiscount) }}
                                            @endif
                                            @if ($manualItemDiscount > 0)
                                                {{ $voucherItemDiscount > 0 ? '·' : '' }} Manual {{ $fmtCurrency($manualItemDiscount) }}
                                            @endif
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Net: {{ $fmtCurrency($netLineSubtotal) }}</p>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <p class="text-sm text-gray-800 dark:text-white/90">{{ $inventoryApplied ? $fmtCurrency($hppTotal) : '-' }}</p>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <p class="text-sm font-semibold {{ $hppTotal > 0 && $profit < 0 ? 'text-error-600 dark:text-error-500' : 'text-gray-800 dark:text-white/90' }}">
                                        {{ $inventoryApplied ? $fmtCurrency($profit) : '-' }}
                                    </p>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-200 px-5 py-4 dark:border-gray-800">
                <div class="flex flex-col gap-2 text-sm">
                    @php
                        $totalHpp = (float) $displayItems->sum('hpp_total');
                        $totalProfit = (float) $netSubtotal - $totalHpp;
                        $inventoryApplied = $transaction->inventory_applied_at !== null;
                        $feeAmount = (int) ($transaction->payment_fee_amount ?? 0);
                        $pointDiscountAmount = (int) ($transaction->point_discount_amount ?? 0);
                        $roundingAmount = (int) ($transaction->rounding_amount ?? 0);
                    @endphp
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">Subtotal</span>
                        <span class="font-medium text-gray-800 dark:text-white/90">{{ $fmtCurrency((int) $transaction->subtotal) }}</span>
                    </div>
                    @if ($discountTotal > 0)
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Total Diskon</span>
                            <span class="font-medium text-gray-800 dark:text-white/90">-{{ $fmtCurrency($discountTotal) }}</span>
                        </div>
                    @endif
                    @if ((int) ($transaction->voucher_discount_amount ?? 0) > 0)
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Diskon Voucher</span>
                            <span class="font-medium text-gray-800 dark:text-white/90">-{{ $fmtCurrency((int) $transaction->voucher_discount_amount) }}</span>
                        </div>
                    @endif
                    @if ((int) ($transaction->manual_discount_amount ?? 0) > 0)
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Diskon Manual</span>
                            <span class="font-medium text-gray-800 dark:text-white/90">-{{ $fmtCurrency((int) $transaction->manual_discount_amount) }}</span>
                        </div>
                    @endif
                    @if ($pointDiscountAmount > 0)
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Diskon Poin</span>
                            <span class="font-medium text-gray-800 dark:text-white/90">-{{ $fmtCurrency($pointDiscountAmount) }}</span>
                        </div>
                    @endif
                    @if ($discountTotal > 0)
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Subtotal Bersih</span>
                            <span class="font-medium text-gray-800 dark:text-white/90">{{ $fmtCurrency((int) $netSubtotal) }}</span>
                        </div>
                    @endif
                    @if ((int) ($transaction->points_redeemed ?? 0) > 0 || (int) ($transaction->points_earned ?? 0) > 0)
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Poin Dipakai</span>
                            <span class="font-medium text-gray-800 dark:text-white/90">{{ number_format((int) ($transaction->points_redeemed ?? 0), 0, ',', '.') }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Poin Didapat</span>
                            <span class="font-medium text-gray-800 dark:text-white/90">{{ number_format((int) ($transaction->points_earned ?? 0), 0, ',', '.') }}</span>
                        </div>
                    @endif
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">Total HPP</span>
                        <span class="font-medium text-gray-800 dark:text-white/90">{{ $inventoryApplied ? $fmtCurrency($totalHpp) : 'Belum diproses' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">Estimasi Laba</span>
                        <span class="font-medium text-gray-800 dark:text-white/90">{{ $inventoryApplied ? $fmtCurrency($totalProfit) : '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">Pajak PB1</span>
                        <span class="font-medium text-gray-800 dark:text-white/90">{{ $fmtCurrency((int) ($transaction->tax_amount ?? 0)) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">Biaya Admin</span>
                        <span class="font-medium text-gray-800 dark:text-white/90">{{ $feeAmount > 0 ? $fmtCurrency($feeAmount) : '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">Pembulatan</span>
                        <span class="font-medium text-gray-800 dark:text-white/90">{{ $fmtCurrency($roundingAmount) }}</span>
                    </div>
                    <div class="flex items-center justify-between pt-2 border-t border-gray-100 dark:border-gray-800">
                        <span class="text-gray-800 dark:text-white/90 font-semibold">Total</span>
                        <span class="text-gray-800 dark:text-white/90 font-semibold">{{ $fmtCurrency((int) $transaction->total) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
            <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Riwayat Aktivitas</h3>
        </div>
        <div class="divide-y divide-gray-100 dark:divide-gray-800">
            @forelse ($transaction->events as $event)
                <div class="px-5 py-4">
                    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-2">
                            <span class="rounded-full px-2 py-0.5 text-theme-xs font-medium {{ $event->action === 'refund' ? 'bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-orange-400' : ($event->action === 'void' ? 'bg-gray-50 text-gray-700 dark:bg-gray-500/15 dark:text-gray-300' : 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500') }}">
                                {{ strtoupper((string) $event->action) }}
                            </span>
                            <p class="text-sm font-medium text-gray-800 dark:text-white/90">
                                {{ $event->actor?->name ?? 'System' }}
                            </p>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ optional($event->created_at)->format('d M Y, H:i') }}</p>
                    </div>

                    @php
                        $meta = (array) ($event->meta ?? []);
                    @endphp
                    <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                        @if (! empty($meta['reason']))
                            <p class="text-sm text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">Alasan:</span> {{ $meta['reason'] }}</p>
                        @endif
                        @if (! empty($meta['amount']))
                            <p class="text-sm text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">Nominal:</span> {{ $fmtCurrency((int) $meta['amount']) }}</p>
                        @endif
                        @if ($event->action === 'voucher_redeem')
                            @if (! empty($meta['voucher_code']))
                                <p class="text-sm text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">Voucher:</span> {{ (string) $meta['voucher_code'] }}</p>
                            @endif
                            @if (! empty($meta['discount_amount']))
                                <p class="text-sm text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">Diskon:</span> {{ $fmtCurrency((int) $meta['discount_amount']) }}</p>
                            @endif
                        @endif
                        @if ($event->action === 'manual_discount')
                            @if (! empty($meta['amount']))
                                <p class="text-sm text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">Diskon:</span> {{ $fmtCurrency((int) $meta['amount']) }}</p>
                            @endif
                            @if (! empty($meta['type']) && ! empty($meta['value']))
                                <p class="text-sm text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">Aturan:</span> {{ $meta['type'] === 'percent' ? ((int) $meta['value']).'%' : $fmtCurrency((int) $meta['value']) }}</p>
                            @endif
                        @endif
                        @if ($event->action === 'point_redeem')
                            @if (! empty($meta['points_redeemed']))
                                <p class="text-sm text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">Poin dipakai:</span> {{ number_format((int) $meta['points_redeemed'], 0, ',', '.') }}</p>
                            @endif
                            @if (! empty($meta['point_discount_amount']))
                                <p class="text-sm text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">Diskon poin:</span> {{ $fmtCurrency((int) $meta['point_discount_amount']) }}</p>
                            @endif
                        @endif
                        @if (! empty($meta['approval_required']))
                            @php($approvedName = ! empty($meta['approved_by_user_id']) ? ($approvedBy[(int) $meta['approved_by_user_id']] ?? null) : null)
                            <p class="text-sm text-gray-700 dark:text-gray-300">
                                <span class="text-gray-500 dark:text-gray-400">Approval:</span>
                                {{ $approvedName ? 'Disetujui oleh '.$approvedName : 'Dibutuhkan' }}
                            </p>
                        @endif
                        @if (! empty($meta['previous_payment_status']) || ! empty($meta['new_payment_status']))
                            <p class="text-sm text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">Status:</span> {{ \App\Helpers\DataLabelHelper::enum($meta['previous_payment_status'] ?? null, 'payment_status') }} → {{ \App\Helpers\DataLabelHelper::enum($meta['new_payment_status'] ?? null, 'payment_status') }}</p>
                        @endif
                        @if (array_key_exists('revert_inventory', $meta))
                            <p class="text-sm text-gray-700 dark:text-gray-300"><span class="text-gray-500 dark:text-gray-400">Revert stok:</span> {{ (bool) $meta['revert_inventory'] ? 'Ya' : 'Tidak' }}</p>
                        @endif
                    </div>
                </div>
            @empty
                <div class="px-5 py-10">
                    <p class="text-center text-sm text-gray-500 dark:text-gray-400">Belum ada koreksi.</p>
                </div>
            @endforelse
        </div>
    </div>

    @if ($voidModalOpen)
        <div class="fixed inset-0 z-[100000] flex items-center justify-center p-4" aria-modal="true" role="dialog">
            <div class="absolute inset-0 bg-black/50" wire:click="closeVoidModal"></div>
            <div class="relative w-full max-w-2xl overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <div>
                        <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Void Transaksi</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Void hanya untuk transaksi pending.</p>
                    </div>
                    <button type="button" wire:click="closeVoidModal" class="text-sm font-medium text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
                        Tutup
                    </button>
                </div>
                <form wire:submit="voidTransaction" class="space-y-4 p-5">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Alasan</label>
                        <input wire:model.live="correctionReason" type="text" aria-invalid="{{ $errors->has('correctionReason') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('correctionReason') ? 'error-correctionReason' : '' }}" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                        <x-common.input-error for="correctionReason" />
                    </div>
                    @if ($voidNeedsApproval)
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-900">
                            <p class="text-sm font-semibold text-gray-800 dark:text-white/90">Approval (PIN)</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Dibutuhkan sesuai aturan sistem. Batas void cepat: maks {{ number_format($voidQuickMaxCount, 0, ',', '.') }}/hari dan window {{ number_format($voidWindowMinutes, 0, ',', '.') }} menit.
                            </p>

                            <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Approver (Opsional)</label>
                                    <select wire:model.live="approverUserId" aria-invalid="{{ $errors->has('approverUserId') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('approverUserId') ? 'error-approverUserId' : '' }}" class="shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-white px-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                                        <option value="">Auto (pakai PIN)</option>
                                        @foreach ($voidApprovers as $approver)
                                            <option value="{{ (int) $approver->id }}">{{ $approver->name }}</option>
                                        @endforeach
                                    </select>
                                    <x-common.input-error for="approverUserId" />
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Kosongkan jika ingin sistem otomatis mendeteksi approver dari PIN.</p>
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">PIN</label>
                                    <input wire:model.live="approverPin" type="password" inputmode="numeric" aria-invalid="{{ $errors->has('approverPin') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('approverPin') ? 'error-approverPin' : '' }}" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" placeholder="PIN approver" />
                                    <x-common.input-error for="approverPin" />
                                </div>
                            </div>
                        </div>
                    @endif
                    @if ($inventoryApplied)
                        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <input wire:model.live="revertInventory" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500/20 dark:border-gray-700" />
                            Revert stok (buat pergerakan reversal)
                        </label>
                    @endif
                    <div class="flex items-center justify-end gap-2">
                        <button type="button" wire:click="closeVoidModal" class="shadow-theme-xs inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                            Batal
                        </button>
                        <button type="submit" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex h-11 items-center justify-center rounded-lg px-4 text-sm font-semibold text-white transition">
                            Void
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($refundModalOpen)
        <div class="fixed inset-0 z-[100000] flex items-center justify-center p-4" aria-modal="true" role="dialog">
            <div class="absolute inset-0 bg-black/50" wire:click="closeRefundModal"></div>
            <div class="relative w-full max-w-2xl overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <div>
                        <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Refund Transaksi</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Refund dicatat untuk kebutuhan audit dan laporan.</p>
                    </div>
                    <button type="button" wire:click="closeRefundModal" class="text-sm font-medium text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
                        Tutup
                    </button>
                </div>
                <form wire:submit="refundTransaction" class="space-y-4 p-5">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Alasan</label>
                            <input wire:model.live="correctionReason" type="text" aria-invalid="{{ $errors->has('correctionReason') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('correctionReason') ? 'error-correctionReason' : '' }}" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                            <x-common.input-error for="correctionReason" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Nominal Refund</label>
                            <x-common.rupiah-input wire-model="refundAmount" placeholder="0" />
                            <x-common.input-error for="refundAmount" />
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Batas refund cepat: Rp{{ number_format($refundQuickMaxAmount, 0, ',', '.') }} (maks {{ number_format($refundQuickMaxCount, 0, ',', '.') }}/hari/kasir). Jika melebihi, sistem minta PIN.</p>
                        </div>
                        <div class="flex items-end">
                            @if ($inventoryApplied)
                                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <input wire:model.live="revertInventory" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500/20 dark:border-gray-700" />
                                    Revert stok
                                </label>
                            @endif
                        </div>
                    </div>
                    @if ($refundNeedsApproval)
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-900">
                            <p class="text-sm font-semibold text-gray-800 dark:text-white/90">Approval (PIN)</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Refund ini memerlukan approval (PIN) sesuai aturan sistem.</p>

                            <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Approver (Opsional)</label>
                                    <select wire:model.live="approverUserId" aria-invalid="{{ $errors->has('approverUserId') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('approverUserId') ? 'error-approverUserId' : '' }}" class="shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-white px-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                                        <option value="">Auto (pakai PIN)</option>
                                        @foreach ($refundApprovers as $approver)
                                            <option value="{{ (int) $approver->id }}">{{ $approver->name }}</option>
                                        @endforeach
                                    </select>
                                    <x-common.input-error for="approverUserId" />
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Kosongkan jika ingin sistem otomatis mendeteksi approver dari PIN.</p>
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">PIN</label>
                                    <input wire:model.live="approverPin" type="password" inputmode="numeric" aria-invalid="{{ $errors->has('approverPin') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('approverPin') ? 'error-approverPin' : '' }}" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" placeholder="PIN approver" />
                                    <x-common.input-error for="approverPin" />
                                </div>
                            </div>
                        </div>
                    @endif
                    <div class="flex items-center justify-end gap-2">
                        <button type="button" wire:click="closeRefundModal" class="shadow-theme-xs inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                            Batal
                        </button>
                        <button type="submit" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex h-11 items-center justify-center rounded-lg px-4 text-sm font-semibold text-white transition">
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
