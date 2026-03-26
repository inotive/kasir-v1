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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->string('channel', 30)->default('pos');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->enum('order_type', ['take_away', 'dine_in'])->default('take_away');
            $table->foreignId('dining_table_id')->nullable()->constrained('dining_tables')->cascadeOnDelete();
            $table->integer('subtotal');
            $table->decimal('tax_percentage', 5, 2)->nullable();
            $table->integer('tax_amount')->nullable();
            $table->integer('rounding_amount')->default(0);
            $table->integer('cash_received')->nullable();
            $table->integer('cash_change')->nullable();
            $table->unsignedInteger('refunded_amount')->default(0);
            $table->integer('total');
            $table->string('checkout_link');
            $table->string('payment_method');
            $table->string('payment_status');
            $table->string('order_status', 30)->default('new');
            $table->timestamp('paid_at')->nullable();
            $table->boolean('is_midtrans_processed')->default(false);
            $table->string('external_id');
            $table->timestamp('voided_at')->nullable();
            $table->unsignedBigInteger('voided_by_user_id')->nullable();
            $table->string('void_reason', 255)->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->unsignedBigInteger('refunded_by_user_id')->nullable();
            $table->string('refund_reason', 255)->nullable();

            $table->timestamp('inventory_applied_at')->nullable();

            $table->timestamp('kitchen_processed_at')->nullable();
            $table->unsignedBigInteger('kitchen_processed_by_user_id')->nullable();

            $table->string('self_order_token', 80)->nullable();
            $table->string('payment_session_hash', 64)->nullable();

            $table->string('midtrans_snap_token', 80)->nullable();
            $table->string('midtrans_redirect_url', 255)->nullable();
            $table->string('midtrans_status', 50)->nullable();
            $table->json('midtrans_payload')->nullable();

            $table->index(['channel', 'payment_status']);
            $table->index(['channel', 'order_status']);
            $table->index('self_order_token');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
