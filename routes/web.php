<?php

use App\Http\Controllers\MemberController;
use App\Http\Controllers\ReportExcelController;
use App\Http\Controllers\TableQrController;
use App\Http\Controllers\TransactionController;
use App\Http\Middleware\CheckTableNumber;
use App\Livewire\Auth\InitialSetupPage;
use App\Livewire\Auth\SignInPage;
use App\Livewire\DashboardPage;
use App\Livewire\DiningTables\DiningTablesPage;
use App\Livewire\Guides\GuideShowPage;
use App\Livewire\Guides\GuidesIndexPage;
use App\Livewire\Inventory\IngredientConversionsPage;
use App\Livewire\Inventory\IngredientsPage;
use App\Livewire\Inventory\InventoryMovementsPage;
use App\Livewire\Inventory\InventoryValuationPage;
use App\Livewire\Inventory\LowStockPage;
use App\Livewire\Inventory\PurchaseFormPage;
use App\Livewire\Inventory\PurchasesPage;
use App\Livewire\Inventory\StockCardPage;
use App\Livewire\Inventory\StockOpnameFormPage;
use App\Livewire\Inventory\StockOpnamesPage;
use App\Livewire\Inventory\SuppliersPage;
use App\Livewire\Members\MemberRegionsPage;
use App\Livewire\Members\MembersPage;
use App\Livewire\Pos\PosPage;
use App\Livewire\Product\ProductFormPage;
use App\Livewire\Product\ProductsPage;
use App\Livewire\Reports\ManualDiscountReportPage;
use App\Livewire\Reports\MemberPerformanceReportPage;
use App\Livewire\Reports\OperatingExpensesPage;
use App\Livewire\Reports\SalesProfitReportPage;
use App\Livewire\Roles\RoleForm;
use App\Livewire\Roles\RoleIndex;
use App\Livewire\SelfOrder\Pages\CartPage;
use App\Livewire\SelfOrder\Pages\CheckoutPage;
use App\Livewire\SelfOrder\Pages\HomePage;
use App\Livewire\SelfOrder\Pages\InvalidPage;
use App\Livewire\SelfOrder\Pages\MemberAccountPage;
use App\Livewire\SelfOrder\Pages\MemberProfileEditPage;
use App\Livewire\SelfOrder\Pages\MemberTransactionShowPage;
use App\Livewire\SelfOrder\Pages\MemberTransactionsPage;
use App\Livewire\SelfOrder\Pages\PaymentStatusPage;
use App\Livewire\SelfOrder\Pages\ScanPage;
use App\Livewire\SelfOrder\Pages\StartPage;
use App\Livewire\SelfOrder\Pages\VerifyEmailPage;
use App\Livewire\Settings\SettingsPage;
use App\Livewire\Transaction\TransactionShowPage;
use App\Livewire\Transaction\TransactionsPage;
use App\Livewire\Users\UsersPage;
use App\Livewire\Vouchers\VoucherCampaignFormPage;
use App\Livewire\Vouchers\VoucherCampaignsPage;
use App\Livewire\Vouchers\VoucherCodesPage;
use App\Livewire\Vouchers\VoucherPerformancePage;
use App\Livewire\Vouchers\VoucherRedemptionsPage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('signin');
})->name('landing');

Route::get('/t/{code}', TableQrController::class)->name('tables.qr');
Route::prefix('order')->name('self-order.')->group(function () {
    Route::get('/scan', ScanPage::class)->name('scan');
    Route::get('/invalid', InvalidPage::class)->name('invalid');

    // Halaman Status Pembayaran (UI tunggal)
    Route::get('/status/{code?}', PaymentStatusPage::class)->name('payment.status');
    Route::get('/payment/receipt/{code}', [TransactionController::class, 'receipt'])
        ->middleware('throttle:60,1')
        ->name('payment.receipt');
    Route::view('/payment/failure', 'livewire.self-order.payment.failure')->name('payment.failure');

    Route::post('/payment/webhook', [TransactionController::class, 'handleWebhook'])
        ->middleware('throttle:120,1')
        ->name('payment.webhook');

    Route::middleware(CheckTableNumber::class)->group(function () {
        Route::get('/start', StartPage::class)->name('start');
        Route::get('/', HomePage::class)->name('home');
        Route::get('/verify-email', VerifyEmailPage::class)->name('member.verify-email');

        Route::get('/account', MemberAccountPage::class)->name('member.account');
        Route::get('/account/profile', MemberProfileEditPage::class)->name('member.profile.edit');
        Route::get('/account/transactions', MemberTransactionsPage::class)->name('member.transactions');
        Route::get('/account/transactions/{transaction}', MemberTransactionShowPage::class)->name('member.transactions.show');

        Route::get('/cart', CartPage::class)->name('payment.cart');
        Route::get('/payment', CheckoutPage::class)->name('payment.page');

        Route::post('/payment', [TransactionController::class, 'handlePayment'])
            ->middleware('throttle:10,1')
            ->name('payment.pay');
    });
});

