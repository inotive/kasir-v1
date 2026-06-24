<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                {{ $isEditing ? 'Edit Tenant' : 'Buat Tenant Baru' }}</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ $isEditing ? 'Ubah informasi tenant.' : 'Buat bisnis baru dan akun owner.' }}</p>
        </div>
        <a href="{{ route('tenants.index') }}" wire:navigate
            class="shadow-theme-xs inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
            Kembali
        </a>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
        <form wire:submit="save" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="name" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Nama
                    Tenant</label>
                <input type="text" id="name" wire:model.live="name"
                    aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}"
                    aria-describedby="{{ $errors->has('name') ? 'error-name' : '' }}"
                    class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                <x-common.input-error for="name" />
            </div>
            <div>
                <label for="slug" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Slug
                    (subdomain)</label>
                <input type="text" id="slug" wire:model.live="slug" placeholder="warung-sate"
                    aria-invalid="{{ $errors->has('slug') ? 'true' : 'false' }}"
                    aria-describedby="{{ $errors->has('slug') ? 'error-slug' : '' }}"
                    class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                <x-common.input-error for="slug" />
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Akan digunakan sebagai subdomain: <strong
                        class="text-gray-700 dark:text-gray-300">{{ $slug ?: '{slug}' }}.domain-utama</strong></p>
            </div>
            <div class="sm:col-span-2">
                <label for="businessName" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Nama
                    Bisnis</label>
                <input type="text" id="businessName" wire:model.live="businessName"
                    aria-invalid="{{ $errors->has('businessName') ? 'true' : 'false' }}"
                    aria-describedby="{{ $errors->has('businessName') ? 'error-businessName' : '' }}"
                    class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                <x-common.input-error for="businessName" />
            </div>

            @unless ($isEditing)
                <div class="sm:col-span-2 border-t border-gray-200 pt-4 dark:border-gray-800">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-white/90">Akun Owner</h3>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Buat akun pemilik untuk tenant ini.</p>
                </div>

                <div>
                    <label for="ownerName" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Nama
                        Owner</label>
                    <input type="text" id="ownerName" wire:model.live="ownerName"
                        aria-invalid="{{ $errors->has('ownerName') ? 'true' : 'false' }}"
                        aria-describedby="{{ $errors->has('ownerName') ? 'error-ownerName' : '' }}"
                        class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                    <x-common.input-error for="ownerName" />
                </div>
                <div>
                    <label for="ownerEmail" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Email
                        Owner</label>
                    <input type="email" id="ownerEmail" wire:model.live="ownerEmail"
                        aria-invalid="{{ $errors->has('ownerEmail') ? 'true' : 'false' }}"
                        aria-describedby="{{ $errors->has('ownerEmail') ? 'error-ownerEmail' : '' }}"
                        class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                    <x-common.input-error for="ownerEmail" />
                </div>
                <div>
                    <label for="ownerPassword"
                        class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Password</label>
                    <input type="password" id="ownerPassword" wire:model.live="ownerPassword"
                        aria-invalid="{{ $errors->has('ownerPassword') ? 'true' : 'false' }}"
                        aria-describedby="{{ $errors->has('ownerPassword') ? 'error-ownerPassword' : '' }}"
                        class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                    <x-common.input-error for="ownerPassword" />
                </div>
                <div>
                    <label for="ownerPasswordConfirmation"
                        class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Konfirmasi Password</label>
                    <input type="password" id="ownerPasswordConfirmation" wire:model.live="ownerPasswordConfirmation"
                        aria-invalid="{{ $errors->has('ownerPasswordConfirmation') ? 'true' : 'false' }}"
                        aria-describedby="{{ $errors->has('ownerPasswordConfirmation') ? 'error-ownerPasswordConfirmation' : '' }}"
                        class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                    <x-common.input-error for="ownerPasswordConfirmation" />
                </div>
            @endunless

            <div
                class="sm:col-span-2 flex items-center justify-end gap-2 border-t border-gray-100 pt-4 dark:border-gray-800">
                <a href="{{ route('tenants.index') }}" wire:navigate
                    class="shadow-theme-xs inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                    Batal
                </a>
                <button type="submit"
                    class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex h-11 items-center justify-center rounded-lg px-6 text-sm font-semibold text-white transition">
                    {{ $isEditing ? 'Simpan' : 'Buat Tenant' }}
                </button>
            </div>
        </form>
    </div>
</div>
