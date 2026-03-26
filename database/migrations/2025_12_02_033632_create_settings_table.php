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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('store_logo')->nullable();
            $table->string('store_name')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->boolean('payment_gateway_enabled')->default(true);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->integer('rounding_base')->default(100);
            $table->string('pos_default_customer_name')->default('Walk-in');
            $table->string('pos_default_payment_method')->default('cash');

            $table->boolean('corrections_void_pending_requires_approval')->default(false);
            $table->boolean('corrections_refund_requires_approval_for_cash')->default(true);
            $table->unsignedInteger('corrections_refund_quick_max_amount')->default(20000);
            $table->unsignedInteger('corrections_refund_quick_max_count_per_day')->default(2);
            $table->unsignedInteger('corrections_void_quick_max_count_per_day')->default(3);
            $table->unsignedInteger('corrections_void_quick_window_minutes')->default(5);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
