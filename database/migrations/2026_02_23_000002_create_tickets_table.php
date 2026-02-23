<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')
                ->index()                                // explicit index on FK
                ->constrained('events')
                ->cascadeOnDelete();
            $table->enum('type', ['VIP', 'Standard', 'Economy']);
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('quantity');
            $table->timestamps();

            // Composite index — typical query: "get tickets of a given type for an event"
            $table->index(['event_id', 'type'], 'tickets_event_type_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
