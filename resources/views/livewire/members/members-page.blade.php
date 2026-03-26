<div class="space-y-6">
    @php
        $canViewPii = auth()->user()?->can('members.pii.view') ?? false;
        $searchPlaceholder = $canViewPii ? 'Cari nama/email/telepon...' : 'Cari nama...';
    @endphp
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Member</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Kelola member dan pantau total transaksi per member.</p>
        </div>
    </div>

    <x-common.input-error for="members" class="text-sm text-error-600" />

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex flex-col justify-between gap-4 border-b border-gray-200 px-5 py-4 sm:flex-row sm:items-center dark:border-gray-800">
            <div class="relative flex-1 sm:flex-none">
                <span class="absolute top-1/2 left-4 -translate-y-1/2 text-gray-500 dark:text-gray-400">
                    <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M3.04199 9.37363C3.04199 5.87693 5.87735 3.04199 9.37533 3.04199C12.8733 3.04199 15.7087 5.87693 15.7087 9.37363C15.7087 12.8703 12.8733 15.7053 9.37533 15.7053C5.87735 15.7053 3.04199 12.8703 3.04199 9.37363ZM9.37533 1.54199C5.04926 1.54199 1.54199 5.04817 1.54199 9.37363C1.54199 13.6991 5.04926 17.2053 9.37533 17.2053C11.2676 17.2053 13.0032 16.5344 14.3572 15.4176L17.1773 18.238C17.4702 18.5309 17.945 18.5309 18.2379 18.238C18.5308 17.9451 18.5309 17.4703 18.238 17.1773L15.4182 14.3573C16.5367 13.0033 17.2087 11.2669 17.2087 9.37363C17.2087 5.04817 13.7014 1.54199 9.37533 1.54199Z" fill="" />
                    </svg>
                </span>
                <input wire:model.live.debounce.400ms="search" type="text" placeholder="{{ $searchPlaceholder }}" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent py-2.5 pr-4 pl-11 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden sm:w-[360px] sm:min-w-[360px] dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
            </div>

            @canany(['members.create'])
                @can('members.pii.view')
                    <button type="button" wire:click="openCreateMemberModal" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex h-11 items-center justify-center rounded-lg px-4 text-sm font-semibold text-white transition">
                        Tambah Member
                    </button>
                @endcan
            @endcanany
        </div>

        <div class="custom-scrollbar overflow-x-auto">
            <table class="w-full table-auto">
                <thead>
                    <tr class="border-b border-gray-200 dark:divide-gray-800 dark:border-gray-800">
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Member</th>
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Wilayah</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Poin</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Transaksi</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($members as $member)
                        @php
                            $isEditing = $editingMemberId === (int) $member->id;
                        @endphp
                        <tr>
                            <td class="px-5 py-4">
                                @if ($isEditing)
                                    <div class="space-y-2">
                                        <input wire:model.live="editingName" type="text" aria-invalid="{{ $errors->has('editingName') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('editingName') ? 'error-editingName' : '' }}" class="dark:bg-dark-900 shadow-theme-xs h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                                        <x-common.input-error for="editingName" class="text-xs text-error-600" />
                                        @if ($canViewPii)
                                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                                <div>
                                                    <input wire:model.live="editingEmail" type="email" placeholder="Email" aria-invalid="{{ $errors->has('editingEmail') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('editingEmail') ? 'error-editingEmail' : '' }}" class="dark:bg-dark-900 shadow-theme-xs h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                                                    <x-common.input-error for="editingEmail" />
                                                </div>
                                                <div>
                                                    <input wire:model.live="editingPhone" type="text" placeholder="Telepon" aria-invalid="{{ $errors->has('editingPhone') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('editingPhone') ? 'error-editingPhone' : '' }}" class="dark:bg-dark-900 shadow-theme-xs h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                                                    <x-common.input-error for="editingPhone" />
                                                </div>
                                            </div>
                                        @else
                                            <p class="text-sm text-gray-500 dark:text-gray-400">-</p>
                                        @endif
                                        <div>
                                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                                                <div>
                                                    <select wire:model.live="editingProvince" aria-invalid="{{ $errors->has('editingProvince') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('editingProvince') ? 'error-editingProvince' : '' }}" class="dark:bg-dark-900 shadow-theme-xs h-10 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                                        <option value="">-</option>
                                                        @foreach ($provinces as $p)
                                                            <option value="{{ $p }}">{{ $p }}</option>
                                                        @endforeach
                                                    </select>
                                                    <x-common.input-error for="editingProvince" />
                                                </div>
                                                <div>
                                                    <select wire:model.live="editingRegency" aria-invalid="{{ $errors->has('editingRegency') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('editingRegency') ? 'error-editingRegency' : '' }}" class="dark:bg-dark-900 shadow-theme-xs h-10 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" @disabled($editingProvince === null || $editingProvince === '')>
                                                        <option value="">-</option>
                                                        @foreach ($editingRegencies as $r)
                                                            <option value="{{ $r }}">{{ $r }}</option>
                                                        @endforeach
                                                    </select>
                                                    <x-common.input-error for="editingRegency" />
                                                </div>
                                                <div>
                                                    <select wire:model.live="editingMemberRegionId" aria-invalid="{{ $errors->has('editingMemberRegionId') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('editingMemberRegionId') ? 'error-editingMemberRegionId' : '' }}" class="dark:bg-dark-900 shadow-theme-xs h-10 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" @disabled($editingRegency === null || $editingRegency === '')>
                                                        <option value="">-</option>
                                                        @foreach ($editingDistricts as $d)
                                                            <option value="{{ $d->id }}">{{ $d->district }}</option>
                                                        @endforeach
                                                    </select>
                                                    <x-common.input-error for="editingMemberRegionId" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $member->name }}</p>
                                    @if ($canViewPii)
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $member->phone ?? $member->email ?? '-' }}</p>
                                    @else
                                        <p class="text-xs text-gray-500 dark:text-gray-400">-</p>
                                    @endif
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                @if ($isEditing)
                                    <p class="text-sm text-gray-400 dark:text-gray-500">-</p>
                                @else
                                    <p class="text-sm text-gray-800 dark:text-white/90">{{ $member->region ? ($member->region->district ?? '-') : '-' }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $member->region ? $member->region->regency : '' }}{{ $member->region ? ', '.$member->region->province : '' }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                                @if ($isEditing)
                                    <input wire:model.live="editingPoints" type="number" min="0" aria-invalid="{{ $errors->has('editingPoints') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('editingPoints') ? 'error-editingPoints' : '' }}" class="dark:bg-dark-900 shadow-theme-xs h-10 w-28 rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 text-right" />
                                    <x-common.input-error for="editingPoints" />
                                @else
                                    <p class="text-sm font-semibold text-gray-800 dark:text-white/90">{{ number_format((int) $member->points, 0, ',', '.') }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                                <p class="text-sm text-gray-800 dark:text-white/90">{{ number_format((int) ($member->transactions_count ?? 0), 0, ',', '.') }}</p>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="inline-flex items-center gap-2">
                                    @if ($isEditing)
                                        <button type="button" wire:click="updateMember" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-3 py-2 text-xs font-medium text-white transition">
                                            Simpan
                                        </button>
                                        <button type="button" wire:click="cancelEditMember" class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                                            Batal
                                        </button>
                                    @else
                                        @can('members.edit')
                                            @can('members.pii.view')
                                                <button type="button" wire:click="startEditMember({{ (int) $member->id }})" class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                                                    Edit
                                                </button>
                                            @endcan
                                        @endcan
                                        @can('members.delete')
                                            <button type="button" x-on:click.prevent="$dispatch('confirm', { message: 'Hapus member ini?', method: 'deleteMember', args: [{{ (int) $member->id }}] })" class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                                                Hapus
                                            </button>
                                        @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-common.empty-table-row colspan="5" message="Member belum ada." />
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-200 px-5 py-4 dark:border-gray-800">
            {{ $members->links('livewire.pagination.admin') }}
        </div>
    </div>

    @if ($createMemberModalOpen)
        <div class="fixed inset-0 z-[100000] flex items-center justify-center p-4" aria-modal="true" role="dialog">
            <div class="absolute inset-0 bg-black/50" wire:click="closeCreateMemberModal"></div>
            <div class="relative w-full max-w-2xl overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <div>
                        <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Tambah Member</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Isi data member baru.</p>
                    </div>
                    <button type="button" wire:click="closeCreateMemberModal" class="text-sm font-medium text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
                        Tutup
                    </button>
                </div>
                <form wire:submit="createMember" class="space-y-4 p-5">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Nama</label>
                            <input wire:model.live="name" type="text" aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('name') ? 'error-name' : '' }}" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                            <x-common.input-error for="name" />
                        </div>
                        @if ($canViewPii)
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Email</label>
                            <input wire:model.live="email" type="email" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('email') ? 'error-email' : '' }}" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                            <x-common.input-error for="email" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Telepon</label>
                            <input wire:model.live="phone" type="text" aria-invalid="{{ $errors->has('phone') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('phone') ? 'error-phone' : '' }}" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                            <x-common.input-error for="phone" />
                        </div>
                        @endif
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Provinsi</label>
                            <select wire:model.live="province" aria-invalid="{{ $errors->has('province') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('province') ? 'error-province' : '' }}" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                <option value="">-</option>
                                @foreach ($provinces as $p)
                                    <option value="{{ $p }}">{{ $p }}</option>
                                @endforeach
                            </select>
                            <x-common.input-error for="province" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Kab/Kota</label>
                            <select wire:model.live="regency" aria-invalid="{{ $errors->has('regency') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('regency') ? 'error-regency' : '' }}" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" @disabled($province === null || $province === '')>
                                <option value="">-</option>
                                @foreach ($regencies as $r)
                                    <option value="{{ $r }}">{{ $r }}</option>
                                @endforeach
                            </select>
                            <x-common.input-error for="regency" />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Kecamatan</label>
                            <select wire:model.live="memberRegionId" aria-invalid="{{ $errors->has('memberRegionId') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('memberRegionId') ? 'error-memberRegionId' : '' }}" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" @disabled($regency === null || $regency === '')>
                                <option value="">-</option>
                                @foreach ($districts as $d)
                                    <option value="{{ $d->id }}">{{ $d->district }}</option>
                                @endforeach
                            </select>
                            <x-common.input-error for="memberRegionId" />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Poin</label>
                            <input wire:model.live="points" type="number" min="0" aria-invalid="{{ $errors->has('points') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('points') ? 'error-points' : '' }}" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" placeholder="0" />
                            <x-common.input-error for="points" />
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2">
                        <button type="button" wire:click="closeCreateMemberModal" class="shadow-theme-xs inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
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

    <x-common.confirm-modal confirm-label="Ya, hapus" />
</div>
