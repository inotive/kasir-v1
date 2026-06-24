<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_item_addons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_item_id')->constrained('transaction_items')->cascadeOnDelete();
            $table->foreignId('addon_id')->nullable()->constrained('addons')->nullOnDelete();
            $table->string('name');
            $table->integer('price')->default(0);
            $table->integer('quantity')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_item_addons');
    }
};
