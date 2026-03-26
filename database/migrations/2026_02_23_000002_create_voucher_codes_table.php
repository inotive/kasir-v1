<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voucher_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_campaign_id')->constrained('voucher_campaigns')->cascadeOnDelete();
            $table->string('code', 80)->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('usage_limit_total')->nullable();
            $table->unsignedInteger('usage_limit_per_user')->nullable();
            $table->unsignedInteger('times_redeemed')->default(0);
            $table->timestamps();

            $table->index(['voucher_campaign_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_codes');
    }
};
