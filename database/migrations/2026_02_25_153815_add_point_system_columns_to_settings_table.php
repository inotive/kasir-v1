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
        Schema::table('settings', function (Blueprint $table) {
            $table->decimal('point_earning_rate', 12, 4)->default(0)->after('rounding_base'); // Points earned per currency unit or similar ratio
            $table->decimal('point_redemption_value', 12, 2)->default(0)->after('point_earning_rate'); // Value of 1 point in currency
            $table->integer('min_redemption_points')->default(0)->after('point_redemption_value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['point_earning_rate', 'point_redemption_value', 'min_redemption_points']);
        });

    }
};
