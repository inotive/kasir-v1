<div class="space-y-6">
    <div>
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Wilayah Member</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400">Master wilayah provinsi, kabupaten/kota, dan kecamatan.</p>
    </div>

    <x-common.input-error for="regions" class="text-sm text-error-600" />

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-col justify-between gap-4 border-b border-gray-200 px-5 py-4 sm:flex-row sm:items-center dark:border-gray-800">
            <div class="relative flex-1 sm:flex-none">
                <span class="absolute top-1/2 left-4 -translate-y-1/2 text-gray-500 dark:text-gray-400">
                    <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M3.04199 9.37363C3.04199 5.87693 5.87735 3.04199 9.37533 3.04199C12.8733 3.04199 15.7087 5.87693 15.7087 9.37363C15.7087 12.8703 12.8733 15.7053 9.37533 15.7053C5.87735 15.7053 3.04199 12.8703 3.04199 9.37363ZM9.37533 1.54199C5.04926 1.54199 1.54199 5.04817 1.54199 9.37363C1.54199 13.6991 5.04926 17.2053 9.37533 17.2053C11.2676 17.2053 13.0032 16.5344 14.3572 15.4176L17.1773 18.238C17.4702 18.5309 17.945 18.5309 18.2379 18.238C18.5308 17.9451 18.5309 17.4703 18.238 17.1773L15.4182 14.3573C16.5367 13.0033 17.2087 11.2669 17.2087 9.37363C17.2087 5.04817 13.7014 1.54199 9.37533 1.54199Z" fill="" />
                    </svg>
                </span>
                <input wire:model.live.debounce.400ms="search" type="text" placeholder="Cari provinsi/kab/kota/kecamatan..." class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent py-2.5 pr-4 pl-11 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden sm:w-[360px] sm:min-w-[360px] dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
            </div>

            @can('members.regions.manage')
            <div class="flex justify-end gap-2">
                <a target="_blank" href="https://rkurniawan.blog/2025/05/01/unduh-file-geojson-batas-administrasi-pemekaran-38-provinsi-indonesia/" class="bg-gray-600 shadow-theme-xs hover:bg-gray-700 inline-flex h-11 items-center justify-center rounded-lg px-4 text-sm font-semibold text-white transition">
                    Download File Import
                </a>
                <button type="button" wire:click="openImportModal" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex h-11 items-center justify-center rounded-lg px-4 text-sm font-semibold text-white transition">
                    Import Wilayah
                </button>
            </div>
            @endcan
        </div>

        <div class="custom-scrollbar overflow-x-auto">
            <table class="w-full table-auto">
                <thead>
                    <tr class="border-b border-gray-200 dark:divide-gray-800 dark:border-gray-800">
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Provinsi</th>
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Kab/Kota</th>
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Kecamatan</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Member</th>
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">GeoJSON</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($regions as $region)
                        <tr>
                            <td class="px-5 py-4">
                                <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $region->province }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <p class="text-sm text-gray-800 dark:text-white/90">{{ $region->regency }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <p class="text-sm text-gray-800 dark:text-white/90">{{ $region->district ?? '-' }}</p>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <p class="text-sm text-gray-800 dark:text-white/90">{{ number_format((int) ($region->members_count ?? 0), 0, ',', '.') }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <p class="text-sm text-gray-800 dark:text-white/90">{{ $region->geojson ? 'Ada' : '-' }}</p>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="inline-flex items-center gap-2">
                                    @can('members.regions.manage')
                                        <button type="button" x-on:click.prevent="$dispatch('confirm', { message: 'Hapus wilayah ini?', method: 'deleteRegion', args: [{{ (int) $region->id }}] })" class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                                            Hapus
                                        </button>
                                    @else
                                        <p class="text-sm text-gray-500 dark:text-gray-400">-</p>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-common.empty-table-row colspan="6" message="Wilayah belum ada." />
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-200 px-5 py-4 dark:border-gray-800">
            {{ $regions->links('livewire.pagination.admin') }}
        </div>
    </div>

    @if ($importModalOpen)
        <div class="fixed inset-0 z-[100000] flex items-center justify-center p-4" aria-modal="true" role="dialog">
            <div class="fixed inset-0 bg-black/50" wire:click="closeImportModal"></div>
            <div class="relative w-full max-w-2xl overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <div>
                        <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Import Wilayah</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Upload file GeoJSON untuk menambah wilayah.</p>
                    </div>
                    <button type="button" wire:click="closeImportModal" class="text-sm font-medium text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
                        Tutup
                    </button>
                </div>
                <form wire:submit="importGeojson" class="grid grid-cols-1 gap-4 p-5 sm:grid-cols-6 sm:items-end">
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Provinsi</label>
                        <input wire:model.defer="importProvince" type="text" aria-invalid="{{ $errors->has('importProvince') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('importProvince') ? 'error-importProvince' : '' }}" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" placeholder="Lampung" />
                        <x-common.input-error for="importProvince" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Kab/Kota</label>
                        <input wire:model.defer="importRegency" type="text" aria-invalid="{{ $errors->has('importRegency') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('importRegency') ? 'error-importRegency' : '' }}" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" placeholder="Lampung Timur" />
                        <x-common.input-error for="importRegency" />
                    </div>
                    <div class="sm:col-span-3">
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">GeoJSON Kabupaten (Opsional)</label>
                        <input wire:model="importRegencyGeojson" type="file" accept=".json,.geojson" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                        <x-common.input-error for="importRegencyGeojson" />
                    </div>
                    <div class="sm:col-span-3">
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">GeoJSON Kecamatan</label>
                        <input wire:model="importDistrictGeojson" type="file" accept=".json,.geojson" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                        <x-common.input-error for="importDistrictGeojson" />
                    </div>
                    <div class="sm:col-span-6 flex items-center justify-end gap-2">
                        <button type="button" wire:click="closeImportModal" class="shadow-theme-xs inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                            Batal
                        </button>
                        <button type="submit" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex h-11 items-center justify-center rounded-lg px-4 text-sm font-semibold text-white transition">
                            Import
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <x-common.confirm-modal confirm-label="Ya, hapus" />
</div>
