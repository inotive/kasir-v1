<div class="space-y-6">
    @php
        $canEditAll = auth()->user()?->can('settings.edit') ?? false;

        $canViewStore = $canEditAll || (auth()->user()?->can('settings.store.view') ?? false) || (auth()->user()?->can('settings.store.edit') ?? false);
        $canEditStore = $canEditAll || (auth()->user()?->can('settings.store.edit') ?? false);

        $canViewPrinters = $canEditAll || (auth()->user()?->can('settings.printers.view') ?? false) || (auth()->user()?->can('settings.printers.edit') ?? false);
        $canEditPrinters = $canEditAll || (auth()->user()?->can('settings.printers.edit') ?? false);

        $canViewSystem = $canEditAll || (auth()->user()?->can('settings.system.view') ?? false) || (auth()->user()?->can('settings.system.edit') ?? false);
        $canEditSystem = $canEditAll || (auth()->user()?->can('settings.system.edit') ?? false);

        $canViewPoints = $canEditAll || (auth()->user()?->can('settings.points.view') ?? false) || (auth()->user()?->can('settings.points.edit') ?? false);
        $canEditPoints = $canEditAll || (auth()->user()?->can('settings.points.edit') ?? false);

        $canViewTargets = $canEditAll || (auth()->user()?->can('settings.targets.view') ?? false) || (auth()->user()?->can('settings.targets.edit') ?? false);
        $canEditTargets = $canEditAll || (auth()->user()?->can('settings.targets.edit') ?? false);

        $canViewAnySection = $canViewStore || $canViewPrinters || $canViewSystem || $canViewPoints || $canViewTargets;
    @endphp

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Pengaturan</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Kelola konfigurasi toko, pajak, pembayaran, dan printer.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
        <div class="lg:col-span-3">
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Menu</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Pilih area pengaturan.</p>
                </div>
                <div class="space-y-2 p-3">
                    @if ($canViewStore)
                        <button type="button" wire:click="setSection('store')" @class([
                            'w-full rounded-xl border px-4 py-3 text-left text-sm font-semibold transition',
                            'bg-brand-500 text-white border-brand-600' => $activeSection === 'store',
                            'bg-white text-gray-700 border-gray-200 hover:bg-gray-50 dark:bg-gray-900 dark:text-gray-300 dark:border-gray-800 dark:hover:bg-white/[0.03]' => $activeSection !== 'store',
                        ])>
                            Pengaturan Toko
                            <p class="mt-1 text-xs font-normal opacity-90">Info toko, pajak, dan status gateway.</p>
                        </button>
                    @endif
                    @if ($canViewPrinters)
                        <button type="button" wire:click="setSection('printers')" @class([
                            'w-full rounded-xl border px-4 py-3 text-left text-sm font-semibold transition',
                            'bg-brand-500 text-white border-brand-600' => $activeSection === 'printers',
                            'bg-white text-gray-700 border-gray-200 hover:bg-gray-50 dark:bg-gray-900 dark:text-gray-300 dark:border-gray-800 dark:hover:bg-white/[0.03]' => $activeSection !== 'printers',
                        ])>
                            Sumber Printer
                            <p class="mt-1 text-xs font-normal opacity-90">Kelola kategori printer dan setup perangkat.</p>
                        </button>
                    @endif
                    @if ($canViewSystem)
                        <button type="button" wire:click="setSection('system')" @class([
                            'w-full rounded-xl border px-4 py-3 text-left text-sm font-semibold transition',
                            'bg-brand-500 text-white border-brand-600' => $activeSection === 'system',
                            'bg-white text-gray-700 border-gray-200 hover:bg-gray-50 dark:bg-gray-900 dark:text-gray-300 dark:border-gray-800 dark:hover:bg-white/[0.03]' => $activeSection !== 'system',
                        ])>
                            Sistem
                            <p class="mt-1 text-xs font-normal opacity-90">Pengaturan umum POS dan operasional.</p>
                        </button>
                    @endif
                    @if ($canViewPoints)
                        <button type="button" wire:click="setSection('points')" @class([
                            'w-full rounded-xl border px-4 py-3 text-left text-sm font-semibold transition',
                            'bg-brand-500 text-white border-brand-600' => $activeSection === 'points',
                            'bg-white text-gray-700 border-gray-200 hover:bg-gray-50 dark:bg-gray-900 dark:text-gray-300 dark:border-gray-800 dark:hover:bg-white/[0.03]' => $activeSection !== 'points',
                        ])>
                            Poin & Member
                            <p class="mt-1 text-xs font-normal opacity-90">Konfigurasi loyalty point.</p>
                        </button>
                    @endif
                    @if ($canViewTargets)
                        <button type="button" wire:click="setSection('targets')" @class([
                            'w-full rounded-xl border px-4 py-3 text-left text-sm font-semibold transition',
                            'bg-brand-500 text-white border-brand-600' => $activeSection === 'targets',
                            'bg-white text-gray-700 border-gray-200 hover:bg-gray-50 dark:bg-gray-900 dark:text-gray-300 dark:border-gray-800 dark:hover:bg-white/[0.03]' => $activeSection !== 'targets',
                        ])>
                            Target Bulanan
                            <p class="mt-1 text-xs font-normal opacity-90">Target pendapatan per bulan untuk analisa.</p>
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <div class="lg:col-span-9">
            @if (! $canViewAnySection)
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white p-6 text-sm text-gray-700 dark:border-gray-800 dark:bg-white/[0.03] dark:text-gray-300">
                    Anda tidak memiliki akses ke bagian pengaturan manapun.
                </div>
            @elseif ($activeSection === 'store')
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                        <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Pengaturan Toko</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Informasi toko dan pajak yang dipakai di POS/struk.</p>
                    </div>

                    <form wire:submit="saveStoreSettings" class="space-y-5 p-5">
                        <div class="grid grid-cols-1 gap-5 md:grid-cols-12">
                            <div class="md:col-span-8">
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div class="sm:col-span-2">
                                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Nama Toko</label>
                                        <input wire:model.live="store_name" type="text" aria-invalid="{{ $errors->has('store_name') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('store_name') ? 'error-store_name' : '' }}" {{ $canEditStore ? '' : 'disabled' }} class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" placeholder="{{ config('app.name') }}" />
                                        <x-common.input-error for="store_name" />
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Telepon</label>
                                        <input wire:model.live="phone" type="text" aria-invalid="{{ $errors->has('phone') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('phone') ? 'error-phone' : '' }}" {{ $canEditStore ? '' : 'disabled' }} class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" placeholder="08xxxx" />
                                        <x-common.input-error for="phone" />
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Pajak PB1 (%)</label>
                                        <input wire:model.live="tax_rate" type="number" step="0.01" min="0" max="100" aria-invalid="{{ $errors->has('tax_rate') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('tax_rate') ? 'error-tax_rate' : '' }}" {{ $canEditStore ? '' : 'disabled' }} class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                                        <x-common.input-error for="tax_rate" />
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Alamat</label>
                                        <textarea wire:model.live="address" rows="3" aria-invalid="{{ $errors->has('address') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('address') ? 'error-address' : '' }}" {{ $canEditStore ? '' : 'disabled' }} class="dark:bg-dark-900 shadow-theme-xs w-full rounded-lg border border-gray-300 bg-transparent px-4 py-3 text-sm text-gray-800 disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></textarea>
                                        <x-common.input-error for="address" />
                                    </div>
                                </div>

                                <div class="mt-5 rounded-2xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-900">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <p class="text-sm font-semibold text-gray-800 dark:text-white/90">Payment Gateway</p>
                                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Mengaktifkan pembayaran online bila sudah dikonfigurasi.</p>
                                        </div>
                                        <label class="inline-flex cursor-pointer items-center gap-2">
                                            <input wire:model.live="payment_gateway_enabled" type="checkbox" {{ $canEditStore ? '' : 'disabled' }} class="h-5 w-5 rounded border-gray-300 text-brand-600 focus:ring-brand-500/20 disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-700 dark:bg-gray-900" />
                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $payment_gateway_enabled ? 'Aktif' : 'Nonaktif' }}</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="md:col-span-4">
                                <div class="space-y-3 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
                                    <p class="text-sm font-semibold text-gray-800 dark:text-white/90">Logo Toko</p>
                                    @if ($this->storeLogoUrl())
                                        <div class="flex items-center justify-center rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-900">
                                            <img src="{{ $this->storeLogoUrl() }}" alt="Logo" class="h-24 w-24 rounded-xl object-cover" />
                                        </div>
                                    @else
                                        <div class="flex items-center justify-center rounded-xl border border-dashed border-gray-300 bg-gray-50 p-6 text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">
                                            Belum ada logo
                                        </div>
                                    @endif
                                    <div>
                                        <input wire:model="store_logo_upload" type="file" accept="image/*" {{ $canEditStore ? '' : 'disabled' }} class="block w-full text-sm text-gray-600 disabled:cursor-not-allowed disabled:opacity-60 file:mr-4 file:rounded-lg file:border-0 file:bg-brand-500 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-brand-600 dark:text-gray-300" />
                                        <x-common.input-error for="store_logo_upload" />
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Ukuran maksimal 2MB.</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            @if ($canEditStore)
                                <button type="submit" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex h-11 items-center justify-center rounded-lg px-4 text-sm font-semibold text-white transition">
                                    Simpan Perubahan
                                </button>
                            @endif
                        </div>
                    </form>
                </div>
            @elseif ($activeSection === 'printers')
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                        <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Sumber Printer</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Kelola daftar sumber printer dan setup perangkat per sumber.</p>
                    </div>
                    <div class="space-y-6 p-5">
                        <x-common.input-error for="printer" class="text-sm text-error-600" />

                        <form wire:submit="savePrinterSettings" class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p class="text-sm font-semibold text-gray-800 dark:text-white/90">Struk Kasir</p>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Atur apakah logo toko dicetak pada struk tipe kasir.</p>
                                </div>
                            @if ($canEditPrinters)
                                    <button type="submit" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex h-10 items-center justify-center rounded-lg px-4 text-xs font-semibold text-white transition">
                                        Simpan
                                    </button>
                                @endif
                            </div>
                            <div class="mt-3">
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <input wire:model.live="cashier_receipt_print_logo" type="checkbox" {{ $canEditPrinters ? '' : 'disabled' }} class="h-5 w-5 rounded border-gray-300 text-brand-600 focus:ring-brand-500/20 disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-700 dark:bg-gray-900" />
                                    Cetak logo pada struk kasir
                                </label>
                                <x-common.input-error for="cashier_receipt_print_logo" />
                            </div>
                        </form>

                        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                            <div class="flex flex-col justify-between gap-4 border-b border-gray-200 px-5 py-4 lg:flex-row lg:items-center dark:border-gray-800">
                                <form wire:submit="createPrinterSource" class="grid w-full grid-cols-1 gap-3 lg:w-auto lg:grid-cols-6 lg:items-end">
                                    <div class="lg:col-span-3">
                                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Nama</label>
                                        <input wire:model.live="printerName" type="text" aria-invalid="{{ $errors->has('printerName') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('printerName') ? 'error-printerName' : '' }}" {{ $canEditPrinters ? '' : 'disabled' }} class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" placeholder="Contoh: Printer Kasir" />
                                        <x-common.input-error for="printerName" />
                                    </div>
                                    <div class="lg:col-span-2">
                                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Tipe</label>
                                        <select wire:model.live="printerType" aria-invalid="{{ $errors->has('printerType') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('printerType') ? 'error-printerType' : '' }}" {{ $canEditPrinters ? '' : 'disabled' }} class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                            <option value="Kasir">Kasir</option>
                                            <option value="Dapur">Dapur</option>
                                            <option value="Checker">Checker</option>
                                        </select>
                                        <x-common.input-error for="printerType" />
                                    </div>
                                    <div class="lg:col-span-1">
                                        @if ($canEditPrinters)
                                            <button type="submit" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex h-11 w-full items-center justify-center rounded-lg px-4 text-sm font-semibold text-white transition">
                                                Tambah
                                            </button>
                                        @endif
                                    </div>
                                </form>
                            </div>

                            <div class="custom-scrollbar overflow-x-auto">
                                <table class="w-full table-auto">
                                    <thead>
                                        <tr class="border-b border-gray-200 dark:divide-gray-800 dark:border-gray-800">
                                            <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Sumber</th>
                                            <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Tipe</th>
                                            <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Dipakai Produk</th>
                                            <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                        @forelse ($this->printerSourcesForUi() as $source)
                                            @php($isEditing = $editingPrinterSourceId === (int) $source->id)
                                            <tr>
                                                <td class="px-5 py-4">
                                                    @if ($isEditing)
                                                        <input wire:model.live="editingPrinterName" type="text" aria-invalid="{{ $errors->has('editingPrinterName') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('editingPrinterName') ? 'error-editingPrinterName' : '' }}" class="dark:bg-dark-900 shadow-theme-xs h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                                                        <x-common.input-error for="editingPrinterName" />
                                                    @else
                                                        <p class="text-sm font-semibold text-gray-800 dark:text-white/90">{{ $source->name }}</p>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400">source-{{ (int) $source->id }}</p>
                                                    @endif
                                                </td>
                                                <td class="px-5 py-4">
                                                    @if ($isEditing)
                                                        <input wire:model.live="editingPrinterType" type="text" aria-invalid="{{ $errors->has('editingPrinterType') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('editingPrinterType') ? 'error-editingPrinterType' : '' }}" class="dark:bg-dark-900 shadow-theme-xs h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                                                        <x-common.input-error for="editingPrinterType" />
                                                    @else
                                                        <p class="text-sm text-gray-800 dark:text-white/90">{{ $source->type }}</p>
                                                    @endif
                                                </td>
                                                <td class="px-5 py-4 text-right">
                                                    <p class="text-sm text-gray-800 dark:text-white/90">{{ number_format((int) ($source->products_count ?? 0), 0, ',', '.') }}</p>
                                                </td>
                                                <td class="px-5 py-4 text-right">
                                                    <div class="inline-flex items-center gap-2">
                                                        @if ($isEditing)
                                                            @if ($canEditPrinters)
                                                                <button type="button" wire:click="updatePrinterSource" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-3 py-2 text-xs font-semibold text-white transition">
                                                                    Simpan
                                                                </button>
                                                                <button type="button" wire:click="cancelEditPrinterSource" class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                                                                    Batal
                                                                </button>
                                                            @endif
                                                        @else
                                                            @if ($canEditPrinters)
                                                                <button type="button" wire:click="startEditPrinterSource({{ (int) $source->id }})" class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                                                                    Edit
                                                                </button>
                                                                <button type="button" x-on:click.prevent="$dispatch('confirm', { message: 'Hapus sumber printer ini?', method: 'deletePrinterSource', args: [{{ (int) $source->id }}] })" class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                                                                    Hapus
                                                                </button>
                                                            @endif
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <x-common.empty-table-row colspan="4" message="Sumber printer belum ada." />
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        @if ($canEditPrinters)
                            <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]" x-data="printerSystem">
                            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <h4 class="text-base font-semibold text-gray-800 dark:text-white/90">Setup Perangkat Printer</h4>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Konfigurasi printer Bluetooth untuk setiap sumber.</p>
                                    </div>
                                    <button type="button" @click="isOpen = !isOpen" class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                                        <span x-text="isOpen ? 'Sembunyikan' : 'Buka Setup'"></span>
                                    </button>
                                </div>
                            </div>

                            <div x-show="isOpen" x-cloak class="space-y-4 p-5">
                                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                                    Setup printer membutuhkan browser yang mendukung Web Bluetooth (umumnya Chrome). Pastikan printer menyala dan berada dekat perangkat.
                                </div>

                                <div class="custom-scrollbar overflow-x-auto">
                                    <table class="w-full table-auto">
                                        <thead>
                                            <tr class="border-b border-gray-200 dark:border-gray-800">
                                                <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Sumber</th>
                                                <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Perangkat</th>
                                                <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Status</th>
                                                <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                            <template x-for="(cfg, role) in printers" :key="role">
                                                <tr>
                                                    <td class="px-5 py-4">
                                                        <p class="text-sm font-semibold text-gray-800 dark:text-white/90" x-text="cfg.label"></p>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400" x-text="role"></p>
                                                    </td>
                                                    <td class="px-5 py-4">
                                                        <p class="text-sm text-gray-800 dark:text-white/90" x-text="cfg.deviceName"></p>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400" x-text="cfg.type"></p>
                                                    </td>
                                                    <td class="px-5 py-4 whitespace-nowrap">
                                                        <span class="rounded-full px-3 py-1 text-xs font-semibold"
                                                            :class="cfg.status === 'Terhubung' ? 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400' : 'bg-error-50 text-error-700 dark:bg-error-500/15 dark:text-error-400'"
                                                            x-text="cfg.status"></span>
                                                    </td>
                                                    <td class="px-5 py-4 text-right">
                                                        <div class="inline-flex items-center gap-2">
                                                            <button type="button" @click="setup(role)" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-3 py-2 text-xs font-semibold text-white transition">
                                                                Setup
                                                            </button>
                                                            <button type="button" @click="testPrint(role)" class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                                                                Test
                                                            </button>
                                                            <button type="button" @click="forget(role)" class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                                                                Lupa
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            @elseif ($activeSection === 'system')
                <div wire:key="settings-section-system" class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                        <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Sistem</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Pengaturan operasional sistem.</p>
                    </div>
                    <form wire:key="settings-form-system" wire:submit="saveSystemSettings" class="space-y-5 p-5">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Pembulatan Total</label>
                                <input wire:model.live="rounding_base" type="number" min="0" step="1" aria-invalid="{{ $errors->has('rounding_base') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('rounding_base') ? 'error-rounding_base' : '' }}" {{ $canEditSystem ? '' : 'disabled' }} class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                                <x-common.input-error for="rounding_base" />
                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Isi 100 untuk pembulatan ke 100 terdekat. Isi 0 untuk mematikan pembulatan.</p>
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Nama Default Pelanggan</label>
                                <input wire:model.live="pos_default_customer_name" type="text" aria-invalid="{{ $errors->has('pos_default_customer_name') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('pos_default_customer_name') ? 'error-pos_default_customer_name' : '' }}" {{ $canEditSystem ? '' : 'disabled' }} class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                                <x-common.input-error for="pos_default_customer_name" />
                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Dipakai saat transaksi POS tanpa input nama.</p>
                            </div>

                            <div class="md:col-span-2">
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Metode Pembayaran Default</label>
                                <select wire:model.live="pos_default_payment_method" aria-invalid="{{ $errors->has('pos_default_payment_method') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('pos_default_payment_method') ? 'error-pos_default_payment_method' : '' }}" {{ $canEditSystem ? '' : 'disabled' }} class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                    <option value="cash">Tunai</option>
                                    <option value="qris">QRIS</option>
                                    <option value="transfer_bank">Transfer Bank</option>
                                    <option value="gofood">GoFood</option>
                                    <option value="grab_food">GrabFood</option>
                                    <option value="shopee_food">ShopeeFood</option>
                                </select>
                                <x-common.input-error for="pos_default_payment_method" />
                            </div>
                        </div>

                        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
                            <div class="mb-3">
                                <h4 class="text-base font-semibold text-gray-800 dark:text-white/90">Koreksi Transaksi</h4>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Atur batasan cepat di POS dan kapan butuh approval (PIN).</p>
                            </div>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div class="md:col-span-2 rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                                    Approver ditentukan oleh permission: transactions.void.approve dan transactions.refund.approve. PIN diatur di Manajemen User.
                                </div>

                                <div class="md:col-span-2">
                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                        <input wire:model.live="corrections_void_pending_requires_approval" type="checkbox" {{ $canEditSystem ? '' : 'disabled' }} class="h-5 w-5 rounded border-gray-300 text-brand-600 focus:ring-brand-500/20 disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-700 dark:bg-gray-900" />
                                        Void transaksi pending wajib approval (PIN)
                                    </label>
                                    <x-common.input-error for="corrections_void_pending_requires_approval" />
                                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Jika nonaktif, transaksi pending bisa di-void cepat oleh role yang berhak.</p>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                        <input wire:model.live="corrections_refund_requires_approval_for_cash" type="checkbox" {{ $canEditSystem ? '' : 'disabled' }} class="h-5 w-5 rounded border-gray-300 text-brand-600 focus:ring-brand-500/20 disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-700 dark:bg-gray-900" />
                                        Refund metode tunai wajib approval (PIN)
                                    </label>
                                    <x-common.input-error for="corrections_refund_requires_approval_for_cash" />
                                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Disarankan aktif untuk mencegah penyalahgunaan kas.</p>
                                </div>

                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Batas refund cepat tanpa approval (Rp)</label>
                                    <input 
                                        x-data="currencyInput($wire.entangle('corrections_refund_quick_max_amount'))"
                                        x-model="displayValue"
                                        @input="handleInput"
                                        type="text"
                                        inputmode="numeric" 
                                        aria-invalid="{{ $errors->has('corrections_refund_quick_max_amount') ? 'true' : 'false' }}"
                                        aria-describedby="{{ $errors->has('corrections_refund_quick_max_amount') ? 'error-corrections_refund_quick_max_amount' : '' }}"
                                        {{ $canEditSystem ? '' : 'disabled' }}
                                        class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" 
                                    />
                                    <x-common.input-error for="corrections_refund_quick_max_amount" />
                                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Refund di atas angka ini otomatis minta PIN.</p>
                                </div>

                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Maks refund cepat per hari per kasir</label>
                                    <input wire:model.live="corrections_refund_quick_max_count_per_day" type="number" min="0" step="1" aria-invalid="{{ $errors->has('corrections_refund_quick_max_count_per_day') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('corrections_refund_quick_max_count_per_day') ? 'error-corrections_refund_quick_max_count_per_day' : '' }}" {{ $canEditSystem ? '' : 'disabled' }} class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                                    <x-common.input-error for="corrections_refund_quick_max_count_per_day" />
                                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Jika sudah lewat, refund berikutnya minta PIN.</p>
                                </div>

                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Maks void cepat per hari per kasir</label>
                                    <input wire:model.live="corrections_void_quick_max_count_per_day" type="number" min="0" step="1" aria-invalid="{{ $errors->has('corrections_void_quick_max_count_per_day') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('corrections_void_quick_max_count_per_day') ? 'error-corrections_void_quick_max_count_per_day' : '' }}" {{ $canEditSystem ? '' : 'disabled' }} class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                                    <x-common.input-error for="corrections_void_quick_max_count_per_day" />
                                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Jika sudah lewat, void berikutnya minta PIN (jika diizinkan).</p>
                                </div>

                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Window void cepat (menit)</label>
                                    <input wire:model.live="corrections_void_quick_window_minutes" type="number" min="0" step="1" aria-invalid="{{ $errors->has('corrections_void_quick_window_minutes') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('corrections_void_quick_window_minutes') ? 'error-corrections_void_quick_window_minutes' : '' }}" {{ $canEditSystem ? '' : 'disabled' }} class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                                    <x-common.input-error for="corrections_void_quick_window_minutes" />
                                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Contoh 5 = void cepat hanya sampai 5 menit setelah transaksi dibuat. Isi 0 untuk mematikan.</p>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
                            <div class="mb-3">
                                <h4 class="text-base font-semibold text-gray-800 dark:text-white/90">Diskon Manual</h4>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Atur batas diskon per role dan kapan butuh approval (PIN).</p>
                            </div>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div class="md:col-span-2">
                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                        <input wire:model.live="discount_applies_before_tax" type="checkbox" {{ $canEditSystem ? '' : 'disabled' }} class="h-5 w-5 rounded border-gray-300 text-brand-600 focus:ring-brand-500/20 disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-700 dark:bg-gray-900" />
                                        Diskon mengurangi dasar pajak (pajak dihitung setelah diskon)
                                    </label>
                                    <x-common.input-error for="discount_applies_before_tax" />
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            @if ($canEditSystem)
                                <button type="submit" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex h-11 items-center justify-center rounded-lg px-4 text-sm font-semibold text-white transition">
                                    Simpan Perubahan
                                </button>
                            @endif
                        </div>
                    </form>
                </div>
            @elseif ($activeSection === 'points')
                <div wire:key="settings-section-points" class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                        <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Poin & Member</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Konfigurasi sistem poin dan member.</p>
                    </div>

                    <form wire:key="settings-form-points" wire:submit="savePointSettings" class="space-y-5 p-5">
                        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
                            <div class="mb-3">
                                <h4 class="text-base font-semibold text-gray-800 dark:text-white/90">Earning (Mendapatkan Poin)</h4>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Atur berapa nilai transaksi untuk mendapatkan 1 poin.</p>
                            </div>
                            
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Rate Earning (Rp per 1 Poin)</label>
                                <input 
                                    x-data="currencyInput($wire.entangle('point_earning_rate'))"
                                    x-model="displayValue"
                                    @input="handleInput"
                                    type="text"
                                    inputmode="numeric"
                                    aria-invalid="{{ $errors->has('point_earning_rate') ? 'true' : 'false' }}"
                                    aria-describedby="{{ $errors->has('point_earning_rate') ? 'error-point_earning_rate' : '' }}"
                                    {{ $canEditPoints ? '' : 'disabled' }}
                                    class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" 
                                />
                                <x-common.input-error for="point_earning_rate" />
                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Contoh: Isi 10000 artinya setiap belanja Rp 10.000 dapat 1 poin. Isi 0 untuk menonaktifkan earning.</p>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
                            <div class="mb-3">
                                <h4 class="text-base font-semibold text-gray-800 dark:text-white/90">Redemption (Penukaran Poin)</h4>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Atur nilai tukar poin menjadi diskon.</p>
                            </div>
                            
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Nilai 1 Poin (Rp)</label>
                                    <input 
                                        x-data="currencyInput($wire.entangle('point_redemption_value'))"
                                        x-model="displayValue"
                                        @input="handleInput"
                                        type="text"
                                        inputmode="numeric"
                                        aria-invalid="{{ $errors->has('point_redemption_value') ? 'true' : 'false' }}"
                                        aria-describedby="{{ $errors->has('point_redemption_value') ? 'error-point_redemption_value' : '' }}"
                                        {{ $canEditPoints ? '' : 'disabled' }}
                                        class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" 
                                    />
                                    <x-common.input-error for="point_redemption_value" />
                                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Contoh: Isi 1 artinya 1 poin bernilai diskon Rp 1.</p>
                                </div>

                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Minimal Poin Ditukar</label>
                                    <input wire:model.live="min_redemption_points" type="number" min="0" step="1" aria-invalid="{{ $errors->has('min_redemption_points') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('min_redemption_points') ? 'error-min_redemption_points' : '' }}" {{ $canEditPoints ? '' : 'disabled' }} class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                                    <x-common.input-error for="min_redemption_points" />
                                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Member harus punya minimal poin segini untuk bisa menukar.</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            @if ($canEditPoints)
                                <button type="submit" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex h-11 items-center justify-center rounded-lg px-4 text-sm font-semibold text-white transition">
                                    Simpan Perubahan
                                </button>
                            @endif
                        </div>
                    </form>
                </div>
            @elseif ($activeSection === 'targets')
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                        <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Target Bulanan</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Atur target pendapatan per bulan untuk dashboard dan laporan.</p>
                    </div>

                    <div class="space-y-5 p-5">
                        <div class="grid grid-cols-1 gap-5 md:grid-cols-12">
                            <div class="md:col-span-5">
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Bulan</label>
                                <select wire:model.live="monthlyTargetMonth" aria-invalid="{{ $errors->has('monthlyTargetMonth') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('monthlyTargetMonth') ? 'error-monthlyTargetMonth' : '' }}" {{ $canEditTargets ? '' : 'disabled' }} class="dark:bg-dark-900 shadow-theme-xs h-11 w-full appearance-none rounded-lg border border-gray-300 bg-white px-4 py-2.5 pr-11 text-sm text-gray-700 focus:ring-3 focus:outline-hidden disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                    @foreach ([1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'] as $m => $label)
                                        <option value="{{ (int) $m }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                <x-common.input-error for="monthlyTargetMonth" />
                            </div>
                            <div class="md:col-span-3">
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Tahun</label>
                                <input wire:model.live="monthlyTargetYear" type="number" min="2000" max="2100" step="1" aria-invalid="{{ $errors->has('monthlyTargetYear') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('monthlyTargetYear') ? 'error-monthlyTargetYear' : '' }}" {{ $canEditTargets ? '' : 'disabled' }} class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                                <x-common.input-error for="monthlyTargetYear" />
                            </div>
                            <div class="md:col-span-4">
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Target Pendapatan (Rp)</label>
                                <div x-data="currencyInput($wire.entangle('monthlyTargetAmount'))">
                                    <input x-model="displayValue" @input="handleInput" type="text" inputmode="numeric" aria-invalid="{{ $errors->has('monthlyTargetAmount') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('monthlyTargetAmount') ? 'error-monthlyTargetAmount' : '' }}" {{ $canEditTargets ? '' : 'disabled' }} class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                                </div>
                                <x-common.input-error for="monthlyTargetAmount" />
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-2">
                            @if ($canEditTargets)
                                <button type="button" wire:click="saveMonthlyTarget" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex h-11 items-center justify-center rounded-lg px-5 text-sm font-semibold text-white transition">
                                    Simpan Target
                                </button>
                            @endif
                        </div>

                        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                                <h4 class="text-sm font-semibold text-gray-800 dark:text-white/90">Daftar Target</h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Klik baris untuk mengedit, atau hapus target bila tidak digunakan.</p>
                            </div>
                            <div class="custom-scrollbar overflow-x-auto">
                                <table class="w-full table-auto">
                                    <thead>
                                        <tr class="border-b border-gray-200 dark:divide-gray-800 dark:border-gray-800">
                                            <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Periode</th>
                                            <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Target</th>
                                            <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                        @forelse ($monthlyTargets as $row)
                                            <tr
                                                class="{{ $canEditTargets ? 'cursor-pointer hover:bg-gray-50 dark:hover:bg-white/[0.02]' : '' }}"
                                                @if ($canEditTargets)
                                                    wire:click="selectMonthlyTarget({{ (int) ($row['year'] ?? 0) }}, {{ (int) ($row['month'] ?? 0) }})"
                                                @endif
                                            >
                                                <td class="px-5 py-4">
                                                    <p class="text-sm font-medium text-gray-800 dark:text-white/90">
                                                        {{ \Carbon\Carbon::create((int) ($row['year'] ?? 0), max(1, (int) ($row['month'] ?? 1)), 1)->translatedFormat('F Y') }}
                                                    </p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                                        {{ sprintf('%04d-%02d', (int) ($row['year'] ?? 0), (int) ($row['month'] ?? 0)) }}
                                                    </p>
                                                </td>
                                                <td class="px-5 py-4 text-right">
                                                    <p class="text-sm font-semibold text-gray-800 dark:text-white/90">Rp {{ number_format((int) ($row['amount'] ?? 0), 0, ',', '.') }}</p>
                                                </td>
                                                <td class="px-5 py-4 text-right">
                                                    @if ($canEditTargets)
                                                        <button
                                                            type="button"
                                                            x-on:click.stop.prevent="$dispatch('confirm', { message: 'Hapus target {{ sprintf('%04d-%02d', (int) ($row['year'] ?? 0), (int) ($row['month'] ?? 0)) }}?', method: 'deleteMonthlyTarget', args: [{{ (int) ($row['year'] ?? 0) }}, {{ (int) ($row['month'] ?? 0) }}] })"
                                                            class="rounded-lg bg-error-50 px-3 py-2 text-xs font-medium text-error-600 hover:bg-error-100 dark:bg-error-500/10 dark:text-error-500 dark:hover:bg-error-500/20"
                                                        >
                                                            Hapus
                                                        </button>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <x-common.empty-table-row colspan="3" message="Belum ada target." />
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <x-common.confirm-modal confirm-label="Ya, hapus" />
</div>
