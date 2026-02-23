<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed 5 events with 3 ticket types each (15 tickets total).
     */
    public function run(): void
    {
        $organizers = User::where('role', 'organizer')->get();

        if ($organizers->isEmpty()) {
            $organizers = User::factory(3)->organizer()->create()->each(function (User $user) {
                $user->assignRole('organizer');
            });
        }

        // ── 5 Events ──────────────────────────────────────────────────────────
        for ($i = 0; $i < 5; $i++) {
            $event = Event::factory()->create([
                'created_by' => $organizers->random()->id,
            ]);

            // ── 3 Tickets per event (VIP + Standard + Economy = 15 total) ───
            Ticket::factory()->vip()->create([
                'event_id' => $event->id,
                'quantity' => 50,
            ]);

            Ticket::factory()->standard()->create([
                'event_id' => $event->id,
                'quantity' => 150,
            ]);

            Ticket::factory()->economy()->create([
                'event_id' => $event->id,
                'quantity' => 300,
            ]);
        }
    }
}
