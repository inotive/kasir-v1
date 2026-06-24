<?php

namespace App\TenantFinder;

use Illuminate\Http\Request;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\TenantFinder\TenantFinder;

class SubdomainTenantFinder extends TenantFinder
{
    public function findForRequest(Request $request): ?IsTenant
    {
        $host = $request->getHost();

        $parts = explode('.', $host);

        if (count($parts) < 2) {
            return null;
        }

        $slug = $parts[0];

        if ($slug === 'www' || $slug === 'localhost' || filter_var($host, FILTER_VALIDATE_IP)) {
            return null;
        }

        return app(IsTenant::class)::query()->where('slug', $slug)->first();
    }
}
