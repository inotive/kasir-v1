<?php

namespace App\Support\Guides;

use DOMDocument;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class GuideRepository
{
    public function categories(): array
    {
        $cats = (array) config('guides.categories', []);

        $items = [];
        foreach ($cats as $key => $cat) {
            $key = (string) $key;
            $items[] = [
                'key' => $key,
                'label' => (string) ($cat['label'] ?? $key),
                'description' => (string) ($cat['description'] ?? ''),
                'order' => (int) ($cat['order'] ?? 1000),
            ];
        }

        usort($items, fn ($a, $b) => ($a['order'] <=> $b['order']) ?: strcmp($a['label'], $b['label']));

        return $items;
    }

    public function articles(): array
    {
        $cats = $this->categories();
        $catLabels = [];
        $catOrders = [];
        foreach ($cats as $cat) {
            $catLabels[(string) $cat['key']] = (string) $cat['label'];
            $catOrders[(string) $cat['key']] = (int) ($cat['order'] ?? 1000);
        }

        $articles = Arr::wrap(config('guides.articles', []));
        $items = [];
        foreach ($articles as $a) {
            $slug = trim((string) ($a['slug'] ?? ''));
            if ($slug === '') {
                continue;
            }

            $category = (string) ($a['category'] ?? 'faq');
            $items[] = [
                'slug' => $slug,
                'title' => (string) ($a['title'] ?? $slug),
                'category' => $category,
                'category_label' => (string) ($catLabels[$category] ?? $category),
                'category_order' => (int) ($catOrders[$category] ?? 1000),
                'order' => (int) ($a['order'] ?? 1000),
                'featured' => (bool) ($a['featured'] ?? false),
                'summary' => (string) ($a['summary'] ?? ''),
                'file' => (string) ($a['file'] ?? ($slug.'.md')),
            ];
        }

        usort($items, fn ($a, $b) => (($a['category_order'] <=> $b['category_order']) ?: ($a['order'] <=> $b['order']) ?: strcmp($a['title'], $b['title'])));

        return $items;
    }

    public function featuredArticles(int $limit = 6): array
    {
        $all = $this->articles();
        $featured = array_values(array_filter($all, fn ($a) => (bool) ($a['featured'] ?? false)));

        usort($featured, fn ($a, $b) => ($a['order'] <=> $b['order']) ?: strcmp($a['title'], $b['title']));

        return array_slice($featured, 0, max(0, $limit));
    }

    public function find(string $slug): ?array
    {
        $slug = trim($slug);
        if ($slug === '') {
            return null;
        }

        $article = null;
        foreach ($this->articles() as $a) {
            if ((string) $a['slug'] === $slug) {
                $article = $a;
                break;
            }
        }

        if (! $article) {
            return null;
        }

        $path = resource_path('content/guides/'.(string) $article['file']);
        if (! File::exists($path)) {
            return array_merge($article, [
                'markdown' => '',
                'html' => '',
                'toc' => [],
                'missing' => true,
            ]);
        }

        $markdown = $this->normalizeMarkdown((string) File::get($path));
        $rawHtml = (string) Str::markdown($markdown, ['html_input' => 'strip']);
        [$html, $toc] = $this->buildHtmlWithToc($rawHtml);

        return array_merge($article, [
            'markdown' => $markdown,
            'html' => $html,
            'toc' => $toc,
            'missing' => false,
        ]);
    }

    public function search(string $term): array
    {
        $term = trim($term);
        if ($term === '') {
            return [];
        }

        $needle = mb_strtolower($term);
        $results = [];

        foreach ($this->articles() as $a) {
            $slug = (string) $a['slug'];
            $title = (string) $a['title'];
            $hay = mb_strtolower($title.' '.$slug);

            $found = str_contains($hay, $needle);
            $content = '';

            if (! $found) {
                $path = resource_path('content/guides/'.(string) $a['file']);
                if (File::exists($path)) {
                    $content = (string) File::get($path);
                    $found = str_contains(mb_strtolower($content), $needle);
                }
            }

            if ($found) {
                $results[] = array_merge($a, [
                    'match_in_content' => $content !== '' && str_contains(mb_strtolower($content), $needle),
                ]);
            }
        }

        return $results;
    }

    private function normalizeMarkdown(string $markdown): string
    {
        $markdown = str_replace("\r\n", "\n", $markdown);
        $markdown = preg_replace('/^\s*-\s+\[ \]\s+/m', '- ☐ ', $markdown) ?? $markdown;
        $markdown = preg_replace('/^\s*-\s+\[x\]\s+/mi', '- ☑ ', $markdown) ?? $markdown;

        return $markdown;
    }

    private function buildHtmlWithToc(string $html): array
    {
        $toc = [];

        $dom = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8"><div>'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $root = $dom->getElementsByTagName('div')->item(0);
        if (! $root) {
            return [$html, $toc];
        }

        $this->applyChecklistStyling($root);

        $headings = [];
        $this->collectHeadings($root, $headings);

        $used = [];
        $idx = 1;
        foreach ($headings as $h) {
            $node = $h['node'];
            $level = (int) $h['level'];
            $text = trim((string) $node->textContent);
            if ($text === '') {
                continue;
            }

            $base = Str::slug($text);
            if ($base === '') {
                $base = 'section-'.$idx;
            }

            $id = $base;
            $n = 2;
            while (isset($used[$id])) {
                $id = $base.'-'.$n;
                $n++;
            }
            $used[$id] = true;
            $idx++;

            $node->setAttribute('id', $id);
            $toc[] = [
                'level' => $level,
                'id' => $id,
                'title' => $text,
            ];
        }

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $dom->saveHTML($child);
        }

        return [$out, $toc];
    }

    private function applyChecklistStyling($root): void
    {
        $items = [];
        $this->collectNodesByTag($root, 'li', $items);

        foreach ($items as $li) {
            $text = (string) $li->textContent;
            if (! preg_match('/^\s*(☐|☑)\s+/u', $text, $m)) {
                continue;
            }

            $mark = (string) ($m[1] ?? '');
            $li->setAttribute('data-check', $mark === '☑' ? '1' : '0');

            foreach ($li->childNodes as $child) {
                if ((int) $child->nodeType === XML_TEXT_NODE) {
                    $child->nodeValue = preg_replace('/^\s*(☐|☑)\s+/u', '', (string) $child->nodeValue) ?? (string) $child->nodeValue;
                    break;
                }
            }
        }
    }

    private function collectHeadings($node, array &$out): void
    {
        if (! $node) {
            return;
        }

        if (property_exists($node, 'nodeName')) {
            $name = strtolower((string) $node->nodeName);
            if ($name === 'h2') {
                $out[] = ['node' => $node, 'level' => 2];
            } elseif ($name === 'h3') {
                $out[] = ['node' => $node, 'level' => 3];
            }
        }

        if (! property_exists($node, 'childNodes') || ! $node->childNodes) {
            return;
        }

        foreach ($node->childNodes as $child) {
            $this->collectHeadings($child, $out);
        }
    }

    private function collectNodesByTag($node, string $tag, array &$out): void
    {
        if (! $node) {
            return;
        }

        if (property_exists($node, 'nodeName')) {
            $name = strtolower((string) $node->nodeName);
            if ($name === strtolower($tag)) {
                $out[] = $node;
            }
        }

        if (! property_exists($node, 'childNodes') || ! $node->childNodes) {
            return;
        }

        foreach ($node->childNodes as $child) {
            $this->collectNodesByTag($child, $tag, $out);
        }
    }
}
