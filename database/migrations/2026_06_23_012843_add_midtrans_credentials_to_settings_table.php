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
        Schema::table('settings', function (Blueprint $table) {
            $table->string('midtrans_merchant_id', 100)->nullable()->after('qris_image');
            $table->string('midtrans_server_key', 255)->nullable()->after('midtrans_merchant_id');
            $table->string('midtrans_client_key', 255)->nullable()->after('midtrans_server_key');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['midtrans_merchant_id', 'midtrans_server_key', 'midtrans_client_key']);
        });
    }
};
