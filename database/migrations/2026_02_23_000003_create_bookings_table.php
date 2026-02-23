<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->index()                                // explicit index on FK
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('ticket_id')
                ->index()                                // explicit index on FK
                ->constrained('tickets')
                ->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->enum('status', ['pending', 'confirmed', 'cancelled'])->default('pending')->index(); // index — filter bookings by status
            $table->timestamps();

            // Composite index — "get all confirmed bookings for a specific user"
            $table->index(['user_id', 'status'], 'bookings_user_status_index');

            // Composite index — "get all bookings for a specific ticket + status"
            $table->index(['ticket_id', 'status'], 'bookings_ticket_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
