<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOptionalTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Tenant::checkCurrent()) {
            return $next($request);
        }

        $sessionKey = 'ensure_valid_tenant_session_tenant_id';
        $currentTenantKey = Tenant::current()->getKey();

        if (! $request->session()->has($sessionKey)) {
            $request->session()->put($sessionKey, $currentTenantKey);

            return $next($request);
        }

        if ($request->session()->get($sessionKey) !== $currentTenantKey) {
            abort(401);
        }

        return $next($request);
    }
}
