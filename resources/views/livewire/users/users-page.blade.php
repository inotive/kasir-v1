<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Manajemen Pengguna</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Atur akun, peran, dan status akses untuk karyawan.</p>
        </div>
        @can('users.create')
            <button type="button" wire:click="openCreateModal" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex h-11 items-center justify-center rounded-lg px-4 text-sm font-semibold text-white transition">
                Tambah Pengguna
            </button>
        @endcan
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="custom-scrollbar overflow-x-auto">
            <table class="w-full table-auto">
                <thead>
                    <tr class="border-b border-gray-200 dark:divide-gray-800 dark:border-gray-800">
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Pengguna</th>
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Peran</th>
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Status</th>
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Login Terakhir</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($users as $user)
                        <tr>
                            <td class="px-5 py-4">
                                <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $user->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <p class="text-sm text-gray-800 dark:text-white/90">
                                    @php
                                        $roleNames = $user->roles->pluck('name')->all();
                                        $roleLabelList = array_map(fn (string $r) => \App\Helpers\RbacLabelHelper::role($r), $roleNames);
                                        $legacyRole = $user->role instanceof \App\Enums\UserRole ? $user->role->value : $user->role;
                                        $legacyRole = is_string($legacyRole) ? trim($legacyRole) : '';
                                        $displayRole = $roleLabelList !== [] ? implode(', ', $roleLabelList) : ($legacyRole !== '' ? \App\Helpers\RbacLabelHelper::role($legacyRole) : '-');
                                    @endphp
                                    {{ $displayRole }}
                                </p>
                            </td>
                            <td class="px-5 py-4">
                                <span class="rounded-full px-2 py-0.5 text-theme-xs font-medium {{ $user->is_active ? 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500' : 'bg-gray-50 text-gray-600 dark:bg-gray-500/15 dark:text-gray-400' }}">
                                    {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <p class="text-sm text-gray-800 dark:text-white/90">
                                    {{ $user->last_login_at ? $user->last_login_at->format('d M Y H:i') : '-' }}
                                </p>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="inline-flex items-center gap-2">
                                    @can('users.edit')
                                        <button type="button" wire:click="startEdit({{ (int) $user->id }})" class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                                            Ubah
                                        </button>
                                    @endcan
                                    @can('users.delete')
                                        <button type="button" x-on:click.prevent="$dispatch('confirm', { message: 'Hapus pengguna ini?', method: 'deleteUser', args: [{{ (int) $user->id }}] })" class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                                            Hapus
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-common.empty-table-row colspan="5" message="Belum ada pengguna." />
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-200 px-5 py-4 dark:border-gray-800">
            {{ $users->links('livewire.pagination.admin') }}
        </div>
    </div>

    @if ($createModalOpen)
        <div class="fixed inset-0 z-[100000] flex items-center justify-center p-4" aria-modal="true" role="dialog">
            <div class="absolute inset-0 bg-black/50" wire:click="closeCreateModal"></div>
            <div class="relative w-full max-w-2xl overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <div>
                        <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Tambah Pengguna</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Buat akun baru, pilih peran, lalu atur aksesnya.</p>
                    </div>
                    <button type="button" wire:click="closeCreateModal" class="text-sm font-medium text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
                        Tutup
                    </button>
                </div>

                <form wire:submit="createUser" autocomplete="off" class="space-y-4 p-5">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Nama</label>
                            <input wire:model.live="name" type="text" aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('name') ? 'error-name' : '' }}" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                            <x-common.input-error for="name" />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Email</label>
                            <input wire:model.live="email" type="email" autocomplete="off" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('email') ? 'error-email' : '' }}" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                            <x-common.input-error for="email" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Peran</label>
                            <select wire:key="create-role-select" wire:model.live="role" aria-invalid="{{ $errors->has('role') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('role') ? 'error-role' : '' }}" class="shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-white px-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                                <option value="">Pilih peran</option>
                                @foreach ($roleLabels as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-common.input-error for="role" />
                        </div>
                        <div class="flex items-end">
                            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <input wire:model.live="isActive" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500/20 dark:border-gray-700" />
                                Aktif
                            </label>
                            <x-common.input-error for="isActive" class="ml-3 text-xs text-error-600" />
                        </div>
                        @if ($createNeedsManagerPin)
                            <div class="sm:col-span-2">
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">PIN Manager</label>
                                <input wire:model.live="managerPin" type="password" inputmode="numeric" autocomplete="new-password" aria-invalid="{{ $errors->has('managerPin') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('managerPin') ? 'error-managerPin' : '' }}" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" placeholder="4–8 angka" />
                                <x-common.input-error for="managerPin" />
                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Wajib untuk peran yang dapat menyetujui pembatalan/refund.</p>
                            </div>
                        @else
                            <div class="sm:col-span-2 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-xs text-gray-600 dark:border-gray-800 dark:bg-white/[0.03] dark:text-gray-400">
                                PIN akan diminta jika peran yang dipilih memiliki akses penyetujuan pembatalan/refund.
                            </div>
                        @endif
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Password</label>
                            <input wire:model.live="password" type="password" autocomplete="new-password" aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('password') ? 'error-password' : '' }}" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                            <x-common.input-error for="password" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Konfirmasi Password</label>
                            <input wire:model.live="passwordConfirmation" type="password" autocomplete="new-password" aria-invalid="{{ $errors->has('passwordConfirmation') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('passwordConfirmation') ? 'error-passwordConfirmation' : '' }}" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                            <x-common.input-error for="passwordConfirmation" />
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2">
                        <button type="button" wire:click="closeCreateModal" class="shadow-theme-xs inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
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

    @if ($editModalOpen)
        <div class="fixed inset-0 z-[100000] flex items-center justify-center p-4" aria-modal="true" role="dialog">
            <div class="absolute inset-0 bg-black/50" wire:click="closeEditModal"></div>
            <div class="relative w-full max-w-2xl overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <div>
                        <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Ubah Pengguna</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Ubah profil, peran, dan status akses.</p>
                    </div>
                    <button type="button" wire:click="closeEditModal" class="text-sm font-medium text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
                        Tutup
                    </button>
                </div>

                <form wire:submit="updateUser" autocomplete="off" class="space-y-4 p-5">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Nama</label>
                            <input wire:model.live="editingName" type="text" aria-invalid="{{ $errors->has('editingName') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('editingName') ? 'error-editingName' : '' }}" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                            <x-common.input-error for="editingName" />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Email</label>
                            <input wire:model.live="editingEmail" type="email" autocomplete="off" aria-invalid="{{ $errors->has('editingEmail') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('editingEmail') ? 'error-editingEmail' : '' }}" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                            <x-common.input-error for="editingEmail" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Peran</label>
                            <select wire:key="edit-role-select" wire:model.live="editingRole" aria-invalid="{{ $errors->has('editingRole') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('editingRole') ? 'error-editingRole' : '' }}" class="shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-white px-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                                <option value="">Pilih peran</option>
                                @foreach ($roleLabels as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-common.input-error for="editingRole" />
                        </div>
                        <div class="flex items-end">
                            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <input wire:model.live="editingIsActive" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500/20 dark:border-gray-700" />
                                Aktif
                            </label>
                            <x-common.input-error for="editingIsActive" class="ml-3 text-xs text-error-600" />
                        </div>
                        @if ($editNeedsManagerPin)
                            <div class="sm:col-span-2">
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">PIN Manager</label>
                                <input wire:model.live="editingManagerPin" type="password" inputmode="numeric" autocomplete="new-password" aria-invalid="{{ $errors->has('editingManagerPin') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('editingManagerPin') ? 'error-editingManagerPin' : '' }}" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" placeholder="4–8 angka" />
                                <x-common.input-error for="editingManagerPin" />
                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Status PIN: {{ $editingManagerPinIsSet ? 'Sudah diset' : 'Belum diset' }}. Jika diisi, PIN akan diganti.</p>
                            </div>
                        @else
                            <div class="sm:col-span-2 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-xs text-gray-600 dark:border-gray-800 dark:bg-white/[0.03] dark:text-gray-400">
                                PIN Manager tidak diperlukan untuk role ini.
                            </div>
                        @endif
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Password Baru (Opsional)</label>
                            <input wire:model.live="editingPassword" type="password" autocomplete="new-password" aria-invalid="{{ $errors->has('editingPassword') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('editingPassword') ? 'error-editingPassword' : '' }}" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                            <x-common.input-error for="editingPassword" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Konfirmasi Password</label>
                            <input wire:model.live="editingPasswordConfirmation" type="password" autocomplete="new-password" aria-invalid="{{ $errors->has('editingPasswordConfirmation') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('editingPasswordConfirmation') ? 'error-editingPasswordConfirmation' : '' }}" class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                            <x-common.input-error for="editingPasswordConfirmation" />
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2">
                        <button type="button" wire:click="closeEditModal" class="shadow-theme-xs inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
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
