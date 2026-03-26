<div class="space-y-6">
    @php
        $canEdit = auth()->user()?->can('dining_tables.edit') ?? false;
    @endphp

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Meja</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Kelola meja dan QR untuk transaksi dine-in.</p>
        </div>
    </div>

    <x-common.input-error for="tables" class="text-sm text-error-600" />

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-col justify-between gap-5 border-b border-gray-200 px-5 py-4 lg:flex-row lg:items-center dark:border-gray-800">
            <div class="relative flex-1 lg:flex-none">
                <span class="absolute top-1/2 left-4 -translate-y-1/2 text-gray-500 dark:text-gray-400">
                    <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M3.04199 9.37363C3.04199 5.87693 5.87735 3.04199 9.37533 3.04199C12.8733 3.04199 15.7087 5.87693 15.7087 9.37363C15.7087 12.8703 12.8733 15.7053 9.37533 15.7053C5.87735 15.7053 3.04199 12.8703 3.04199 9.37363ZM9.37533 1.54199C5.04926 1.54199 1.54199 5.04817 1.54199 9.37363C1.54199 13.6991 5.04926 17.2053 9.37533 17.2053C11.2676 17.2053 13.0032 16.5344 14.3572 15.4176L17.1773 18.238C17.4702 18.5309 17.945 18.5309 18.2379 18.238C18.5308 17.9451 18.5309 17.4703 18.238 17.1773L15.4182 14.3573C16.5367 13.0033 17.2087 11.2669 17.2087 9.37363C17.2087 5.04817 13.7014 1.54199 9.37533 1.54199Z" fill="" />
                    </svg>
                </span>
                <input wire:model.live.debounce.400ms="search" type="text" placeholder="Cari nomor meja..." class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent py-2.5 pr-4 pl-11 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden lg:w-[320px] lg:min-w-[320px] dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if ($canEdit)
                    <button type="button" wire:click="generateMissingQr" class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                        Generate QR yang kosong
                    </button>
                @endif
            </div>
        </div>

        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-12 lg:items-end">
                <form wire:submit="createTable" class="grid grid-cols-1 gap-3 lg:col-span-5 lg:grid-cols-6 lg:items-end">
                    <div class="lg:col-span-4">
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Nomor Meja</label>
                        <input wire:model.live="tableNumber" type="text" placeholder="Contoh: T01" aria-invalid="{{ $errors->has('tableNumber') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('tableNumber') ? 'error-tableNumber' : '' }}" {{ $canEdit ? '' : 'disabled' }} class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                        <x-common.input-error for="tableNumber" />
                    </div>
                    <div class="lg:col-span-2">
                        @if ($canEdit)
                            <button type="submit" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex h-11 w-full items-center justify-center rounded-lg px-4 text-sm font-medium text-white transition">
                                Tambah
                            </button>
                        @endif
                    </div>
                </form>

                <form wire:submit="bulkCreate" class="grid grid-cols-1 gap-3 lg:col-span-7 lg:grid-cols-10 lg:items-end">
                    <div class="lg:col-span-2">
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Prefix</label>
                        <input wire:model.live="bulkPrefix" type="text" aria-invalid="{{ $errors->has('bulkPrefix') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('bulkPrefix') ? 'error-bulkPrefix' : '' }}" {{ $canEdit ? '' : 'disabled' }} class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2.5 text-sm text-gray-800 disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                        <x-common.input-error for="bulkPrefix" />
                    </div>
                    <div class="lg:col-span-2">
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Mulai</label>
                        <input wire:model.live="bulkStart" type="number" min="0" aria-invalid="{{ $errors->has('bulkStart') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('bulkStart') ? 'error-bulkStart' : '' }}" {{ $canEdit ? '' : 'disabled' }} class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2.5 text-sm text-gray-800 disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                        <x-common.input-error for="bulkStart" />
                    </div>
                    <div class="lg:col-span-2">
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Sampai</label>
                        <input wire:model.live="bulkEnd" type="number" min="0" aria-invalid="{{ $errors->has('bulkEnd') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('bulkEnd') ? 'error-bulkEnd' : '' }}" {{ $canEdit ? '' : 'disabled' }} class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2.5 text-sm text-gray-800 disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                        <x-common.input-error for="bulkEnd" />
                    </div>
                    <div class="lg:col-span-2">
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Padding</label>
                        <input wire:model.live="bulkPadLength" type="number" min="0" max="6" aria-invalid="{{ $errors->has('bulkPadLength') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('bulkPadLength') ? 'error-bulkPadLength' : '' }}" {{ $canEdit ? '' : 'disabled' }} class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2.5 text-sm text-gray-800 disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                        <x-common.input-error for="bulkPadLength" />
                    </div>
                    <div class="lg:col-span-2">
                        @if ($canEdit)
                            <button type="submit" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex h-11 w-full items-center justify-center rounded-lg px-4 text-sm font-medium text-white transition">
                                Buat Massal
                            </button>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <div class="custom-scrollbar overflow-x-auto">
            <table class="w-full table-auto">
                <thead>
                    <tr class="border-b border-gray-200 dark:divide-gray-800 dark:border-gray-800">
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Meja</th>
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">QR</th>
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Link</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Transaksi</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($tables as $table)
                        @php
                            $isEditing = $editingTableId === (int) $table->id;
                            $qrUrl = $table->qr_value;
                            $qrImageUrl = $table->image ? Storage::disk('public')->url($table->image).'?v='.((int) ($table->updated_at?->timestamp ?? 0)) : null;
                        @endphp
                        <tr x-data="{ open:false, copied:false }">
                            <td class="px-5 py-4">
                                @if ($isEditing)
                                    <input wire:model.live="editingTableNumber" type="text" aria-invalid="{{ $errors->has('editingTableNumber') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('editingTableNumber') ? 'error-editingTableNumber' : '' }}" class="dark:bg-dark-900 shadow-theme-xs h-10 w-40 rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                                    <x-common.input-error for="editingTableNumber" />
                                @else
                                    <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $table->table_number }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $table->created_at?->format('d/m/Y H:i') }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                @if ($qrImageUrl)
                                    <button type="button" @click="open = true" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-2 py-1 text-xs font-medium text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                                        <img src="{{ $qrImageUrl }}" alt="QR" class="h-10 w-10" />
                                        <span>Lihat</span>
                                    </button>
                                @else
                                    <span class="text-sm text-gray-500 dark:text-gray-400">-</span>
                                @endif

                                <div x-show="open" x-cloak class="fixed inset-0 z-[100000] flex items-center justify-center bg-black/40 px-6">
                                    <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white p-5 shadow-xl dark:bg-gray-900">
                                        <div class="flex items-center justify-between">
                                            <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">QR Meja {{ $table->table_number }}</h3>
                                            <button type="button" @click="open=false" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">Tutup</button>
                                        </div>
                                        <div class="mt-4 flex items-center justify-center rounded-xl border border-gray-200 bg-gray-50 p-6 dark:border-gray-800 dark:bg-gray-950">
                                            @if ($qrImageUrl)
                                                <img src="{{ $qrImageUrl }}" alt="QR" class="h-56 w-56" />
                                            @endif
                                        </div>
                                        <div class="mt-4 flex flex-wrap items-center gap-2">
                                            @if ($qrUrl)
                                                <button type="button" @click="navigator.clipboard.writeText('{{ $qrUrl }}'); copied=true; setTimeout(()=>copied=false, 1200)" class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                                                    <span x-show="!copied">Copy Link</span>
                                                    <span x-show="copied" x-cloak>Copied</span>
                                                </button>
                                            @endif
                                            @if ($qrImageUrl)
                                                <a href="{{ $qrImageUrl }}" target="_blank" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-3 py-2 text-xs font-medium text-white transition">
                                                    Download SVG
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                @if ($qrUrl)
                                    <div class="max-w-[420px]">
                                        <p class="truncate text-sm text-gray-800 dark:text-white/90">{{ $qrUrl }}</p>
                                        <div class="mt-1 flex items-center gap-2">
                                            <button type="button" @click="navigator.clipboard.writeText('{{ $qrUrl }}'); copied=true; setTimeout(()=>copied=false, 1200)" class="text-xs font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300">
                                                <span x-show="!copied">Copy</span>
                                                <span x-show="copied" x-cloak>Copied</span>
                                            </button>
                                            <a href="{{ $qrUrl }}" target="_blank" class="text-xs font-medium text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">Open</a>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Belum dibuat</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                                <p class="text-sm text-gray-800 dark:text-white/90">{{ number_format((int) ($table->transactions_count ?? 0), 0, ',', '.') }}</p>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="inline-flex items-center gap-2">
                                    @if ($isEditing)
                                        @if ($canEdit)
                                            <button type="button" wire:click="updateTable" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-3 py-2 text-xs font-medium text-white transition">
                                                Simpan
                                            </button>
                                            <button type="button" wire:click="cancelEditTable" class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                                                Batal
                                            </button>
                                        @endif
                                    @else
                                        @if ($canEdit)
                                            <button type="button" wire:click="regenerateQr({{ (int) $table->id }})" class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                                                Regenerate QR
                                            </button>
                                            <button type="button" wire:click="startEditTable({{ (int) $table->id }})" class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                                                Edit
                                            </button>
                                            <button type="button" x-on:click.prevent="$dispatch('confirm', { message: 'Hapus meja ini?', method: 'deleteTable', args: [{{ (int) $table->id }}] })" class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                                                Hapus
                                            </button>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-common.empty-table-row colspan="5" message="Meja belum ada." />
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-200 px-5 py-4 dark:border-gray-800">
            {{ $tables->links('livewire.pagination.admin') }}
        </div>
    </div>

    <x-common.confirm-modal confirm-label="Ya, hapus" />
</div>
