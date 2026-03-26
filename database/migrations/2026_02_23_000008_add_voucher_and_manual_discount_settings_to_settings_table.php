<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->boolean('discount_applies_before_tax')->default(true)->after('rounding_base');

            $table->unsignedInteger('manual_discount_cashier_max_percent')->default(5)->after('pos_default_payment_method');
            $table->unsignedInteger('manual_discount_cashier_max_amount')->default(50000)->after('manual_discount_cashier_max_percent');
            $table->unsignedInteger('manual_discount_cashier_daily_max_amount')->default(200000)->after('manual_discount_cashier_max_amount');

            $table->unsignedInteger('manual_discount_supervisor_max_percent')->default(15)->after('manual_discount_cashier_daily_max_amount');
            $table->unsignedInteger('manual_discount_supervisor_max_amount')->default(200000)->after('manual_discount_supervisor_max_percent');
            $table->unsignedInteger('manual_discount_supervisor_daily_max_amount')->default(1000000)->after('manual_discount_supervisor_max_amount');

            $table->unsignedInteger('manual_discount_manager_max_percent')->default(100)->after('manual_discount_supervisor_daily_max_amount');
            $table->unsignedInteger('manual_discount_manager_max_amount')->default(1000000000)->after('manual_discount_manager_max_percent');
            $table->unsignedInteger('manual_discount_manager_daily_max_amount')->default(1000000000)->after('manual_discount_manager_max_amount');

            $table->unsignedInteger('manual_discount_approval_threshold_percent')->default(10)->after('manual_discount_manager_daily_max_amount');
            $table->unsignedInteger('manual_discount_approval_threshold_amount')->default(100000)->after('manual_discount_approval_threshold_percent');

            $table->unsignedInteger('voucher_alert_days_before_expiry')->default(7)->after('manual_discount_approval_threshold_amount');
            $table->unsignedInteger('voucher_alert_quota_threshold')->default(10)->after('voucher_alert_days_before_expiry');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'discount_applies_before_tax',
                'manual_discount_cashier_max_percent',
                'manual_discount_cashier_max_amount',
                'manual_discount_cashier_daily_max_amount',
                'manual_discount_supervisor_max_percent',
                'manual_discount_supervisor_max_amount',
                'manual_discount_supervisor_daily_max_amount',
                'manual_discount_manager_max_percent',
                'manual_discount_manager_max_amount',
                'manual_discount_manager_daily_max_amount',
                'manual_discount_approval_threshold_percent',
                'manual_discount_approval_threshold_amount',
                'voucher_alert_days_before_expiry',
                'voucher_alert_quota_threshold',
            ]);
        });
    }
};
