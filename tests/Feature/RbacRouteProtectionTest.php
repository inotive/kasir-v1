<?php

use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

function rbacRouteHasPermissionOrRoleMiddleware(LaravelRoute $route): bool
{
    $middleware = $route->gatherMiddleware();

    foreach ($middleware as $name) {
        $name = (string) $name;
        if (Str::startsWith($name, 'permission:') || Str::startsWith($name, 'role:') || Str::startsWith($name, 'role_or_permission:')) {
            return true;
        }
    }

    return false;
}

function rbacRouteHasMiddleware(LaravelRoute $route, string $needle): bool
{
    $middleware = $route->gatherMiddleware();

    foreach ($middleware as $name) {
        if ((string) $name === $needle) {
            return true;
        }
    }

    return false;
}

test('guardrail RBAC: semua route GET yang butuh auth diproteksi', function () {
    $routes = app('router')->getRoutes();
    $violations = [];

    foreach ($routes as $route) {
        $route = $route instanceof LaravelRoute ? $route : null;
        if (! $route) {
            continue;
        }

        $methods = $route->methods();
        if (! in_array('GET', $methods, true)) {
            continue;
        }

        $middleware = $route->gatherMiddleware();
        $needsAuth = Arr::first($middleware, fn ($m) => Str::startsWith((string) $m, 'auth')) !== null;

        if (! $needsAuth) {
            continue;
        }

        $routeName = (string) ($route->getName() ?? '');
        if ($routeName === 'dashboard') {
            if (! rbacRouteHasMiddleware($route, 'dashboard.redirect')) {
                $violations[] = $route->uri();
            }

            continue;
        }

        if (! rbacRouteHasPermissionOrRoleMiddleware($route)) {
            $violations[] = $routeName !== '' ? $routeName : $route->uri();
        }
    }

    expect($violations)->toBeEmpty();
});
