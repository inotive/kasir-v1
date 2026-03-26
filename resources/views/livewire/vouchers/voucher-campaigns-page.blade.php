<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Voucher</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Kelola program voucher, kode, dan pantau pemakaiannya.</p>
        </div>
        <div class="flex items-center gap-2">
            @can('vouchers.manage')
                <a href="{{ route('vouchers.create') }}" wire:navigate class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex h-11 items-center justify-center rounded-lg px-4 text-sm font-semibold text-white transition">
                    Buat Program
                </a>
            @endcan
            @can('vouchers.view')
                <a href="{{ route('vouchers.performance') }}" wire:navigate class="shadow-theme-xs inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                    Performa
                </a>
                <a href="{{ route('vouchers.redemptions') }}" wire:navigate class="shadow-theme-xs inline-flex h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                    Riwayat Pakai
                </a>
            @endcan
        </div>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
        <div class="relative flex-1 sm:flex-none">
            <span class="absolute top-1/2 left-4 -translate-y-1/2 text-gray-500 dark:text-gray-400">
                <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M3.04199 9.37363C3.04199 5.87693 5.87735 3.04199 9.37533 3.04199C12.8733 3.04199 15.7087 5.87693 15.7087 9.37363C15.7087 12.8703 12.8733 15.7053 9.37533 15.7053C5.87735 15.7053 3.04199 12.8703 3.04199 9.37363ZM9.37533 1.54199C5.04926 1.54199 1.54199 5.04817 1.54199 9.37363C1.54199 13.6991 5.04926 17.2053 9.37533 17.2053C11.2676 17.2053 13.0032 16.5344 14.3572 15.4176L17.1773 18.238C17.4702 18.5309 17.945 18.5309 18.2379 18.238C18.5308 17.9451 18.5309 17.4703 18.238 17.1773L15.4182 14.3573C16.5367 13.0033 17.2087 11.2669 17.2087 9.37363C17.2087 5.04817 13.7014 1.54199 9.37533 1.54199Z" fill="" />
                </svg>
            </span>
            <input wire:model.live.debounce.400ms="search" type="text" placeholder="Cari program..." class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent py-2.5 pr-4 pl-11 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden sm:w-[320px] sm:min-w-[320px] dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
        </div>

        <select wire:model.live="status" class="shadow-theme-xs h-11 rounded-lg border border-gray-300 bg-white px-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
            <option value="">Semua Status</option>
            <option value="active">Aktif</option>
            <option value="inactive">Nonaktif</option>
            <option value="running">Sedang Berjalan</option>
        </select>
    </div>

    @php
        $now = now();
        $countRunning = 0;
        $countExpiring = 0;
        $countQuotaLow = 0;
        foreach ($campaigns as $c) {
            $used = (int) ($c->codes_sum_times_redeemed ?? 0);
            $limit = $c->usage_limit_total === null ? null : (int) $c->usage_limit_total;
            $remaining = $limit === null ? null : max(0, $limit - $used);
            $isRunning = (bool) $c->is_active
                && (! $c->starts_at || $c->starts_at->lte($now))
                && (! $c->ends_at || $c->ends_at->gte($now));
            $expiresSoon = $c->ends_at ? $c->ends_at->lte($now->copy()->addDays($daysBeforeExpiry)) : false;
            $quotaLow = $remaining !== null ? $remaining <= $quotaThreshold : false;
            if ($isRunning) $countRunning++;
            if ($expiresSoon) $countExpiring++;
            if ($quotaLow) $countQuotaLow++;
        }
    @endphp

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-xs text-gray-500 dark:text-gray-400">Program (di halaman ini)</p>
            <p class="mt-1 text-lg font-semibold text-gray-800 dark:text-white/90">{{ number_format((int) $campaigns->count(), 0, ',', '.') }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-xs text-gray-500 dark:text-gray-400">Sedang berjalan</p>
            <p class="mt-1 text-lg font-semibold text-gray-800 dark:text-white/90">{{ number_format((int) $countRunning, 0, ',', '.') }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-xs text-gray-500 dark:text-gray-400">Akan berakhir ({{ (int) $daysBeforeExpiry }} hari)</p>
            <p class="mt-1 text-lg font-semibold text-gray-800 dark:text-white/90">{{ number_format((int) $countExpiring, 0, ',', '.') }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-xs text-gray-500 dark:text-gray-400">Kuota menipis (≤ {{ (int) $quotaThreshold }})</p>
            <p class="mt-1 text-lg font-semibold text-gray-800 dark:text-white/90">{{ number_format((int) $countQuotaLow, 0, ',', '.') }}</p>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="custom-scrollbar overflow-x-auto">
            <table class="w-full table-auto">
                <thead>
                    <tr class="border-b border-gray-200 dark:divide-gray-800 dark:border-gray-800">
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Program</th>
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Periode</th>
                        <th class="px-5 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Diskon</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Kode</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Dipakai</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Diskon Total</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Kuota</th>
                        <th class="px-5 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($campaigns as $c)
                        @php
                            $used = (int) ($c->codes_sum_times_redeemed ?? 0);
                            $limit = $c->usage_limit_total === null ? null : (int) $c->usage_limit_total;
                            $remaining = $limit === null ? null : max(0, $limit - $used);
                            $isRunning = (bool) $c->is_active
                                && (! $c->starts_at || $c->starts_at->lte(now()))
                                && (! $c->ends_at || $c->ends_at->gte(now()));
                            $expiresSoon = $c->ends_at ? $c->ends_at->lte(now()->addDays($daysBeforeExpiry)) : false;
                            $quotaLow = $remaining !== null ? $remaining <= $quotaThreshold : false;
                        @endphp
                        <tr>
                            <td class="px-5 py-4">
                                <div class="space-y-1">
                                    <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $c->name }}</p>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded-full px-2 py-0.5 text-theme-xs font-medium {{ $isRunning ? 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500' : 'bg-gray-50 text-gray-700 dark:bg-gray-500/15 dark:text-gray-300' }}">
                                            {{ $isRunning ? 'Berjalan' : ((bool) $c->is_active ? 'Aktif' : 'Nonaktif') }}
                                        </span>
                                        @if ($expiresSoon)
                                            <span class="rounded-full px-2 py-0.5 text-theme-xs font-medium bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-orange-400">
                                                Akan berakhir
                                            </span>
                                        @endif
                                        @if ($quotaLow)
                                            <span class="rounded-full px-2 py-0.5 text-theme-xs font-medium bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-orange-400">
                                                Kuota Menipis
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="space-y-1">
                                    <p class="text-sm text-gray-800 dark:text-white/90">{{ $c->starts_at ? $c->starts_at->format('d M Y') : '-' }} - {{ $c->ends_at ? $c->ends_at->format('d M Y') : '-' }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $c->is_member_only ? 'Khusus Member' : 'Semua Pengunjung' }}</p>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="space-y-1">
                                    <p class="text-sm text-gray-800 dark:text-white/90">
                                        Diskon: {{ $c->discount_type === 'percent' ? ($c->discount_value.'%') : ('Rp'.number_format((int) $c->discount_value, 0, ',', '.')) }}
                                    </p>
                                    @if ($c->discount_type === 'percent' && $c->max_discount_amount !== null)
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                            Batas: Rp{{ number_format((int) $c->max_discount_amount, 0, ',', '.') }}
                                        </p>
                                        @endif
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        Minimal belanja: {{ $c->min_eligible_subtotal !== null ? 'Rp'.number_format((int) $c->min_eligible_subtotal, 0, ',', '.') : '-' }}
                                    </p>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <p class="text-sm text-gray-800 dark:text-white/90">{{ number_format((int) ($c->codes_count ?? 0), 0, ',', '.') }}</p>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <p class="text-sm text-gray-800 dark:text-white/90">{{ number_format((int) ($c->redemptions_count ?? 0), 0, ',', '.') }}</p>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <p class="text-sm text-gray-800 dark:text-white/90">
                                    {{ (int) ($c->redemptions_sum_discount_amount ?? 0) > 0 ? 'Rp'.number_format((int) $c->redemptions_sum_discount_amount, 0, ',', '.') : '-' }}
                                </p>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <p class="text-sm text-gray-800 dark:text-white/90">
                                    {{ $limit === null ? '∞' : (number_format($used, 0, ',', '.').' / '.number_format($limit, 0, ',', '.')) }}
                                </p>
                                @if ($remaining !== null)
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Sisa: {{ number_format($remaining, 0, ',', '.') }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="inline-flex items-center gap-2">
                                    @can('vouchers.manage')
                                        <a href="{{ route('vouchers.codes', ['campaign' => $c->id]) }}" wire:navigate class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                                            Kode
                                        </a>
                                        <a href="{{ route('vouchers.edit', ['campaign' => $c->id]) }}" wire:navigate class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                                            Ubah
                                        </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-common.empty-table-row colspan="8" message="Belum ada program voucher." />
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-200 px-5 py-4 dark:border-gray-800">
            {{ $campaigns->links('livewire.pagination.admin') }}
        </div>
    </div>
</div>
