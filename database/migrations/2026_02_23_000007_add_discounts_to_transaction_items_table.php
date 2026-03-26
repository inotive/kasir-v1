<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaction_items', function (Blueprint $table) {
            $table->unsignedInteger('voucher_discount_amount')->default(0)->after('subtotal');
            $table->unsignedInteger('manual_discount_amount')->default(0)->after('voucher_discount_amount');
        });
    }

    public function down(): void
    {
        Schema::table('transaction_items', function (Blueprint $table) {
            $table->dropColumn(['voucher_discount_amount', 'manual_discount_amount']);
        });
    }
};
