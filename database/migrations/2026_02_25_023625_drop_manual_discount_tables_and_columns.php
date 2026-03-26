<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Drop foreign key and columns from transactions table
        Schema::table('transactions', function (Blueprint $table) {
            // Drop foreign key if exists
            if (Schema::hasColumn('transactions', 'manual_discount_reason_id')) {
                $table->dropForeign(['manual_discount_reason_id']);
            }

            $table->dropColumn([
                'manual_discount_reason_id',
                'manual_discount_reason_label',
                'manual_discount_approved_by_user_id',
                'manual_discount_approved_at',
                'manual_discount_approval_note',
            ]);
        });

        // 2. Drop manual_discount_reasons table
        Schema::dropIfExists('manual_discount_reasons');

        // 3. Drop manual_discount_role_limits table
        Schema::dropIfExists('manual_discount_role_limits');

        // 4. Drop columns from settings table
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
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
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Recreate manual_discount_reasons table
        Schema::create('manual_discount_reasons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('label', 120);
            $table->boolean('is_active')->default(true);
            $table->boolean('requires_note')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // 2. Recreate manual_discount_role_limits table
        Schema::create('manual_discount_role_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->unsignedInteger('max_percent')->default(0);
            $table->unsignedBigInteger('max_amount')->default(0);
            $table->unsignedBigInteger('daily_max_amount')->default(0);
            $table->timestamps();
            $table->unique('role_id');
        });

        // 3. Add columns back to transactions table
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('manual_discount_reason_id')->nullable()->after('manual_discount_amount')->constrained('manual_discount_reasons')->nullOnDelete();
            $table->string('manual_discount_reason_label', 120)->nullable()->after('manual_discount_reason_id');
            // manual_discount_note is assumed to be kept based on instructions, so insert after it or where it was
            // But we didn't drop manual_discount_note, so we add after manual_discount_by_user_id (which we also kept)
            $table->unsignedBigInteger('manual_discount_approved_by_user_id')->nullable()->after('manual_discount_by_user_id');
            $table->timestamp('manual_discount_approved_at')->nullable()->after('manual_discount_approved_by_user_id');
            $table->string('manual_discount_approval_note', 255)->nullable()->after('manual_discount_approved_at');
        });

        // 4. Add columns back to settings table
        Schema::table('settings', function (Blueprint $table) {
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
        });
    }
};
