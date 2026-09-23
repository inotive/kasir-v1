<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
            $table->string('openwa_session_id')->nullable();
            $table->string('openwa_session_name')->nullable();
            $table->string('status')->default('not_configured');
            $table->string('linked_phone')->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->boolean('send_on_checkout_default')->default(true);
            $table->timestamp('last_status_checked_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->unique('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_settings');
    }
};
