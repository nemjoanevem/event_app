<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the events table.
     *
     * Columns:
     * - title: short event title
     * - description: long event description (nullable)
     * - starts_at: when the event starts
     * - location: where the event takes place (nullable)
     * - capacity: how many seats/tickets are available (nullable => unlimited)
     * - category: optional text label for grouping (nullable)
     * - status: draft|published|cancelled
     * - price: ticket price per unit (nullable => free event)
     */
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->dateTime('starts_at');
            $table->string('location', 255)->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->string('category', 100)->nullable();
            $table->enum('status', ['draft', 'published', 'cancelled'])->default('draft');
            $table->decimal('price', 10, 2)->nullable();
            $table->integer('max_tickets_per_user')->default(5);
            $table->timestamps();

            // Optional helpful index for queries by start date/status
            $table->index(['starts_at', 'status']);
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
