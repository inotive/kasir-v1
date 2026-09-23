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
        Schema::table('operating_expenses', function (Blueprint $table) {
            $table->decimal('quantity', 12, 3)->nullable()->after('category');
            $table->string('unit', 50)->nullable()->after('quantity');
            $table->decimal('unit_cost', 14, 2)->nullable()->after('unit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('operating_expenses', function (Blueprint $table) {
            $table->dropColumn(['quantity', 'unit', 'unit_cost']);
        });
    }
};