Route::post('/members/register', [MemberController::class, 'register'])
    ->middleware('throttle:10,1')
    ->name('members.register');
Route::get('/members/verify/{token}', [MemberController::class, 'verify'])
    ->middleware('throttle:60,1')
    ->name('members.verify');

$adminDomain = (string) config('domains.admin', '');

if ($adminDomain !== '') {
    Route::domain($adminDomain)->group(function () {
        Route::get('/manifest.webmanifest', function () {
            $manifest = [
                'name' => config('app.name').' Admin',
                'short_name' => 'Admin',
                'start_url' => '/signin',
                'scope' => '/',
                'display' => 'standalone',
                'background_color' => '#ffffff',
                'theme_color' => '#111827',
                'icons' => [
                    [
                        'src' => '/assets/images/192.png',
                        'sizes' => '192x192',
                        'type' => 'image/png',
                    ],
                    [
                        'src' => '/assets/images/512.png',
                        'sizes' => '512x512',
                        'type' => 'image/png',
                    ],
                ],
            ];

            return response()->json($manifest)->header('Content-Type', 'application/manifest+json');
        })->middleware('admin.domain')->name('admin.manifest');

        Route::get('/sw.js', function () {
            $script = <<<'JS'
            const CACHE_NAME = 'admin-static-v1';

            self.addEventListener('install', (event) => {
              event.waitUntil(self.skipWaiting());
            });

            self.addEventListener('activate', (event) => {
              event.waitUntil((async () => {
                const keys = await caches.keys();
                await Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k)));
                await self.clients.claim();
              })());
            });

            function isCacheable(request) {
              if (request.method !== 'GET') {
                return false;
              }

              const url = new URL(request.url);

              if (url.origin !== self.location.origin) {
                return false;
              }

              if (request.mode === 'navigate') {
                return false;
              }

              if (['script', 'style', 'image', 'font'].includes(request.destination)) {
                return true;
              }

              return /\.(?:js|css|png|jpg|jpeg|webp|svg|gif|woff2?|ttf|eot)$/.test(url.pathname);
            }

            async function cacheFirst(request) {
              const cache = await caches.open(CACHE_NAME);
              const cached = await cache.match(request);

              if (cached) {
                return cached;
              }

              const response = await fetch(request);

              if (response.ok) {
                await cache.put(request, response.clone());
              }

              return response;
            }

            async function networkFirst(request) {
              try {
                return await fetch(request);
              } catch (e) {
                return new Response(
                  '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Offline</title></head><body style="font-family:system-ui;padding:24px"><h1>Offline</h1><p>Koneksi internet tidak tersedia.</p></body></html>',
                  { headers: { 'Content-Type': 'text/html; charset=utf-8' } }
                );
              }
            }

            self.addEventListener('fetch', (event) => {
              const request = event.request;

              if (request.method !== 'GET') {
                return;
              }

              if (request.mode === 'navigate') {
                event.respondWith(networkFirst(request));
                return;
              }

              if (isCacheable(request)) {
                event.respondWith(cacheFirst(request));
              }
            });
            JS;

            return response($script)->header('Content-Type', 'application/javascript; charset=utf-8');
        })->middleware('admin.domain')->name('admin.service-worker');

        Route::get('/login', function () {
            return redirect()->route('signin');
        })->middleware(['guest', 'admin.domain'])->name('login');

        Route::get('/signin', SignInPage::class)
            ->middleware(['guest', 'admin.domain'])
            ->name('signin');

        Route::get('/setup', InitialSetupPage::class)
            ->middleware(['guest', 'admin.domain'])
            ->name('setup');

        Route::post('/logout', function () {
            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            return redirect()->route('signin');
        })->middleware(['auth', 'active', 'admin.domain'])->name('logout');

        Route::middleware(['auth', 'active', 'admin.domain'])->group(function () {
            Route::get('/', DashboardPage::class)->middleware('dashboard.redirect')->name('dashboard');

            Route::get('/pos', PosPage::class)->middleware('permission:pos.access')->name('pos.index');
            Route::get('/midtrans/unprocessed', [TransactionController::class, 'midtransUnprocessed'])
                ->middleware('permission:transactions.view')
                ->name('midtrans.unprocessed');

            Route::get('/transactions', TransactionsPage::class)->middleware('permission:transactions.view')->name('transactions.index');
            Route::get('/transactions/{transaction}', TransactionShowPage::class)->middleware('permission:transactions.details')->name('transactions.show');

            Route::get('/products', ProductsPage::class)->middleware('permission:products.view')->name('products.index');
            Route::get('/products/create', ProductFormPage::class)->middleware('permission:products.create')->name('products.create');
            Route::get('/products/{product}/edit', ProductFormPage::class)->middleware('permission:products.edit')->name('products.edit');

            Route::get('/dining-tables', DiningTablesPage::class)->middleware('permission:dining_tables.view')->name('dining-tables.index');

            Route::get('/members', MembersPage::class)->middleware('permission:members.view')->name('members.index');
            Route::get('/members/regions', MemberRegionsPage::class)->middleware('permission:members.regions.view|members.regions.manage')->name('members.regions');

            Route::get('/reports/sales-profit', SalesProfitReportPage::class)->middleware('permission:reports.sales')->name('reports.sales-profit');
            Route::get('/reports/member-performance', MemberPerformanceReportPage::class)->middleware('permission:reports.performance')->name('reports.member-performance');
            Route::get('/reports/manual-discounts', ManualDiscountReportPage::class)->middleware('permission:reports.sales')->name('reports.manual-discount');
            Route::get('/reports/operating-expenses', OperatingExpensesPage::class)->middleware('permission:reports.sales|reports.expenses.manage')->name('reports.operating-expenses');
            Route::get('/reports/sales-profit/excel', [ReportExcelController::class, 'salesProfit'])->middleware(['permission:reports.sales', 'throttle:10,1'])->name('reports.sales-profit.excel');
            Route::get('/reports/member-performance/excel', [ReportExcelController::class, 'memberPerformance'])->middleware(['permission:reports.performance', 'throttle:10,1'])->name('reports.member-performance.excel');
            Route::get('/reports/manual-discounts/excel', [ReportExcelController::class, 'manualDiscounts'])->middleware(['permission:reports.sales', 'throttle:10,1'])->name('reports.manual-discount.excel');

            Route::get('/guides', GuidesIndexPage::class)->middleware('permission:guides.view')->name('guides.index');
            Route::get('/guides/{slug}', GuideShowPage::class)->middleware('permission:guides.view')->name('guides.show');

            Route::get('/vouchers', VoucherCampaignsPage::class)->middleware('permission:vouchers.view')->name('vouchers.index');
            Route::get('/vouchers/create', VoucherCampaignFormPage::class)->middleware('permission:vouchers.manage')->name('vouchers.create');
            Route::get('/vouchers/{campaign}/edit', VoucherCampaignFormPage::class)->middleware('permission:vouchers.manage')->name('vouchers.edit');
            Route::get('/vouchers/{campaign}/codes', VoucherCodesPage::class)->middleware('permission:vouchers.manage')->name('vouchers.codes');
            Route::get('/vouchers/redemptions', VoucherRedemptionsPage::class)->middleware('permission:vouchers.view')->name('vouchers.redemptions');
            Route::get('/vouchers/performance', VoucherPerformancePage::class)->middleware('permission:vouchers.view')->name('vouchers.performance');

            Route::get('/inventory/ingredients', IngredientsPage::class)->middleware('permission:inventory.ingredients.view|inventory.view')->name('ingredients.index');
            Route::get('/inventory/ingredients/{ingredient}/conversions', IngredientConversionsPage::class)->middleware('permission:inventory.ingredients.manage|inventory.manage')->name('ingredients.conversions');
            Route::get('/inventory/suppliers', SuppliersPage::class)->middleware('permission:inventory.suppliers.view|inventory.view')->name('suppliers.index');
            Route::get('/inventory/movements', InventoryMovementsPage::class)->middleware('permission:inventory.movements.view|inventory.view')->name('inventory-movements.index');
            Route::get('/inventory/stock-opnames', StockOpnamesPage::class)->middleware('permission:inventory.opnames.view|inventory.opnames.manage|inventory.manage|inventory.view')->name('stock-opnames.index');
            Route::get('/inventory/stock-opnames/create', StockOpnameFormPage::class)->middleware('permission:inventory.opnames.create|inventory.opnames.manage|inventory.manage')->name('stock-opnames.create');
            Route::get('/inventory/stock-opnames/{stockOpname}', StockOpnameFormPage::class)->middleware('permission:inventory.opnames.view|inventory.opnames.manage|inventory.manage|inventory.view')->name('stock-opnames.edit');

            Route::get('/inventory/purchases', PurchasesPage::class)->middleware('permission:inventory.purchases.view|inventory.purchases.manage|inventory.manage|inventory.view')->name('purchases.index');
            Route::get('/inventory/purchases/create', PurchaseFormPage::class)->middleware('permission:inventory.purchases.create|inventory.purchases.manage|inventory.manage')->name('purchases.create');
            Route::get('/inventory/purchases/{purchase}', PurchaseFormPage::class)->middleware('permission:inventory.purchases.view|inventory.purchases.manage|inventory.manage|inventory.view')->name('purchases.edit');

            Route::get('/inventory/reports/low-stock', LowStockPage::class)->middleware('permission:inventory.reports.view|inventory.view')->name('inventory-reports.low-stock');
            Route::get('/inventory/reports/stock-card', StockCardPage::class)->middleware('permission:inventory.reports.view|inventory.view')->name('inventory-reports.stock-card');
            Route::get('/inventory/reports/valuation', InventoryValuationPage::class)->middleware('permission:inventory.reports.view|inventory.view')->name('inventory-reports.valuation');
            Route::get('/inventory/reports/low-stock/excel', [ReportExcelController::class, 'inventoryLowStock'])->middleware(['permission:inventory.reports.view|inventory.view', 'throttle:10,1'])->name('inventory-reports.low-stock.excel');
            Route::get('/inventory/reports/stock-card/excel', [ReportExcelController::class, 'inventoryStockCard'])->middleware(['permission:inventory.reports.view|inventory.view', 'throttle:10,1'])->name('inventory-reports.stock-card.excel');
            Route::get('/inventory/reports/valuation/excel', [ReportExcelController::class, 'inventoryValuation'])->middleware(['permission:inventory.reports.view|inventory.view', 'throttle:10,1'])->name('inventory-reports.valuation.excel');

            Route::get('/settings', SettingsPage::class)->middleware('permission:settings.view')->name('settings.index');
            Route::get('/users', UsersPage::class)->middleware('permission:users.view')->name('users.index');

            Route::get('/roles', RoleIndex::class)->middleware('permission:roles.view')->name('roles.index');
            Route::get('/roles/create', RoleForm::class)->middleware('permission:roles.manage')->name('roles.create');
            Route::get('/roles/{role}/edit', RoleForm::class)->middleware('permission:roles.manage')->name('roles.edit');

        });
    });
} else {
    Route::prefix('admin')->group(function () {
        Route::get('/manifest.webmanifest', function () {
            $manifest = [
                'name' => config('app.name').' Admin',
                'short_name' => 'Admin',
                'start_url' => '/admin/signin',
                'scope' => '/admin/',
                'display' => 'standalone',
                'background_color' => '#ffffff',
                'theme_color' => '#111827',
                'icons' => [
                    [
                        'src' => '/assets/images/192.png',
                        'sizes' => '192x192',
                        'type' => 'image/png',
                    ],
                    [
                        'src' => '/assets/images/512.png',
                        'sizes' => '512x512',
                        'type' => 'image/png',
                    ],
                ],
            ];

            return response()->json($manifest)->header('Content-Type', 'application/manifest+json');
        })->middleware('admin.domain')->name('admin.manifest');

        Route::get('/sw.js', function () {
            $script = <<<'JS'
              const CACHE_NAME = 'admin-static-v1';

              self.addEventListener('install', (event) => {
                event.waitUntil(self.skipWaiting());
              });

              self.addEventListener('activate', (event) => {
                event.waitUntil((async () => {
                  const keys = await caches.keys();
                  await Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k)));
                  await self.clients.claim();
                })());
              });

              function isCacheable(request) {
                if (request.method !== 'GET') {
                  return false;
                }

                const url = new URL(request.url);

                if (url.origin !== self.location.origin) {
                  return false;
                }

                if (request.mode === 'navigate') {
                  return false;
                }

                if (['script', 'style', 'image', 'font'].includes(request.destination)) {
                  return true;
                }

                return /\.(?:js|css|png|jpg|jpeg|webp|svg|gif|woff2?|ttf|eot)$/.test(url.pathname);
              }

              async function cacheFirst(request) {
                const cache = await caches.open(CACHE_NAME);
                const cached = await cache.match(request);

                if (cached) {
                  return cached;
                }

                const response = await fetch(request);

                if (response.ok) {
                  await cache.put(request, response.clone());
                }

                return response;
              }

              async function networkFirst(request) {
                try {
                  return await fetch(request);
                } catch (e) {
                  return new Response(
                    '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Offline</title></head><body style="font-family:system-ui;padding:24px"><h1>Offline</h1><p>Koneksi internet tidak tersedia.</p></body></html>',
                    { headers: { 'Content-Type': 'text/html; charset=utf-8' } }
                  );
                }
              }

              self.addEventListener('fetch', (event) => {
                const request = event.request;

                if (request.method !== 'GET') {
                  return;
                }

                if (request.mode === 'navigate') {
                  event.respondWith(networkFirst(request));
                  return;
                }

                if (isCacheable(request)) {
                  event.respondWith(cacheFirst(request));
                }
              });
              JS;

            return response($script)->header('Content-Type', 'application/javascript; charset=utf-8');
        })->middleware('admin.domain')->name('admin.service-worker');

        Route::get('/login', function () {
            return redirect()->route('signin');
        })->middleware(['guest', 'admin.domain'])->name('login');

        Route::get('/signin', SignInPage::class)
            ->middleware(['guest', 'admin.domain'])
            ->name('signin');

        Route::get('/setup', InitialSetupPage::class)
            ->middleware(['guest', 'admin.domain'])
            ->name('setup');

        Route::post('/logout', function () {
            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            return redirect()->route('signin');
        })->middleware(['auth', 'active', 'admin.domain'])->name('logout');

        Route::middleware(['auth', 'active', 'admin.domain'])->group(function () {
            Route::get('/', DashboardPage::class)->middleware('dashboard.redirect')->name('dashboard');

            Route::get('/pos', PosPage::class)->middleware('permission:pos.access')->name('pos.index');
            Route::get('/midtrans/unprocessed', [TransactionController::class, 'midtransUnprocessed'])
                ->middleware('permission:transactions.view')
                ->name('midtrans.unprocessed');

            Route::get('/transactions', TransactionsPage::class)->middleware('permission:transactions.view')->name('transactions.index');
            Route::get('/transactions/{transaction}', TransactionShowPage::class)->middleware('permission:transactions.details')->name('transactions.show');

            Route::get('/products', ProductsPage::class)->middleware('permission:products.view')->name('products.index');
            Route::get('/products/create', ProductFormPage::class)->middleware('permission:products.create')->name('products.create');
            Route::get('/products/{product}/edit', ProductFormPage::class)->middleware('permission:products.edit')->name('products.edit');

            Route::get('/dining-tables', DiningTablesPage::class)->middleware('permission:dining_tables.view')->name('dining-tables.index');

            Route::get('/members', MembersPage::class)->middleware('permission:members.view')->name('members.index');
            Route::get('/members/regions', MemberRegionsPage::class)->middleware('permission:members.regions.view|members.regions.manage')->name('members.regions');

            Route::get('/reports/sales-profit', SalesProfitReportPage::class)->middleware('permission:reports.sales')->name('reports.sales-profit');
            Route::get('/reports/member-performance', MemberPerformanceReportPage::class)->middleware('permission:reports.performance')->name('reports.member-performance');
            Route::get('/reports/manual-discounts', ManualDiscountReportPage::class)->middleware('permission:reports.sales')->name('reports.manual-discount');
            Route::get('/reports/operating-expenses', OperatingExpensesPage::class)->middleware('permission:reports.sales|reports.expenses.manage')->name('reports.operating-expenses');
            Route::get('/reports/sales-profit/excel', [ReportExcelController::class, 'salesProfit'])->middleware('permission:reports.sales')->name('reports.sales-profit.excel');
            Route::get('/reports/member-performance/excel', [ReportExcelController::class, 'memberPerformance'])->middleware('permission:reports.performance')->name('reports.member-performance.excel');
            Route::get('/reports/manual-discounts/excel', [ReportExcelController::class, 'manualDiscounts'])->middleware('permission:reports.sales')->name('reports.manual-discount.excel');

            Route::get('/guides', GuidesIndexPage::class)->middleware('permission:guides.view')->name('guides.index');
            Route::get('/guides/{slug}', GuideShowPage::class)->middleware('permission:guides.view')->name('guides.show');

            Route::get('/vouchers', VoucherCampaignsPage::class)->middleware('permission:vouchers.view')->name('vouchers.index');
            Route::get('/vouchers/create', VoucherCampaignFormPage::class)->middleware('permission:vouchers.manage')->name('vouchers.create');
            Route::get('/vouchers/{campaign}/edit', VoucherCampaignFormPage::class)->middleware('permission:vouchers.manage')->name('vouchers.edit');
            Route::get('/vouchers/{campaign}/codes', VoucherCodesPage::class)->middleware('permission:vouchers.manage')->name('vouchers.codes');
            Route::get('/vouchers/redemptions', VoucherRedemptionsPage::class)->middleware('permission:vouchers.view')->name('vouchers.redemptions');
            Route::get('/vouchers/performance', VoucherPerformancePage::class)->middleware('permission:vouchers.view')->name('vouchers.performance');

            Route::get('/inventory/ingredients', IngredientsPage::class)->middleware('permission:inventory.ingredients.view|inventory.view')->name('ingredients.index');
            Route::get('/inventory/ingredients/{ingredient}/conversions', IngredientConversionsPage::class)->middleware('permission:inventory.ingredients.manage|inventory.manage')->name('ingredients.conversions');
            Route::get('/inventory/suppliers', SuppliersPage::class)->middleware('permission:inventory.suppliers.view|inventory.view')->name('suppliers.index');
            Route::get('/inventory/movements', InventoryMovementsPage::class)->middleware('permission:inventory.movements.view|inventory.view')->name('inventory-movements.index');
            Route::get('/inventory/stock-opnames', StockOpnamesPage::class)->middleware('permission:inventory.opnames.view|inventory.opnames.manage|inventory.manage|inventory.view')->name('stock-opnames.index');
            Route::get('/inventory/stock-opnames/create', StockOpnameFormPage::class)->middleware('permission:inventory.opnames.create|inventory.opnames.manage|inventory.manage')->name('stock-opnames.create');
            Route::get('/inventory/stock-opnames/{stockOpname}', StockOpnameFormPage::class)->middleware('permission:inventory.opnames.view|inventory.opnames.manage|inventory.manage|inventory.view')->name('stock-opnames.edit');

            Route::get('/inventory/purchases', PurchasesPage::class)->middleware('permission:inventory.purchases.view|inventory.purchases.manage|inventory.manage|inventory.view')->name('purchases.index');
            Route::get('/inventory/purchases/create', PurchaseFormPage::class)->middleware('permission:inventory.purchases.create|inventory.purchases.manage|inventory.manage')->name('purchases.create');
            Route::get('/inventory/purchases/{purchase}', PurchaseFormPage::class)->middleware('permission:inventory.purchases.view|inventory.purchases.manage|inventory.manage|inventory.view')->name('purchases.edit');

            Route::get('/inventory/reports/low-stock', LowStockPage::class)->middleware('permission:inventory.reports.view|inventory.view')->name('inventory-reports.low-stock');
            Route::get('/inventory/reports/stock-card', StockCardPage::class)->middleware('permission:inventory.reports.view|inventory.view')->name('inventory-reports.stock-card');
            Route::get('/inventory/reports/valuation', InventoryValuationPage::class)->middleware('permission:inventory.reports.view|inventory.view')->name('inventory-reports.valuation');
            Route::get('/inventory/reports/low-stock/excel', [ReportExcelController::class, 'inventoryLowStock'])->middleware('permission:inventory.reports.view|inventory.view')->name('inventory-reports.low-stock.excel');
            Route::get('/inventory/reports/stock-card/excel', [ReportExcelController::class, 'inventoryStockCard'])->middleware('permission:inventory.reports.view|inventory.view')->name('inventory-reports.stock-card.excel');
            Route::get('/inventory/reports/valuation/excel', [ReportExcelController::class, 'inventoryValuation'])->middleware('permission:inventory.reports.view|inventory.view')->name('inventory-reports.valuation.excel');

            Route::get('/settings', SettingsPage::class)->middleware('permission:settings.view')->name('settings.index');
            Route::get('/users', UsersPage::class)->middleware('permission:users.view')->name('users.index');

            Route::get('/roles', RoleIndex::class)->middleware('permission:roles.view')->name('roles.index');
            Route::get('/roles/create', RoleForm::class)->middleware('permission:roles.manage')->name('roles.create');
            Route::get('/roles/{role}/edit', RoleForm::class)->middleware('permission:roles.manage')->name('roles.edit');
        });
    });
}
