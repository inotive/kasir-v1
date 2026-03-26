<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ $title }}</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Atur aturan voucher dan kategori produk yang berlaku.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('vouchers.index') }}" wire:navigate class="shadow-theme-xs inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                Kembali
            </a>
            @if ($campaignId)
                <a href="{{ route('vouchers.codes', ['campaign' => $campaignId]) }}" wire:navigate class="shadow-theme-xs inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                    Kelola Kode
                </a>
            @endif
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
        <form wire:submit="save" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2 flex items-center justify-between">
                <div>
                    <p class="text-sm font-semibold text-gray-800 dark:text-white/90">Status</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Aktifkan untuk mulai digunakan.</p>
                </div>
                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" wire:model.live="is_active" class="h-5 w-5 rounded border-gray-300 text-brand-600 focus:ring-brand-500/10" />
                    <span class="text-sm text-gray-700 dark:text-gray-300">Aktif</span>
                </label>
            </div>

            <div class="sm:col-span-2">
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Nama Program</label>
                <input wire:model.live="name" type="text" aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('name') ? 'error-name' : '' }}" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                <x-common.input-error for="name" />
            </div>

            <div class="sm:col-span-2">
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Deskripsi (Opsional)</label>
                <input wire:model.live="description" type="text" aria-invalid="{{ $errors->has('description') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('description') ? 'error-description' : '' }}" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                <x-common.input-error for="description" />
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Tipe Diskon</label>
                <select wire:model.live="discount_type" aria-invalid="{{ $errors->has('discount_type') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('discount_type') ? 'error-discount_type' : '' }}" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="percent">Persen (%)</option>
                    <option value="fixed_amount">Nominal (Rp)</option>
                </select>
                <x-common.input-error for="discount_type" />
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Nilai Diskon</label>
                @if ($discount_type === 'fixed_amount')
                    <x-common.rupiah-input wire-model="discount_value" placeholder="0" />
                @else
                    <input wire:model.live="discount_value" type="number" min="1" aria-invalid="{{ $errors->has('discount_value') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('discount_value') ? 'error-discount_value' : '' }}" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                @endif
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $discount_type === 'percent' ? 'Rentang 1–100.' : 'Nominal dalam rupiah.' }}</p>
                <x-common.input-error for="discount_value" />
            </div>

            @if ($discount_type === 'percent')
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Batas Maksimal Diskon (Opsional)</label>
                    <x-common.rupiah-input wire-model="max_discount_amount" placeholder="0" />
                    <x-common.input-error for="max_discount_amount" />
                </div>
            @else
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Batas Maksimal Diskon</label>
                    <input disabled type="text" value="Tidak berlaku untuk diskon nominal" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-500 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-400" />
                </div>
            @endif

            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Minimal Belanja (Opsional)</label>
                <x-common.rupiah-input wire-model="min_eligible_subtotal" placeholder="0" />
                <x-common.input-error for="min_eligible_subtotal" />
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Mulai (Opsional)</label>
                <x-common.date-picker
                    wire-model="starts_at"
                    :default-today="false"
                    :clearable="true"
                    input-class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent py-2.5 pr-20 pl-4 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                />
                <x-common.input-error for="starts_at" />
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Berakhir (Opsional)</label>
                <x-common.date-picker
                    wire-model="ends_at"
                    :default-today="false"
                    :clearable="true"
                    input-class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent py-2.5 pr-20 pl-4 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                />
                <x-common.input-error for="ends_at" />
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Kuota Total (Opsional)</label>
                <input wire:model.live="usage_limit_total" type="number" min="0" aria-invalid="{{ $errors->has('usage_limit_total') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('usage_limit_total') ? 'error-usage_limit_total' : '' }}" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                <x-common.input-error for="usage_limit_total" />
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Kuota per Pengguna (Opsional)</label>
                <input wire:model.live="usage_limit_per_user" type="number" min="0" aria-invalid="{{ $errors->has('usage_limit_per_user') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('usage_limit_per_user') ? 'error-usage_limit_per_user' : '' }}" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                <x-common.input-error for="usage_limit_per_user" />
            </div>

            <div class="sm:col-span-2 flex items-center justify-between border-t border-gray-100 pt-4 dark:border-gray-800">
                <div>
                    <p class="text-sm font-semibold text-gray-800 dark:text-white/90">Khusus Member</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Jika aktif, voucher hanya bisa digunakan oleh member terdaftar.</p>
                </div>
                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" wire:model.live="is_member_only" class="h-5 w-5 rounded border-gray-300 text-brand-600 focus:ring-brand-500/10" />
                    <span class="text-sm text-gray-700 dark:text-gray-300">Ya</span>
                </label>
            </div>

            <div class="sm:col-span-2">
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Kategori Berlaku (Opsional)</label>
                <div class="flex flex-wrap gap-2 rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-800 dark:bg-gray-950">
                    <button type="button" wire:click="clearEligibleCategories" @class([
                        'rounded-lg border px-3 py-2 text-sm font-semibold transition',
                        'bg-brand-500 text-white border-brand-600' => count($eligible_category_ids) === 0,
                        'bg-white text-gray-700 border-gray-200 hover:bg-gray-50 dark:bg-gray-900 dark:text-gray-300 dark:border-gray-800 dark:hover:bg-white/[0.03]' => count($eligible_category_ids) !== 0,
                    ])>
                        Semua Kategori
                    </button>
                    <div class="w-full"></div>
                    <div class="max-h-44 w-full overflow-auto pr-1">
                        <div class="flex flex-wrap gap-2">
                            @foreach ($categories as $cat)
                                <button type="button" wire:click="toggleEligibleCategory({{ (int) $cat->id }})" @class([
                                    'rounded-lg border px-3 py-2 text-sm font-semibold transition',
                                    'bg-brand-500 text-white border-brand-600' => in_array((int) $cat->id, $eligible_category_ids, true),
                                    'bg-white text-gray-700 border-gray-200 hover:bg-gray-50 dark:bg-gray-900 dark:text-gray-300 dark:border-gray-800 dark:hover:bg-white/[0.03]' => ! in_array((int) $cat->id, $eligible_category_ids, true),
                                ])>
                                    {{ $cat->name }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Pilih satu atau beberapa kategori. Jika memilih “Semua Kategori”, voucher berlaku untuk semua.</p>
                <x-common.input-error for="eligible_category_ids" />
            </div>

            <div class="sm:col-span-2">
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Syarat & Ketentuan (Opsional)</label>
                <textarea wire:model.live="terms" rows="4" aria-invalid="{{ $errors->has('terms') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('terms') ? 'error-terms' : '' }}" class="dark:bg-dark-900 shadow-theme-xs w-full rounded-lg border border-gray-300 bg-transparent px-4 py-3 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></textarea>
                <x-common.input-error for="terms" />
            </div>

            <div class="sm:col-span-2 flex items-center justify-end gap-2">
                <button type="submit" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex h-11 items-center justify-center rounded-lg px-6 text-sm font-semibold text-white transition">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>
