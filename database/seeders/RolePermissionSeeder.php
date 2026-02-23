<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ── Define all permissions ─────────────────────────────────────────────
        $permissions = [
            // Events
            'event.viewAny',
            'event.view',
            'event.create',
            'event.update',
            'event.delete',

            // Tickets
            'ticket.viewAny',
            'ticket.view',
            'ticket.create',
            'ticket.update',
            'ticket.delete',

            // Bookings
            'booking.viewAny',
            'booking.view',
            'booking.create',
            'booking.update',
            'booking.cancel',
            'booking.delete',

            // Payments
            'payment.viewAny',
            'payment.view',
            'payment.refund',

            // Users
            'user.viewAny',
            'user.view',
            'user.update',
            'user.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // ── Admin — full access ────────────────────────────────────────────────
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions($permissions);

        // ── Organizer — manage their own events & tickets ─────────────────────
        $organizer = Role::firstOrCreate(['name' => 'organizer']);
        $organizer->syncPermissions([
            'event.viewAny',
            'event.view',
            'event.create',
            'event.update',
            'event.delete',
            'ticket.viewAny',
            'ticket.view',
            'ticket.create',
            'ticket.update',
            'ticket.delete',
            'booking.viewAny',
            'booking.view',                // read-only on bookings
            'payment.viewAny',
            'payment.view',                // read-only on payments
        ]);

        // ── Customer — book tickets & view own bookings ───────────────────────
        $customer = Role::firstOrCreate(['name' => 'customer']);
        $customer->syncPermissions([
            'event.viewAny',
            'event.view',
            'ticket.viewAny',
            'ticket.view',
            'booking.view',
            'booking.create',
            'booking.cancel',
            'payment.view',
        ]);
    }
}
