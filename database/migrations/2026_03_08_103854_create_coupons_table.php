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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();

            $table->string('code', 128);
            $table->string('discount_type', 64)->nullable();
            $table->decimal('amount', 18, 2)->nullable();
            $table->timestamp('date_expires_gmt')->nullable();
            $table->unsignedInteger('usage_count')->nullable();
            $table->unsignedInteger('usage_limit')->nullable();

            $table->longText('coupon_json');
            $table->timestamps();


            $table->unique(['store_id', 'code']);
            $table->index(['store_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
