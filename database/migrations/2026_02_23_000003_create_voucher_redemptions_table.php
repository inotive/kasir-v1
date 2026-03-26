<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voucher_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_campaign_id')->constrained('voucher_campaigns')->cascadeOnDelete();
            $table->foreignId('voucher_code_id')->constrained('voucher_codes')->cascadeOnDelete();
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->string('guest_identifier', 100)->nullable();
            $table->unsignedInteger('discount_amount');
            $table->json('snapshot')->nullable();
            $table->timestamp('redeemed_at');
            $table->timestamps();

            $table->unique(['transaction_id']);
            $table->index(['voucher_code_id', 'redeemed_at']);
            $table->index(['member_id', 'redeemed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_redemptions');
    }
};
