@if ($paginator->hasPages())
    @php
        $current = (int) $paginator->currentPage();
        $last = (int) $paginator->lastPage();
        $pageName = (string) $paginator->getPageName();

        $pages = [1];

        if ($current <= 2) {
            $pages[] = 2;
        } elseif ($current >= $last - 1) {
            $pages[] = max(1, $last - 1);
        } else {
            $pages[] = $current;
        }

        $pages[] = $last;
        $pages = array_values(array_unique(array_filter($pages, fn ($p) => is_int($p) && $p >= 1 && $p <= $last)));
        sort($pages);
    @endphp
    <nav role="navigation" aria-label="Pagination" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="text-xs font-medium text-gray-500 dark:text-gray-400">
            Menampilkan {{ number_format($paginator->firstItem() ?? 0, 0, ',', '.') }}–{{ number_format($paginator->lastItem() ?? 0, 0, ',', '.') }} dari {{ number_format($paginator->total(), 0, ',', '.') }}
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" aria-label="Sebelumnya" class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-400 opacity-60 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-500">
                    Sebelumnya
                </span>
            @else
                <button type="button" wire:click="previousPage('{{ $pageName }}')" rel="prev" class="shadow-theme-xs inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                    Sebelumnya
                </button>
            @endif

            <div class="hidden items-center gap-1 sm:flex">
                @php $prevShown = 0; @endphp
                @foreach ($pages as $page)
                    @if ($prevShown > 0 && $page > $prevShown + 1)
                        <span aria-disabled="true" class="px-2 py-2 text-xs font-medium text-gray-400 dark:text-gray-500">…</span>
                    @endif

                    @if ($page === $current)
                        <span aria-current="page" class="shadow-theme-xs inline-flex h-9 min-w-9 items-center justify-center rounded-lg border border-brand-600 bg-brand-600 px-3 text-xs font-semibold text-white">
                            {{ $page }}
                        </span>
                    @else
                        <button type="button" wire:click="gotoPage({{ $page }}, '{{ $pageName }}')" class="shadow-theme-xs inline-flex h-9 min-w-9 items-center justify-center rounded-lg border border-gray-300 bg-white px-3 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03]">
                            {{ $page }}
                        </button>
                    @endif

                    @php $prevShown = $page; @endphp
                @endforeach
            </div>

            @if ($paginator->hasMorePages())
                <button type="button" wire:click="nextPage('{{ $pageName }}')" rel="next" class="shadow-theme-xs inline-flex items-center justify-center rounded-lg bg-brand-600 px-3 py-2 text-xs font-medium text-white hover:bg-brand-700">
                    Berikutnya
                </button>
            @else
                <span aria-disabled="true" aria-label="Berikutnya" class="shadow-theme-xs inline-flex items-center justify-center rounded-lg bg-gray-100 px-3 py-2 text-xs font-medium text-gray-400 dark:bg-gray-800 dark:text-gray-500">
                    Berikutnya
                </span>
            @endif
        </div>
    </nav>
@endif
