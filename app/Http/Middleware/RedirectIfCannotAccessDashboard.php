<?php

namespace App\Http\Middleware;

use App\Helpers\MenuHelper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfCannotAccessDashboard
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if ($user->can('dashboard.access')) {
            return $next($request);
        }

        if ($user->can('pos.access')) {
            return redirect()->route('pos.index');
        }

        $groups = MenuHelper::getMenuGroups();
        foreach ($groups as $group) {
            foreach ((array) ($group['items'] ?? []) as $item) {
                $subItems = $item['subItems'] ?? null;
                if (is_array($subItems) && $subItems !== []) {
                    $first = $subItems[0]['path'] ?? null;
                    if (is_string($first) && $first !== '') {
                        return redirect()->to($first);
                    }
                }

                $path = $item['path'] ?? null;
                if (is_string($path) && $path !== '') {
                    return redirect()->to($path);
                }
            }
        }

        abort(403);
    }
}
