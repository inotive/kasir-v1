<?php

namespace App\Livewire\Guides;

use App\Support\Guides\GuideRepository;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

class GuidesIndexPage extends Component
{
    public string $title = 'Buku Panduan';

    #[Url]
    public string $q = '';

    #[Url]
    public string $category = '';

    public function render(GuideRepository $repo): View
    {
        $categories = $repo->categories();
        $articles = $repo->articles();
        $featured = $repo->featuredArticles();

        $term = trim($this->q);
        $results = $term !== '' ? $repo->search($term) : [];

        $categoryKey = trim($this->category);
        if ($categoryKey !== '') {
            $articles = array_values(array_filter($articles, fn ($a) => (string) ($a['category'] ?? '') === $categoryKey));
        }

        $grouped = [];
        foreach ($articles as $a) {
            $key = (string) ($a['category'] ?? 'faq');
            $grouped[$key] ??= [
                'category' => $key,
                'label' => (string) ($a['category_label'] ?? $key),
                'items' => [],
            ];
            $grouped[$key]['items'][] = $a;
        }

        return view('livewire.guides.guides-index-page', [
            'categories' => $categories,
            'featured' => $featured,
            'grouped' => array_values($grouped),
            'term' => $term,
            'results' => $results,
        ])->layout('layouts.app', ['title' => $this->title]);
    }
}
