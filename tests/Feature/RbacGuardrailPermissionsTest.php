<?php

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Permission;

function rbacPermissionNamePattern(): string
{
    return '[a-z0-9_]+(?:\.[a-z0-9_]+)+';
}

function rbacSplitPermissionPayload(string $payload): array
{
    $parts = preg_split('/[|,]/', $payload) ?: [];

    return array_values(array_filter(array_map(static function (string $part): ?string {
        $candidate = trim($part);
        if ($candidate === '') {
            return null;
        }

        if (! preg_match('/^'.rbacPermissionNamePattern().'$/', $candidate)) {
            return null;
        }

        return $candidate;
    }, $parts)));
}

function rbacExtractPermissionNamesFromText(string $text): array
{
    $permissionNames = [];

    $middlewarePayloadMatches = [];
    preg_match_all('/[\'"]permission:([^\'"]+)[\'"]/', $text, $middlewarePayloadMatches);
    foreach (($middlewarePayloadMatches[1] ?? []) as $payload) {
        $permissionNames = array_merge($permissionNames, rbacSplitPermissionPayload((string) $payload));
    }

    $roleOrPermissionPayloadMatches = [];
    preg_match_all('/[\'"]role_or_permission:([^\'"]+)[\'"]/', $text, $roleOrPermissionPayloadMatches);
    foreach (($roleOrPermissionPayloadMatches[1] ?? []) as $payload) {
        $permissionNames = array_merge($permissionNames, rbacSplitPermissionPayload((string) $payload));
    }

    $directContextPatterns = [
        '/@can(?:not|any)?\s*\(\s*[\'"]('.rbacPermissionNamePattern().')[\'"]/',
        '/->can(?:Any|All)?\s*\(\s*[\'"]('.rbacPermissionNamePattern().')[\'"]/',
        '/\bauthorize(?:ForUser)?\s*\(\s*[\'"]('.rbacPermissionNamePattern().')[\'"]/',
        '/\bGate::authorize\s*\(\s*[\'"]('.rbacPermissionNamePattern().')[\'"]/',
        '/\bGate::allows\s*\(\s*[\'"]('.rbacPermissionNamePattern().')[\'"]/',
        '/\bGate::denies\s*\(\s*[\'"]('.rbacPermissionNamePattern().')[\'"]/',
    ];

    foreach ($directContextPatterns as $pattern) {
        $matches = [];
        preg_match_all($pattern, $text, $matches);
        foreach (($matches[1] ?? []) as $match) {
            $permissionNames[] = (string) $match;
        }
    }

    $canAnyArrayMatches = [];
    preg_match_all('/@canany\s*\(\s*\[([^\]]+)\]/', $text, $canAnyArrayMatches);
    foreach (($canAnyArrayMatches[1] ?? []) as $arrayPayload) {
        $stringMatches = [];
        preg_match_all('/[\'"]('.rbacPermissionNamePattern().')[\'"]/', (string) $arrayPayload, $stringMatches);
        foreach (($stringMatches[1] ?? []) as $match) {
            $permissionNames[] = (string) $match;
        }
    }

    $permissionNames = array_values(array_unique($permissionNames));
    sort($permissionNames);

    return $permissionNames;
}

test('guardrail RBAC: permission DB, config label, dan pemakaian konsisten', function () {
    $this->seed(RolePermissionSeeder::class);

    $dbPermissions = Permission::query()->pluck('name')->all();
    $dbPermissions = array_values(array_unique(array_map('strval', $dbPermissions)));
    sort($dbPermissions);

    $permissionLabels = (array) config('rbac.permissions', []);
    $permissionGroups = (array) config('rbac.permission_groups', []);

    $missingLabels = array_values(array_filter($dbPermissions, static function (string $permission) use ($permissionLabels): bool {
        if (! array_key_exists($permission, $permissionLabels)) {
            return true;
        }

        return trim((string) $permissionLabels[$permission]) === '';
    }));

    expect($missingLabels)->toBeEmpty();

    $missingGroups = [];
    foreach ($dbPermissions as $permission) {
        $prefix = explode('.', $permission, 2)[0] ?? '';
        $label = (string) ($permissionGroups[$prefix] ?? '');
        if (trim($label) === '') {
            $missingGroups[] = $permission;
        }
    }
    $missingGroups = array_values(array_unique($missingGroups));
    sort($missingGroups);

    expect($missingGroups)->toBeEmpty();

    $invalidDbPermissionNames = array_values(array_filter($dbPermissions, static function (string $permission): bool {
        return ! preg_match('/^'.rbacPermissionNamePattern().'$/', $permission);
    }));

    expect($invalidDbPermissionNames)->toBeEmpty();

    $pathsToScan = [
        base_path('routes'),
        app_path(),
        resource_path('views'),
    ];

    $usedPermissions = [];
    foreach ($pathsToScan as $path) {
        if (! is_dir($path)) {
            continue;
        }

        foreach (File::allFiles($path) as $file) {
            $pathname = $file->getPathname();
            $extension = strtolower((string) $file->getExtension());
            $filename = strtolower((string) $file->getFilename());

            $isPhp = $extension === 'php';
            $isBlade = str_ends_with($filename, '.blade.php');

            if (! $isPhp && ! $isBlade) {
                continue;
            }

            $text = file_get_contents($pathname);
            if ($text === false) {
                continue;
            }

            $usedPermissions = array_merge($usedPermissions, rbacExtractPermissionNamesFromText($text));
        }
    }

    $usedPermissions = array_values(array_unique($usedPermissions));
    sort($usedPermissions);

    $missingInDb = array_values(array_diff($usedPermissions, $dbPermissions));
    sort($missingInDb);

    expect($missingInDb)->toBeEmpty();

    $invalidUsedPermissionNames = array_values(array_filter($usedPermissions, static function (string $permission): bool {
        return ! preg_match('/^'.rbacPermissionNamePattern().'$/', $permission);
    }));

    expect($invalidUsedPermissionNames)->toBeEmpty();
});
