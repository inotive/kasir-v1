<div class="rounded-2xl border border-gray-200 bg-gray-100 dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="shadow-default rounded-2xl bg-white px-5 pb-4 pt-5 dark:bg-gray-900 sm:px-6 sm:pt-6">
        <div class="flex justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                    Target bulan ini
                </h3>
                <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">
                    Target pendapatan yang diharapkan untuk bulan ini
                </p>
            </div>
            <!-- Dropdown Menu -->
             <x-common.dropdown-menu 
             :items="[
                 [
                     'label' => 'Lihat lainnya',
                     'route' => route('reports.sales-profit')
                 ]
             ]"
             />
            <!-- End Dropdown Menu -->

        </div>

        <div class="relative max-h-[195px]">
            {{-- Chart --}}
            <div class="h-full">
                <div wire:ignore id="chartTwo" data-series='@json([$progressPercent])' class="h-full"></div>
            </div>
        </div>
    </div>

    <div class="flex flex-col md:flex-row items-center justify-center md:gap-8 px-6 py-3.5 sm:py-5">
        <div>
            <p class="mb-1 text-center text-theme-xs text-gray-500 dark:text-gray-400 sm:text-sm">
                Target Bulan Ini
            </p>
            <p
                class="flex items-center justify-center gap-1 text-base font-semibold text-gray-800 dark:text-white/90 sm:text-lg">
                Rp{{ number_format($targetAmount, 0, ',', '.') }}
            </p>
        </div>

        <div class="h-7 w-px bg-gray-200 dark:bg-gray-800"></div>

        <div>
            <p class="mb-1 text-center text-theme-xs text-gray-500 dark:text-gray-400 sm:text-sm">
                Omzet Bulan Ini
            </p>
            <p
                class="flex items-center justify-center gap-1 text-base font-semibold text-gray-800 dark:text-white/90 sm:text-lg">
                Rp{{ number_format($revenueAmount, 0, ',', '.') }}
            </p>
        </div>
    </div>
</div>
