<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     * Order: Roles & Permissions → Users (assigned roles) → Events + Tickets → Bookings + Payments
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,  // Must run first — Users depend on roles existing
            UserSeeder::class,
            EventSeeder::class,
            BookingSeeder::class,
        ]);
    }
}
