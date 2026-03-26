<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminDomain
{
    public function handle(Request $request, Closure $next): Response
    {
        $adminDomain = (string) config('domains.admin', '');

        if ($adminDomain === '') {
            return $next($request);
        }

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
