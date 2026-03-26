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
        Schema::create('product_complex_package_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('component_product_id')->constrained('products');
            $table->unsignedInteger('quantity');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['package_product_id', 'component_product_id'], 'pcpi_pkg_component_unique');
            $table->index(['package_product_id', 'sort_order'], 'pcpi_pkg_sort_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_complex_package_items');
    }
};
