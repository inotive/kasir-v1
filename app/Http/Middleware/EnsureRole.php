<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('signin');
        }

        if (count($roles) === 0) {
            return $next($request);
        }

        if (! $user->hasAnyRole($roles)) {
            abort(403);
        }

        return $next($request);
    }
}
