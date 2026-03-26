<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained('ingredients')->cascadeOnDelete();
            $table->decimal('input_quantity', 12, 3);
            $table->string('input_unit');
            $table->decimal('quantity_base', 12, 3);
            $table->decimal('input_unit_cost', 12, 2)->nullable();
            $table->decimal('unit_cost_base', 12, 2)->default(0);
            $table->decimal('subtotal_cost', 14, 2)->default(0);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['purchase_id', 'ingredient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};
