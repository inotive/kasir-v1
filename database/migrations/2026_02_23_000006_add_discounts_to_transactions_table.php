<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('voucher_campaign_id')->nullable()->after('dining_table_id')->constrained('voucher_campaigns')->nullOnDelete();
            $table->foreignId('voucher_code_id')->nullable()->after('voucher_campaign_id')->constrained('voucher_codes')->nullOnDelete();
            $table->string('voucher_code', 80)->nullable()->after('voucher_code_id');
            $table->unsignedInteger('voucher_discount_amount')->default(0)->after('subtotal');

            $table->enum('manual_discount_type', ['percent', 'fixed_amount'])->nullable()->after('voucher_discount_amount');
            $table->unsignedInteger('manual_discount_value')->nullable()->after('manual_discount_type');
            $table->unsignedInteger('manual_discount_amount')->default(0)->after('manual_discount_value');
            $table->foreignId('manual_discount_reason_id')->nullable()->after('manual_discount_amount')->constrained('manual_discount_reasons')->nullOnDelete();
            $table->string('manual_discount_reason_label', 120)->nullable()->after('manual_discount_reason_id');
            $table->string('manual_discount_note', 255)->nullable()->after('manual_discount_reason_label');
            $table->unsignedBigInteger('manual_discount_by_user_id')->nullable()->after('manual_discount_note');
            $table->unsignedBigInteger('manual_discount_approved_by_user_id')->nullable()->after('manual_discount_by_user_id');
            $table->timestamp('manual_discount_approved_at')->nullable()->after('manual_discount_approved_by_user_id');
            $table->string('manual_discount_approval_note', 255)->nullable()->after('manual_discount_approved_at');
            $table->unsignedInteger('discount_total_amount')->default(0)->after('manual_discount_approval_note');

            $table->index(['voucher_code_id', 'voucher_campaign_id']);
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['voucher_code_id', 'voucher_campaign_id']);
            $table->dropConstrainedForeignId('voucher_campaign_id');
            $table->dropConstrainedForeignId('voucher_code_id');
            $table->dropConstrainedForeignId('manual_discount_reason_id');
            $table->dropColumn([
                'voucher_code',
                'voucher_discount_amount',
                'manual_discount_type',
                'manual_discount_value',
                'manual_discount_amount',
                'manual_discount_reason_label',
                'manual_discount_note',
                'manual_discount_by_user_id',
                'manual_discount_approved_by_user_id',
                'manual_discount_approved_at',
                'manual_discount_approval_note',
                'discount_total_amount',
            ]);
        });
    }
};
