<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voucher_campaign_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_campaign_id')->constrained('voucher_campaigns')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['voucher_campaign_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_campaign_category');
    }
};
