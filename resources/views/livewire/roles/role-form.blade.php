<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('roles.index') }}" wire:navigate class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-white/[0.03]">
            Kembali
        </a>
        <h2 class="text-xl font-bold text-gray-800 dark:text-white/90">{{ $isEdit ? 'Ubah Peran' : 'Buat Peran Baru' }}</h2>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">Nama Peran</label>
            <input type="text" wire:model="name" aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}" aria-describedby="{{ $errors->has('name') ? 'error-name' : '' }}" class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:border-brand-500 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90 dark:focus:border-brand-500" placeholder="Contoh: Manajer Area">
            <x-common.input-error for="name" />
        </div>

        <div class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Hak Akses</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Pilih akses yang diizinkan untuk peran ini.</p>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                @foreach ($groupedPermissions as $group => $permissions)
                    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                        <h4 class="mb-4 font-semibold capitalize text-gray-800 dark:text-white/90">
                            {{ \App\Helpers\RbacLabelHelper::permissionGroup((string) $group) }}
                        </h4>
                        <div class="space-y-3">
                            @foreach ($permissions as $permission)
                                <label class="flex items-center gap-3 cursor-pointer group">
                                    <div class="relative flex items-center">
                                        <input type="checkbox" wire:model="selectedPermissions" value="{{ $permission->name }}" 
                                            class="peer h-5 w-5 rounded border-gray-300 text-brand-600 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:checked:bg-brand-500">
                                    </div>
                                    <span class="text-sm text-gray-600 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-gray-200">
                                        {{ \App\Helpers\RbacLabelHelper::permission((string) $permission->name) }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="flex justify-end pt-4">
            <button type="submit" class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-6 py-2.5 text-sm font-medium text-white transition">
                Simpan Peran
            </button>
        </div>
    </form>
</div>
