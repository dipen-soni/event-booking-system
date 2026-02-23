<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')
                ->unique()                              // unique index — one payment per booking (hasOne)
                ->constrained('bookings')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->index()                              // explicit index on FK
                ->constrained('users')
                ->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['success', 'failed', 'refunded'])->default('success')->index(); // index — filter payments by status
            $table->timestamps();

            // Composite index — "get all successful payments for a user"
            $table->index(['user_id', 'status'], 'payments_user_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
