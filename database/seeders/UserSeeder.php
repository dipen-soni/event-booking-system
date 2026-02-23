<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // ── 2 Admins ──────────────────────────────────────────────────────────
        $admin1 = User::factory()->admin()->create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);
        $admin1->assignRole('admin');

        $admin2 = User::factory()->admin()->create([
            'name' => 'Admin Two',
            'email' => 'admin2@example.com',
            'password' => Hash::make('password'),
        ]);
        $admin2->assignRole('admin');

        // ── 3 Organizers ─────────────────────────────────────────────────────
        User::factory(3)->organizer()->create()->each(function (User $user) {
            $user->assignRole('organizer');
        });

        // ── 10 Customers ──────────────────────────────────────────────────────
        User::factory(10)->customer()->create()->each(function (User $user) {
            $user->assignRole('customer');
        });
    }
}
