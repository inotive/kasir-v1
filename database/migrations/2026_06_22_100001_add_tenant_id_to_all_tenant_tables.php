<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'categories',
        'printer_sources',
        'products',
        'product_variants',
        'product_subvariants',
        'product_recipes',
        'product_variant_recipes',
        'product_package_items',
        'product_complex_package_items',
        'product_addons',
        'dining_tables',
        'member_regions',
        'members',
        'transactions',
        'transaction_items',
        'transaction_events',
        'transaction_item_addons',
        'addon_categories',
        'addons',
        'settings',
        'monthly_revenue_targets',
        'suppliers',
        'ingredients',
        'ingredient_unit_conversions',
        'inventory_movements',
        'stock_opnames',
        'stock_opname_items',
        'purchases',
        'purchase_items',
        'voucher_campaigns',
        'voucher_codes',
        'voucher_redemptions',
        'voucher_campaign_category',
        'operating_expenses',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            if (! Schema::hasColumn($table, 'tenant_id')) {
                Schema::table($table, function (Blueprint $tableBlueprint) use ($table) {
                    $tableBlueprint->foreignId('tenant_id')->nullable()->after('id')
                        ->constrained('tenants')->cascadeOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            if (Schema::hasColumn($table, 'tenant_id')) {
                Schema::table($table, function (Blueprint $tableBlueprint) use ($table) {
                    $tableBlueprint->dropForeign(['tenant_id']);
                    $tableBlueprint->dropColumn('tenant_id');
                });
            }
        }
    }
};
