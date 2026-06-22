<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Kelola Tenant</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Daftar semua bisnis yang terdaftar di sistem</p>
        </div>
        <a href="{{ route('tenants.create') }}" wire:navigate
            class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold text-white transition">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Buat Tenant
        </a>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="custom-scrollbar overflow-x-auto">
            <table class="w-full table-auto">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-800">
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Nama</th>
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Slug</th>
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Bisnis</th>
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Pengguna
                        </th>
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Dibuat</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($tenants as $t)
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.03]">
                            <td class="px-5 py-4 text-sm font-medium text-gray-800 dark:text-white/90">
                                {{ $t->name }}</td>
                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $t->slug }}</td>
                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">
                                {{ $t->business_name ?? '-' }}</td>
                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $t->users_count }}</td>
                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">
                                {{ $t->created_at?->format('d M Y') ?? '-' }}</td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('tenants.edit', $t) }}" wire:navigate
                                    class="text-brand-600 hover:text-brand-700 text-sm font-medium dark:text-brand-400 dark:hover:text-brand-300">Ubah</a>
                            </td>
                        </tr>
                    @empty
                        <x-common.empty-table-row colspan="6" message="Belum ada tenant." />
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-200 px-5 py-4 dark:border-gray-800">
            {{ $tenants->links('livewire.pagination.admin') }}
        </div>
    </div>
</div>
