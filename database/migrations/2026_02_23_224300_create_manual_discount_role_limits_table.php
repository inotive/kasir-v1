<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manual_discount_role_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->unsignedInteger('max_percent')->default(0);
            $table->unsignedBigInteger('max_amount')->default(0);
            $table->unsignedBigInteger('daily_max_amount')->default(0);
            $table->timestamps();

            $table->unique('role_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manual_discount_role_limits');
    }
};
