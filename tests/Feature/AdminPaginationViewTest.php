<?php

use Illuminate\Pagination\LengthAwarePaginator;

it('renders admin custom pagination view', function () {
    $paginator = new LengthAwarePaginator(
        items: collect(range(1, 10)),
        total: 25,
        perPage: 10,
        currentPage: 2,
        options: ['path' => '/admin/test']
    );

    $html = $paginator->links('livewire.pagination.admin')->toHtml();

    expect($html)
        ->toContain('Menampilkan')
        ->toContain('Sebelumnya')
        ->toContain('Berikutnya');
});
