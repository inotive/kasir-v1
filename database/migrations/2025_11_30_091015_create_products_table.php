<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        Schema::create('products', function (Blueprint $table) use ($driver) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->string('image');
            $table->boolean('is_available')->default(true);
            $table->boolean('is_promo')->default(false);
            $table->boolean('is_favorite')->default(false);
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->foreignId('printer_source_id')->nullable()->constrained('printer_sources')->nullOnDelete();
            $table->timestamps();

            if ($driver === 'mysql' || $driver === 'mariadb') {
                $table->fullText(['name', 'description'], 'fulltext_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
