<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPwaInstallabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_manifest_route_is_available(): void
    {
        $this->get(route('admin.manifest'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json')
            ->assertJsonPath('display', 'standalone');
    }

    public function test_admin_service_worker_route_is_available(): void
    {
        $this->get(route('admin.service-worker'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/javascript; charset=utf-8')
            ->assertSee('const CACHE_NAME', false);
    }

    public function test_admin_signin_contains_manifest_link(): void
    {
        $adminDomain = (string) config('domains.admin', '');
        $host = $adminDomain !== '' ? (parse_url($adminDomain, PHP_URL_HOST) ?: $adminDomain) : null;
        $path = $adminDomain !== '' ? '/signin' : '/admin/signin';

        $request = $host ? $this->withServerVariables(['HTTP_HOST' => $host]) : $this;

        $request->followingRedirects()->get($path)
            ->assertOk()
            ->assertSee('rel="manifest"', false)
            ->assertSee('manifest.webmanifest', false);
    }

    public function test_landing_does_not_contain_manifest_link(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('rel="manifest"', false);
    }

    public function test_self_order_scan_does_not_contain_manifest_link(): void
    {
        $this->get(route('self-order.scan'))
            ->assertOk()
            ->assertDontSee('rel="manifest"', false)
            ->assertDontSee('serviceWorker.register', false);
    }
}
