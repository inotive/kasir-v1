<?php

namespace App\Helpers;

use Illuminate\Support\Str;

class RbacLabelHelper
{
    public static function role(string $roleName): string
    {
        $roleName = trim($roleName);
        if ($roleName === '') {
            return '-';
        }

        $map = (array) config('rbac.roles', []);

        return (string) ($map[$roleName] ?? Str::headline($roleName));
    }

    public static function permissionGroup(string $groupKey): string
    {
        $groupKey = trim($groupKey);
        if ($groupKey === '') {
            return 'Lainnya';
        }

        $map = (array) config('rbac.permission_groups', []);

        return (string) ($map[$groupKey] ?? Str::headline($groupKey));
    }

    public static function permission(string $permissionName): string
    {
        $permissionName = trim($permissionName);
        if ($permissionName === '') {
            return '-';
        }

        $map = (array) config('rbac.permissions', []);

        return (string) ($map[$permissionName] ?? Str::headline(str_replace(['.', '_'], ' ', $permissionName)));
    }
}
