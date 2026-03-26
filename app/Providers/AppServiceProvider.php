<?php

namespace App\Providers;

use App\Helpers\MenuHelper;
use App\Models\DiningTable;
use App\Models\PrinterSource;
use App\Models\Transaction;
use App\Observers\DiningTableObserver;
use App\Observers\TransactionObserver;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (! function_exists('generate_qr_code') && file_exists(app_path('helpers.php'))) {
            require_once app_path('helpers.php');
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Transaction::observe(TransactionObserver::class);
        DiningTable::observe(DiningTableObserver::class);

        $printerSourcesForJs = [];
        if (Schema::hasTable('printer_sources')) {
            $printerSourcesForJs = PrinterSource::query()
                ->orderBy('name')
                ->get(['id', 'name', 'type'])
                ->map(fn (PrinterSource $s) => [
                    'id' => (int) $s->id,
                    'name' => (string) $s->name,
                    'type' => (string) $s->type,
                ])
                ->all();
        }

        View::share('printerSourcesForJs', $printerSourcesForJs);

        View::composer('layouts.sidebar', function (\Illuminate\View\View $view): void {
            $user = auth()->user();

            $view->with([
                'menuGroups' => MenuHelper::getMenuGroups(),
                'currentPath' => request()->path(),
                'canAccessPos' => $user?->can('pos.access') ?? false,
            ]);
        });
    }
}
