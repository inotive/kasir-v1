<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ $title }}</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $campaign->name }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('vouchers.index') }}" wire:navigate class="shadow-theme-xs inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                Kembali
            </a>
            <a href="{{ route('vouchers.edit', ['campaign' => $campaign->id]) }}" wire:navigate class="shadow-theme-xs inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                Ubah Program
            </a>
            <button type="button" wire:click="exportCodesCsv" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex h-11 items-center justify-center rounded-lg px-4 text-sm font-semibold text-white transition">
                Unduh CSV
            </button>
        </div>
    </div>

    <!-- Code Generation Form -->
    <section class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
        <form class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-8" wire:submit.prevent="generateCodes">
            <!-- Mode Selection -->
            <div class="lg:col-span-2">
                <label for="generateMode" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">
                    Cara Buat Kode
                </label>
                <select
                    id="generateMode"
                    wire:model.live="generateMode"
                    aria-invalid="{{ $errors->has('generateMode') ? 'true' : 'false' }}"
                    aria-describedby="{{ $errors->has('generateMode') ? 'error-generateMode' : '' }}"
                    class="shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-white px-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                >
                    <option value="alphanumeric">Alfanumerik</option>
                    <option value="pattern">Pola</option>
                    <option value="custom">Manual</option>
                </select>
                <x-common.input-error for="generateMode" />
            </div>

            <!-- Quantity Input -->
            <div class="lg:col-span-2">
                <label for="generateCount" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">
                    Jumlah
                </label>
                <input
                    id="generateCount"
                    wire:model.live="generateCount"
                    type="number"
                    min="1"
                    max="500"
                    aria-invalid="{{ $errors->has('generateCount') ? 'true' : 'false' }}"
                    aria-describedby="{{ $errors->has('generateCount') ? 'error-generateCount' : '' }}"
                    class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                />
                <x-common.input-error for="generateCount" />
            </div>

            <!-- Alphanumeric Length -->
            <div class="lg:col-span-2 @if($generateMode !== 'alphanumeric') hidden @endif">
                <label for="generateLength" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">
                    Panjang
                </label>
                <input
                    id="generateLength"
                    wire:model.live="generateLength"
                    type="number"
                    min="4"
                    max="30"
                    aria-invalid="{{ $errors->has('generateLength') ? 'true' : 'false' }}"
                    aria-describedby="{{ $errors->has('generateLength') ? 'error-generateLength' : '' }}"
                    class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                />
                <x-common.input-error for="generateLength" />
            </div>

            <!-- Pattern Input -->
            <div class="lg:col-span-2 @if($generateMode !== 'pattern') hidden @endif">
                <label for="generatePattern" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">
                    Pola
                </label>
                <input
                    id="generatePattern"
                    wire:model.live="generatePattern"
                    type="text"
                    aria-invalid="{{ $errors->has('generatePattern') ? 'true' : 'false' }}"
                    aria-describedby="{{ $errors->has('generatePattern') ? 'error-generatePattern' : '' }}"
                    class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                />
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {YYYY} {YY} {MM} {DD} {RAND:n} {SEQ:n}
                </p>
                <x-common.input-error for="generatePattern" />
            </div>

            <!-- Custom Codes Textarea -->
            <div class="lg:col-span-2 @if($generateMode !== 'custom') hidden @endif">
                <label for="customCodes" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">
                    Kode Manual (1 per baris)
                </label>
                <textarea
                    id="customCodes"
                    wire:model.live="customCodes"
                    rows="4"
                    aria-invalid="{{ $errors->has('customCodes') ? 'true' : 'false' }}"
                    aria-describedby="{{ $errors->has('customCodes') ? 'error-customCodes' : '' }}"
                    class="dark:bg-dark-900 shadow-theme-xs w-full rounded-lg border border-gray-300 bg-transparent px-4 py-3 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                ></textarea>
                <x-common.input-error for="customCodes" />
            </div>

            <!-- Submit Button -->
            <div class="lg:col-span-2 flex justify-end">
                <button
                    type="submit"
                    class="bg-brand-500 w-full mt-5 shadow-theme-xs hover:bg-brand-600 inline-flex h-11 items-center justify-center rounded-lg px-6 text-sm font-semibold text-white transition"
                >
                    Buat Kode
                </button>
            </div>
            <!-- Helper Text -->
            <div class="lg:col-span-6">
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Jika ada kode yang sudah pernah dibuat, sistem akan melewati kode duplikat.
                </p>
            </div>
        </form>
    </section>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="relative flex-1 sm:flex-none">
            <span class="absolute top-1/2 left-4 -translate-y-1/2 text-gray-500 dark:text-gray-400">
                <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M3.04199 9.37363C3.04199 5.87693 5.87735 3.04199 9.37533 3.04199C12.8733 3.04199 15.7087 5.87693 15.7087 9.37363C15.7087 12.8703 12.8733 15.7053 9.37533 15.7053C5.87735 15.7053 3.04199 12.8703 3.04199 9.37363ZM9.37533 1.54199C5.04926 1.54199 1.54199 5.04817 1.54199 9.37363C1.54199 13.6991 5.04926 17.2053 9.37533 17.2053C11.2676 17.2053 13.0032 16.5344 14.3572 15.4176L17.1773 18.238C17.4702 18.5309 17.945 18.5309 18.2379 18.238C18.5308 17.9451 18.5309 17.4703 18.238 17.1773L15.4182 14.3573C16.5367 13.0033 17.2087 11.2669 17.2087 9.37363C17.2087 5.04817 13.7014 1.54199 9.37533 1.54199Z" fill="" />
                </svg>
            </span>
            <input wire:model.live.debounce.400ms="search" type="text" placeholder="Cari kode..." class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent py-2.5 pr-4 pl-11 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden sm:w-[320px] sm:min-w-[320px] dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400">Total kode: {{ number_format((int) ($campaign->codes_count ?? 0), 0, ',', '.') }}</p>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="custom-scrollbar overflow-x-auto">
            <table class="w-full table-auto">
                <thead>
                    <tr class="border-b border-gray-200 dark:divide-gray-800 dark:border-gray-800">
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Kode</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Dipakai</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Status</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($codes as $row)
                        <tr x-data="{ copied:false }">
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="text-sm font-semibold text-gray-800 dark:text-white/90">{{ $row->code }}</p>
                                    <button type="button" @click="navigator.clipboard.writeText('{{ $row->code }}'); copied=true; setTimeout(()=>copied=false, 1200)" class="text-xs font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300">
                                        <span x-show="!copied">Salin</span>
                                        <span x-show="copied" x-cloak>Tersalin</span>
                                    </button>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $row->created_at?->format('d/m/Y H:i') }}</p>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <p class="text-sm text-gray-800 dark:text-white/90">{{ number_format((int) $row->times_redeemed, 0, ',', '.') }}</p>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <span class="rounded-full px-2 py-0.5 text-theme-xs font-medium {{ $row->is_active ? 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500' : 'bg-gray-50 text-gray-700 dark:bg-gray-500/15 dark:text-gray-300' }}">
                                    {{ $row->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <button type="button" wire:click="toggleActive({{ (int) $row->id }})" class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                                    {{ $row->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <x-common.empty-table-row colspan="4" message="Belum ada kode." />
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-200 px-5 py-4 dark:border-gray-800">
            {{ $codes->links('livewire.pagination.admin') }}
        </div>
    </div>
</div>
