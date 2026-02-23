<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BookingSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $customers = User::where('role', 'customer')->get();
        $tickets = Ticket::all();

        if ($customers->isEmpty() || $tickets->isEmpty()) {
            return;
        }

        // Create 20 bookings spread across customers and tickets
        for ($i = 0; $i < 20; $i++) {
            $customer = $customers->random();
            $ticket = $tickets->random();
            $status = fake()->randomElement(['pending', 'confirmed', 'cancelled']);

            $booking = Booking::create([
                'user_id' => $customer->id,
                'ticket_id' => $ticket->id,
                'quantity' => fake()->numberBetween(1, 4),
                'status' => $status,
            ]);

            // Create a payment only for confirmed bookings
            if ($status === 'confirmed') {
                Payment::create([
                    'booking_id' => $booking->id,
                    'user_id' => $customer->id,
                    'amount' => $ticket->price * $booking->quantity,
                    'status' => 'success',
                ]);
            }
        }
    }
}
