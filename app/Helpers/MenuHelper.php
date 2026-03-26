<?php

namespace App\Helpers;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class MenuHelper
{
    private static function path(string $routeName): string
    {
        return route($routeName, [], false);
    }

    public static function getMenuGroups(): array
    {
        $user = Auth::user();

        $items = [
            [
                'name' => 'Dashboard',
                'path' => self::path('dashboard'),
                'icon' => 'dashboard',
                'permission' => 'dashboard.access',
                'exact' => true,
            ],
            [
                'name' => 'Transaksi',
                'path' => self::path('transactions.index'),
                'icon' => 'transactions',
                'permission' => 'transactions.view',
            ],
            [
                'name' => 'Produk',
                'path' => self::path('products.index'),
                'icon' => 'products',
                'permission' => 'products.view',
            ],
            [
                'name' => 'Meja & Qr Code',
                'path' => self::path('dining-tables.index'),
                'icon' => 'tables',
                'permission' => 'dining_tables.view',
            ],
            [
                'name' => 'Pengeluaran',
                'icon' => 'expenses',
                'path' => self::path('reports.operating-expenses'),
                'permission' => 'reports.expenses.manage',
            ],
            [
                'name' => 'Member',
                'icon' => 'members',
                'permission' => 'members.view',
                'subItems' => [
                    [
                        'name' => 'Data Member',
                        'path' => self::path('members.index'),
                        'permission' => 'members.view',
                        'exclude' => [
                            self::path('members.regions'),
                        ],
                    ],
                    [
                        'name' => 'Wilayah Member',
                        'path' => self::path('members.regions'),
                        'permissionAny' => ['members.regions.view', 'members.regions.manage'],
                    ],
                ],
            ],
            [
                'name' => 'Inventaris',
                'icon' => 'inventory',
                'subItems' => [
                    [
                        'name' => 'Supplier',
                        'path' => self::path('suppliers.index'),
                        'permissionAny' => ['inventory.suppliers.view', 'inventory.view'],
                    ],
                    [
                        'name' => 'Bahan Baku',
                        'path' => self::path('ingredients.index'),
                        'permissionAny' => ['inventory.ingredients.view', 'inventory.view'],
                    ],
                    [
                        'name' => 'Pembelian',
                        'path' => self::path('purchases.index'),
                        'permissionAny' => ['inventory.purchases.view', 'inventory.purchases.manage', 'inventory.manage', 'inventory.view'],
                    ],
                    [
                        'name' => 'Pergerakan Stok',
                        'path' => self::path('inventory-movements.index'),
                        'permissionAny' => ['inventory.movements.view', 'inventory.view'],
                    ],
                    [
                        'name' => 'Stock Opname',
                        'path' => self::path('stock-opnames.index'),
                        'permissionAny' => ['inventory.opnames.view', 'inventory.opnames.manage', 'inventory.manage', 'inventory.view'],
                    ],
                    [
                        'name' => 'Stok Terendah',
                        'path' => self::path('inventory-reports.low-stock'),
                        'permissionAny' => ['inventory.reports.view', 'inventory.view'],
                    ],
                    [
                        'name' => 'Kartu Stok',
                        'path' => self::path('inventory-reports.stock-card'),
                        'permissionAny' => ['inventory.reports.view', 'inventory.view'],
                    ],
                    [
                        'name' => 'Laporan Persediaan',
                        'path' => self::path('inventory-reports.valuation'),
                        'permissionAny' => ['inventory.reports.view', 'inventory.view'],
                    ],
                ],
            ],
            [
                'name' => 'Laporan',
                'icon' => 'reports',
                'subItems' => [
                    [
                        'name' => 'Sales & Profit',
                        'path' => self::path('reports.sales-profit'),
                        'permission' => 'reports.sales',
                    ],
                    [
                        'name' => 'Penggunaan Diskon',
                        'path' => self::path('reports.manual-discount'),
                        'permission' => 'reports.sales',
                    ],
                    [
                        'name' => 'Performa Member',
                        'path' => self::path('reports.member-performance'),
                        'permission' => 'reports.performance',
                    ],
                ],
            ],
            [
                'name' => 'Voucher',
                'path' => self::path('vouchers.index'),
                'icon' => 'voucher',
                'permission' => 'vouchers.view',
            ],
            [
                'name' => 'Pengguna',
                'path' => self::path('users.index'),
                'icon' => 'users',
                'permission' => 'users.view',
            ],
            [
                'name' => 'Peran & Hak Akses',
                'path' => self::path('roles.index'),
                'icon' => 'roles',
                'permission' => 'roles.view',
            ],
            [
                'name' => 'Pengaturan',
                'path' => self::path('settings.index'),
                'icon' => 'settings',
                'permission' => 'settings.view',
            ],
        ];

        $items = array_values(array_filter(array_map(fn (array $item) => self::filterMenuItem($item, $user), $items)));

        return [
            [
                'title' => '',
                'items' => $items,
            ],
        ];
    }

