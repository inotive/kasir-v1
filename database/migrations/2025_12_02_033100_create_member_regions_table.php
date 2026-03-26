<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_regions', function (Blueprint $table) {
            $table->id();
            $table->string('province');
            $table->string('regency');
            $table->string('district')->nullable();
            $table->longText('geojson')->nullable();
            $table->timestamps();

            $table->index(['province', 'regency', 'district']);
            $table->unique(['province', 'regency', 'district']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_regions');
    }
};
