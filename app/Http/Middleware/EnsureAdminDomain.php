<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminDomain
{
    public function handle(Request $request, Closure $next): Response
    {
        $adminDomain = (string) config('domains.admin', '');

        // If ADMIN_DOMAIN is set, use strict matching (existing behavior)
        if ($adminDomain !== '') {
            $adminHost = $this->normalizeHost($adminDomain);
            $requestHost = (string) $request->getHost();

            if ($adminHost !== '' && strcasecmp($adminHost, $requestHost) !== 0) {
                $policy = (string) config('domains.admin_block_response', 'redirect');

                if ($policy === '404') {
                    abort(404);
                }

                return redirect()->to((string) config('domains.landing_url', '/'));
            }

            return $next($request);
        }

        // No ADMIN_DOMAIN configured — allow root domain and valid tenant subdomains
        if (Tenant::checkCurrent()) {
            return $next($request);
        }

        $host = $request->getHost();
        $parts = explode('.', $host);
        $isRootDomain = count($parts) < 2 || $parts[0] === 'www' || $parts[0] === 'localhost' || filter_var($host, FILTER_VALIDATE_IP);

        if ($isRootDomain) {
            return $next($request);
        }

        abort(404);
    }

    private function normalizeHost(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        $host = parse_url($value, PHP_URL_HOST);

        if (is_string($host) && $host !== '') {
            return $host;
        }

        $value = preg_replace('#^https?://#i', '', $value) ?? $value;
        $value = preg_replace('#/.*$#', '', $value) ?? $value;

        return trim($value);
    }
}
