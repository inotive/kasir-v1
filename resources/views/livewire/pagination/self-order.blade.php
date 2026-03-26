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
    <nav role="navigation" aria-label="Pagination" class="flex items-center justify-between gap-3 rounded-2xl border border-gray-200 bg-white px-3 py-3">
        <div class="text-xs font-semibold text-gray-600">
            Menampilkan {{ number_format($paginator->firstItem() ?? 0, 0, ',', '.') }}–{{ number_format($paginator->lastItem() ?? 0, 0, ',', '.') }} dari {{ number_format($paginator->total(), 0, ',', '.') }}
        </div>

        <div class="flex items-center gap-1">
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" aria-label="Sebelumnya" class="inline-flex items-center rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-xs font-bold text-gray-400">
                    Sebelumnya
                </span>
            @else
                <button type="button" wire:click="previousPage('{{ $pageName }}')" rel="prev" class="inline-flex items-center rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-bold text-gray-700 hover:bg-gray-50">
                    Sebelumnya
                </button>
            @endif

            <div class="hidden items-center gap-1 sm:flex">
                @php $prevShown = 0; @endphp
                @foreach ($pages as $page)
                    @if ($prevShown > 0 && $page > $prevShown + 1)
                        <span aria-disabled="true" class="px-2 py-2 text-xs font-bold text-gray-400">…</span>
                    @endif

                    @if ($page === $current)
                        <span aria-current="page" class="inline-flex h-9 min-w-9 items-center justify-center rounded-xl bg-primary-60 px-3 text-xs font-extrabold text-white">
                            {{ $page }}
                        </span>
                    @else
                        <button type="button" wire:click="gotoPage({{ $page }}, '{{ $pageName }}')" class="inline-flex h-9 min-w-9 items-center justify-center rounded-xl border border-gray-200 bg-white px-3 text-xs font-bold text-gray-700 hover:bg-gray-50">
                            {{ $page }}
                        </button>
                    @endif

                    @php $prevShown = $page; @endphp
                @endforeach
            </div>

            @if ($paginator->hasMorePages())
                <button type="button" wire:click="nextPage('{{ $pageName }}')" rel="next" class="inline-flex items-center rounded-xl bg-primary-60 px-3 py-2 text-xs font-bold text-white hover:bg-primary-70">
                    Berikutnya
                </button>
            @else
                <span aria-disabled="true" aria-label="Berikutnya" class="inline-flex items-center rounded-xl bg-gray-100 px-3 py-2 text-xs font-bold text-gray-400">
                    Berikutnya
                </span>
            @endif
        </div>
    </nav>
@endif
