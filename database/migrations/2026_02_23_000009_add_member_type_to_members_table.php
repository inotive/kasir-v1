<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->enum('member_type', ['umum', 'regular', 'premium'])->default('umum')->after('member_region_id');
            $table->index('member_type');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropIndex(['member_type']);
            $table->dropColumn('member_type');
        });
    }
};
