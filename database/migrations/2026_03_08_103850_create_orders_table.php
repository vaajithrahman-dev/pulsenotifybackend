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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();

            $table->unsignedBigInteger('order_id'); // Woo order ID
            $table->string('order_number', 64)->nullable();

            $table->string('status', 32)->index(); // normalized
            $table->string('currency', 8)->nullable();

            $table->decimal('total', 18, 2)->nullable();
            $table->decimal('subtotal', 18, 2)->nullable();
            $table->decimal('discount_total', 18, 2)->nullable();
            $table->decimal('shipping_total', 18, 2)->nullable();
            $table->decimal('tax_total', 18, 2)->nullable();

            $table->string('payment_method', 64)->nullable();
            $table->string('payment_method_title', 255)->nullable();

            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('billing_email', 255)->nullable()->index();
            $table->string('billing_first_name', 120)->nullable();
            $table->string('billing_last_name', 120)->nullable();

            $table->string('coupon_codes', 500)->nullable();

            $table->timestamp('created_at_gmt')->nullable();
            $table->timestamp('paid_at_gmt')->nullable();
            $table->timestamp('modified_at_gmt')->nullable()->index();

            $table->unsignedTinyInteger('snapshot_quality')->default(0);

            $table->longText('order_json'); // full snapshot
            $table->timestamp('synced_at')->useCurrent();

            $table->timestamps();

            $table->unique(['store_id', 'order_id']);
            $table->index(['store_id', 'status']);
            $table->index(['store_id', 'updated_at']);

            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
