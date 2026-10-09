@php
    $pmCurrentMethod = (string) ($paymentMethodTransaction?->payment_method ?? '');
    $pmCurrentLabel = $changeablePaymentMethods[$pmCurrentMethod] ?? \App\Helpers\DataLabelHelper::enum($pmCurrentMethod !== '' ? $pmCurrentMethod : null, 'payment_method');
    $pmFmt = fn ($value) => 'Rp'.number_format((float) $value, 0, ',', '.');
@endphp

@if ($paymentMethodModalOpen && $paymentMethodTransaction)
    <div class="fixed inset-0 z-[100000] flex items-center justify-center p-4" aria-modal="true" role="dialog">
        <div class="absolute inset-0 bg-black/50" wire:click="closePaymentMethodModal"></div>
        <div class="relative w-full max-w-xl overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                <div>
                    <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Ubah Metode Bayar</h3>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $paymentMethodTransaction->code }} · Saat ini: {{ $pmCurrentLabel }} · Total {{ $pmFmt((int) ($paymentMethodTransaction->total ?? 0)) }}. Pendapatan tidak berubah, hanya rekap per metode bayar.</p>
                </div>
                <button type="button" wire:click="closePaymentMethodModal" class="text-sm font-medium text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
                    Tutup
                </button>
            </div>
            <form wire:submit="changePaymentMethod" class="space-y-4 p-5">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Metode Baru</label>
                    <select wire:model.live="newPaymentMethod" aria-invalid="{{ $errors->has('newPaymentMethod') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('newPaymentMethod') ? 'error-newPaymentMethod' : '' }}" class="shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-white px-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                        <option value="">Pilih metode</option>
                        @foreach ($changeablePaymentMethods as $methodKey => $methodLabel)
                            @continue($methodKey === $pmCurrentMethod)
                            <option value="{{ $methodKey }}">{{ $methodLabel }}</option>
                        @endforeach
                    </select>
                    <x-common.input-error for="newPaymentMethod" />
                </div>
                @if ($newPaymentMethod === 'cash')
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Uang Diterima</label>
                        <input wire:model.live.debounce.300ms="newCashReceived" type="text" inputmode="numeric" aria-invalid="{{ $errors->has('newCashReceived') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('newCashReceived') ? 'error-newCashReceived' : '' }}" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" placeholder="{{ (int) ($paymentMethodTransaction->total ?? 0) }}" />
                        <x-common.input-error for="newCashReceived" />
                        @php($previewReceived = (int) preg_replace('/\D+/', '', $newCashReceived))
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Kembalian: {{ $pmFmt(max(0, $previewReceived - (int) ($paymentMethodTransaction->total ?? 0))) }}</p>
                    </div>
                @elseif ($pmCurrentMethod === 'cash' && $newPaymentMethod !== '')
                    <div class="rounded-xl border border-warning-200 bg-warning-50 p-3 text-xs text-warning-700 dark:border-warning-800 dark:bg-warning-500/10 dark:text-orange-400">
                        Pastikan uang tunai {{ $pmFmt((int) ($paymentMethodTransaction->cash_received ?? $paymentMethodTransaction->total ?? 0)) }} sudah dikembalikan ke pelanggan (dan kembalian {{ $pmFmt((int) ($paymentMethodTransaction->cash_change ?? 0)) }} diterima kembali). Data uang diterima &amp; kembalian akan dikosongkan.
                    </div>
                @endif
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Alasan</label>
                    <input wire:model.live="paymentMethodReason" type="text" aria-invalid="{{ $errors->has('paymentMethodReason') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('paymentMethodReason') ? 'error-paymentMethodReason' : '' }}" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                    <x-common.input-error for="paymentMethodReason" />
                </div>
                <div class="flex items-center justify-end gap-2">
                    <button type="button" wire:click="closePaymentMethodModal" class="shadow-theme-xs inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
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
