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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('event_id', 128); // evt_xxx
            $table->string('event_type', 64)->index(); // order.created, etc.
            $table->timestamp('occurred_at')->nullable();
            $table->timestamp('received_at')->useCurrent();
            $table->longText('payload_json');
            $table->string('summary', 255)->nullable();
            $table->boolean('is_feed_only')->default(false);
            $table->timestamps();

            $table->unique(['store_id', 'event_id']);
            $table->index(['store_id', 'received_at']);
            $table->index(['store_id', 'event_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
