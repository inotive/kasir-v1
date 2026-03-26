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
        Schema::table('product_complex_package_items', function (Blueprint $table) {
            $table->boolean('is_splitable')->default(false)->after('quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_complex_package_items', function (Blueprint $table) {
            $table->dropColumn('is_splitable');
        });
    }
};
