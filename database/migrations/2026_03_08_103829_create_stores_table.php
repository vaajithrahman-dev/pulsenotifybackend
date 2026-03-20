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
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('store_id', 64)->unique(); // e.g. store_xxx
            $table->string('store_name')->nullable();
            $table->string('account_email')->nullable()->index();
            $table->string('store_site_url')->nullable(); // https://example.com
            $table->string('signing_secret', 255); // per-store HMAC secret (store securely)
            $table->string('status', 32)->default('active'); // active/disabled
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
