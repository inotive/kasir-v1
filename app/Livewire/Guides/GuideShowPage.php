<?php

namespace App\Livewire\Guides;

use App\Support\Guides\GuideRepository;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class GuideShowPage extends Component
{
    public string $title = 'Buku Panduan';

    public string $slug = '';

    public function mount(string $slug): void
    {
        $this->slug = $slug;
    }

    public function render(GuideRepository $repo): View
    {
        $article = $repo->find($this->slug);
        if (! $article) {
            abort(404);
        }

        $all = $repo->articles();
        $categoryKey = (string) ($article['category'] ?? '');
        $inCategory = array_values(array_filter($all, fn ($a) => (string) ($a['category'] ?? '') === $categoryKey));

        $currentIdx = null;
        foreach ($inCategory as $i => $a) {
            if ((string) ($a['slug'] ?? '') === (string) ($article['slug'] ?? '')) {
                $currentIdx = (int) $i;
                break;
            }
        }

        $prev = $currentIdx !== null && $currentIdx > 0 ? ($inCategory[$currentIdx - 1] ?? null) : null;
        $next = $currentIdx !== null ? ($inCategory[$currentIdx + 1] ?? null) : null;

        $related = array_values(array_filter($inCategory, fn ($a) => (string) ($a['slug'] ?? '') !== (string) ($article['slug'] ?? '')));
        $related = array_slice($related, 0, 8);

        return view('livewire.guides.guide-show-page', [
            'article' => $article,
            'related' => $related,
            'prev' => $prev,
            'next' => $next,
        ])->layout('layouts.app', ['title' => (string) ($article['title'] ?? $this->title)]);
    }
}
