<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the bookings table.
     *
     * This links a user to an event with a given quantity and total price.
     * Notes:
     * - start_at is stored for convenience/history (can mirror event.starts_at).
     * - (user_id, event_id) is unique to consolidate quantity per user-event.
     */
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');

            $table->foreignId('event_id')
                ->constrained()
                ->onDelete('cascade');

            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('total_price', 10, 2)->default(0);
            $table->dateTime('start_at')->nullable();

            $table->enum('status', ['confirmed', 'cancelled'])->default('confirmed');

            $table->timestamps();

            $table->index(['event_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
