<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->unique(['tenant_id', 'email']);
        });

        Schema::table('members', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->dropUnique(['phone']);
            $table->dropUnique(['verification_token']);
            $table->unique(['tenant_id', 'email']);
            $table->unique(['tenant_id', 'phone']);
            $table->unique(['tenant_id', 'verification_token']);
        });

        Schema::table('member_regions', function (Blueprint $table) {
            $table->dropUnique(['province', 'regency', 'district']);
            $table->unique(['tenant_id', 'province', 'regency', 'district']);
        });

        Schema::table('monthly_revenue_targets', function (Blueprint $table) {
            $table->dropUnique(['year', 'month']);
            $table->unique(['tenant_id', 'year', 'month']);
        });

        Schema::table('stock_opnames', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->unique(['tenant_id', 'code']);
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->unique(['tenant_id', 'code']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->unique(['tenant_id', 'code']);
        });

        Schema::table('voucher_codes', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->unique(['tenant_id', 'code']);
        });
    }

    public function down(): void
    {
        // Each composite unique below is currently the only index covering the
        // tenant_id foreign key, so a plain tenant_id index must be added first -
        // otherwise dropping the composite fails with "needed in a foreign key
        // constraint" (MySQL error 1553).
        Schema::table('users', function (Blueprint $table) {
            $table->index('tenant_id');
            $table->dropUnique(['tenant_id', 'email']);
            $table->unique('email');
        });

        Schema::table('members', function (Blueprint $table) {
            $table->index('tenant_id');
            $table->dropUnique(['tenant_id', 'email']);
            $table->dropUnique(['tenant_id', 'phone']);
            $table->dropUnique(['tenant_id', 'verification_token']);
            $table->unique('email');
            $table->unique('phone');
            $table->unique('verification_token');
        });

        Schema::table('member_regions', function (Blueprint $table) {
            $table->index('tenant_id');
            $table->dropUnique(['tenant_id', 'province', 'regency', 'district']);
            $table->unique(['province', 'regency', 'district']);
        });

        Schema::table('monthly_revenue_targets', function (Blueprint $table) {
            $table->index('tenant_id');
            $table->dropUnique(['tenant_id', 'year', 'month']);
            $table->unique(['year', 'month']);
        });

        Schema::table('stock_opnames', function (Blueprint $table) {
            $table->index('tenant_id');
            $table->dropUnique(['tenant_id', 'code']);
            $table->unique('code');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->index('tenant_id');
            $table->dropUnique(['tenant_id', 'code']);
            $table->unique('code');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->index('tenant_id');
            $table->dropUnique(['tenant_id', 'code']);
            $table->unique('code');
        });

        Schema::table('voucher_codes', function (Blueprint $table) {
            $table->index('tenant_id');
            $table->dropUnique(['tenant_id', 'code']);
            $table->unique('code');
        });
    }
};
