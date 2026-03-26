<?php

namespace App\Helpers;

use Illuminate\Support\Str;

class DataLabelHelper
{
    public static function enum(string|int|null $value, ?string $group = null): string
    {
        if ($value === null) {
            return (string) config('labels.empty', '-');
        }

        $value = trim((string) $value);
        if ($value === '') {
            return (string) config('labels.empty', '-');
        }

        $globalMap = (array) config('labels.global', []);
        $groupMap = $group ? (array) config('labels.'.$group, []) : [];

        if (array_key_exists($value, $groupMap)) {
            return (string) $groupMap[$value];
        }

        if (array_key_exists($value, $globalMap)) {
            return (string) $globalMap[$value];
        }

        $value = preg_replace('/[_.-]+/', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;
        $value = trim($value);

        return Str::upper($value);
    }
}
