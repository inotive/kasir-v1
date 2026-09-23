<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('cashier_user_id')
                ->nullable()
                ->after('member_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->index(
                ['tenant_id', 'cashier_user_id', 'paid_at'],
                'transactions_tenant_cashier_paid_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_tenant_cashier_paid_index');
            $table->dropConstrainedForeignId('cashier_user_id');
        });
    }
};
