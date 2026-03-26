<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

trait Search
{
    private function buildWildCards(string $term): string
    {
        if ($term === '') {
            return '';
        }

        $reservedSymbols = ['-', '+', '<', '>', '@', '(', ')', '~'];
        $term = str_replace($reservedSymbols, '', $term);

        $words = explode(' ', $term);
        foreach ($words as $index => $word) {
            $words[$index] = '+'.$word.'*';
        }

        return implode(' ', $words);
    }

    protected function scopeSearch(Builder $query, ?string $term): Builder
    {
        if ($term === null || $term === '') {
            return $query;
        }

        $columns = implode(',', $this->searchable);

        $query->whereRaw(
            "MATCH ({$columns}) AGAINST (? IN BOOLEAN MODE)",
            $this->buildWildCards($term)
        );

        return $query;
    }
}