    private static function filterMenuItem(array $item, $user): ?array
    {
        $subItems = Arr::get($item, 'subItems');
        if (is_array($subItems)) {
            $kept = [];
            foreach ($subItems as $subItem) {
                $filtered = self::filterMenuItem($subItem, $user);
                if ($filtered) {
                    $kept[] = $filtered;
                }
            }

            if ($kept === []) {
                return null;
            }

            $item['subItems'] = $kept;

            $permAny = Arr::get($item, 'permissionAny');
            if (is_array($permAny)) {
                if (! $user || ! method_exists($user, 'can')) {
                    return null;
                }
                foreach ($permAny as $p) {
                    $p = (string) $p;
                    if ($p !== '' && $user->can($p)) {
                        return $item;
                    }
                }

                return null;
            }

            $perm = (string) Arr::get($item, 'permission', '');
            if ($perm !== '' && $user && method_exists($user, 'can') && ! $user->can($perm)) {
                return null;
            }

            return $item;
        }

        $permAny = Arr::get($item, 'permissionAny');
        if (is_array($permAny)) {
            if (! $user || ! method_exists($user, 'can')) {
                return null;
            }
            foreach ($permAny as $p) {
                $p = (string) $p;
                if ($p !== '' && $user->can($p)) {
                    return $item;
                }
            }

            return null;
        }

        $perm = (string) Arr::get($item, 'permission', '');
        if ($perm !== '' && $user && method_exists($user, 'can') && ! $user->can($perm)) {
            return null;
        }

        if ($perm !== '' && ! $user) {
            return null;
        }

        return $item;
    }

    public static function getIconSvg(string $name): string
    {
        $name = strtolower(trim($name));

        return match ($name) {
            'dashboard' => '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>',

            // Uang/transaksi keuangan — lebih representatif dari sekadar arrow swap
            'transactions' => '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>',
            'guide' => '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5s3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18s-3.332.477-4.5 1.253" /></svg>',
            'expenses' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cash-coin" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M11 15a4 4 0 1 0 0-8 4 4 0 0 0 0 8m5-4a5 5 0 1 1-10 0 5 5 0 0 1 10 0"/>
                            <path d="M9.438 11.944c.047.596.518 1.06 1.363 1.116v.44h.375v-.443c.875-.061 1.386-.529 1.386-1.207 0-.618-.39-.936-1.09-1.1l-.296-.07v-1.2c.376.043.614.248.671.532h.658c-.047-.575-.54-1.024-1.329-1.073V8.5h-.375v.45c-.747.073-1.255.522-1.255 1.158 0 .562.378.92 1.007 1.066l.248.061v1.272c-.384-.058-.639-.27-.696-.563h-.668zm1.36-1.354c-.369-.085-.569-.26-.569-.522 0-.294.216-.514.572-.578v1.1zm.432.746c.449.104.655.272.655.569 0 .339-.257.571-.709.614v-1.195z"/>
                            <path d="M1 0a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h4.083q.088-.517.258-1H3a2 2 0 0 0-2-2V3a2 2 0 0 0 2-2h10a2 2 0 0 0 2 2v3.528c.38.34.717.728 1 1.154V1a1 1 0 0 0-1-1z"/>
                            <path d="M9.998 5.083 10 5a2 2 0 1 0-3.132 1.65 6 6 0 0 1 3.13-1.567"/>
                            </svg>',

            // Tag harga — tetap, paling tepat untuk produk
            'products' => '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>',

            'voucher' => '<svg xmlns="http://www.w3.org/2000/svg" 
     class="h-5 w-5" 
     fill="none" 
     viewBox="0 0 24 24" 
     stroke="currentColor" 
     stroke-width="2">
  <path stroke-linecap="round" stroke-linejoin="round" 
        d="M15 5H3a2 2 0 00-2 2v2a2 2 0 110 4v2a2 2 0 002 2h12m0-12h6a2 2 0 012 2v2a2 2 0 100 4v2a2 2 0 01-2 2h-6m0-12v14" />
  
  <path stroke-linecap="round" stroke-linejoin="round" d="M15 8v1m0 3v1m0 3v1" />
  
  <path stroke-linecap="round" stroke-linejoin="round" d="M7 11l1 1 2-2" />
</svg>',
            // Kartu identitas anggota — lebih jelas sebagai "member card"
            'members' => '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2" /></svg>',

            // Box 3D — tepat untuk stok/gudang
            'inventory' => '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>',

            // Bar chart — tepat untuk laporan/statistik
            'reports' => '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>',

            // Group pengguna — tepat untuk manajemen akun sistem
            'users' => '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>',

            // Shield-check — lebih intuitif untuk roles & permissions daripada kunci
            'roles' => '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>',

            // Gear/cog — sudah tepat untuk pengaturan
            'settings' => '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>',

            // Table grid — tepat untuk manajemen meja (restoran/kasir)
            'tables' => '<svg xmlns="http://www.w3.org/2000/svg" 
     class="h-5 w-5" 
     fill="none" 
     viewBox="0 0 24 24" 
     stroke="currentColor" 
     stroke-width="2">
  <rect x="3" y="3" width="5" height="5" rx="1" stroke-linecap="round" stroke-linejoin="round" />
  <path d="M5.5 5.5h0.01" stroke-linecap="round" stroke-linejoin="round" />
  
  <rect x="16" y="3" width="5" height="5" rx="1" stroke-linecap="round" stroke-linejoin="round" />
  <path d="M18.5 5.5h0.01" stroke-linecap="round" stroke-linejoin="round" />
  
  <rect x="3" y="16" width="5" height="5" rx="1" stroke-linecap="round" stroke-linejoin="round" />
  <path d="M5.5 18.5h0.01" stroke-linecap="round" stroke-linejoin="round" />

  <path stroke-linecap="round" stroke-linejoin="round" 
        d="M11 3v2m0 4v2m0 4v6M11 11h2m4 0h4M16 16h2m3 0h.01M16 21h5M11 18h0.01" />
  <path stroke-linecap="round" stroke-linejoin="round" 
        d="M3 11h2m4 0h2m0 4h2m4 0v5" />
</svg>
                        ',

            default => '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>',
        };
    }
}
