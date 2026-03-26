<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingredient_unit_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_id')->constrained('ingredients')->cascadeOnDelete();
            $table->string('unit');
            $table->decimal('factor_to_base', 12, 6);
            $table->timestamps();

            $table->unique(['ingredient_id', 'unit']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingredient_unit_conversions');
    }
};
