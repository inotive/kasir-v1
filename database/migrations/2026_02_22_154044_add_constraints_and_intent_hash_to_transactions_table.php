<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('cart_hash', 64)->nullable()->after('payment_session_hash');
            $table->string('payment_intent_hash', 64)->nullable()->after('cart_hash');

            $table->unique('code');
            $table->unique('external_id');
            $table->index('payment_session_hash');
            $table->index('cart_hash');
            $table->unique('payment_intent_hash');
            $table->unique('midtrans_snap_token');

            $table->foreign('voided_by_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('refunded_by_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('kitchen_processed_by_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['voided_by_user_id']);
            $table->dropForeign(['refunded_by_user_id']);
            $table->dropForeign(['kitchen_processed_by_user_id']);

            $table->dropUnique(['code']);
            $table->dropUnique(['external_id']);
            $table->dropIndex(['payment_session_hash']);
            $table->dropIndex(['cart_hash']);
            $table->dropUnique(['payment_intent_hash']);
            $table->dropUnique(['midtrans_snap_token']);

            $table->dropColumn(['cart_hash', 'payment_intent_hash']);
        });
    }
};
