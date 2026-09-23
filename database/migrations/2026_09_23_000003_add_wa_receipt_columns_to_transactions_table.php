<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->timestamp('wa_receipt_sent_at')->nullable()->after('receipt_emailed_at');
            $table->string('wa_receipt_status')->nullable()->after('wa_receipt_sent_at');
            $table->text('wa_receipt_error')->nullable()->after('wa_receipt_status');
            $table->string('wa_receipt_token', 64)->nullable()->unique()->after('wa_receipt_error');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['wa_receipt_sent_at', 'wa_receipt_status', 'wa_receipt_error', 'wa_receipt_token']);
        });
    }
};
