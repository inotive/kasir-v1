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
        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedInteger('point_discount_amount')->default(0)->after('discount_total_amount');
            $table->unsignedInteger('points_redeemed')->default(0)->after('point_discount_amount');
            $table->unsignedInteger('points_earned')->default(0)->after('points_redeemed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['point_discount_amount', 'points_redeemed', 'points_earned']);
        });
    }
};
