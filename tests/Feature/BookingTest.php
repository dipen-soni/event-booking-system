<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;
    protected User $organizer;
    protected Event $event;
    protected Ticket $ticket;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->organizer = User::factory()->organizer()->create();
        $this->organizer->assignRole('organizer');

        $this->customer = User::factory()->customer()->create();
        $this->customer->assignRole('customer');

        $this->event = Event::factory()->create(['created_by' => $this->organizer->id]);

        $this->ticket = Ticket::factory()->create([
            'event_id' => $this->event->id,
            'type' => 'Standard',
            'price' => 100.00,
            'quantity' => 50,
        ]);
    }

    // ─── Create Booking ───────────────────────────────────────────────────────

    public function test_customer_can_book_tickets(): void
    {
        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/tickets/{$this->ticket->id}/bookings", [
                'quantity' => 2,
            ]);

        $response->assertStatus(201)
            ->assertJson(['message' => 'Booking created successfully.']);

        $this->assertDatabaseHas('bookings', [
            'user_id' => $this->customer->id,
            'ticket_id' => $this->ticket->id,
            'quantity' => 2,
            'status' => 'pending',
        ]);
    }

    public function test_booking_fails_when_quantity_exceeds_availability(): void
    {
        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/tickets/{$this->ticket->id}/bookings", [
                'quantity' => 999,
            ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Only 50 ticket(s) available.']);
    }

    public function test_double_booking_same_ticket_prevented(): void
    {
        // First booking
        Booking::create([
            'user_id' => $this->customer->id,
            'ticket_id' => $this->ticket->id,
            'quantity' => 1,
            'status' => 'confirmed',
        ]);

        // Second booking — should be blocked by middleware
        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/tickets/{$this->ticket->id}/bookings", [
                'quantity' => 1,
            ]);

        $response->assertStatus(409)
            ->assertJsonFragment(['message' => 'You already have an active booking for this ticket.']);
    }

    public function test_can_book_after_cancelling_previous(): void
    {
        Booking::create([
            'user_id' => $this->customer->id,
            'ticket_id' => $this->ticket->id,
            'quantity' => 1,
            'status' => 'cancelled',
        ]);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/tickets/{$this->ticket->id}/bookings", [
                'quantity' => 1,
            ]);

        $response->assertStatus(201);
    }

    public function test_booking_requires_quantity(): void
    {
        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/tickets/{$this->ticket->id}/bookings", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('quantity');
    }

    // ─── List Bookings ────────────────────────────────────────────────────────

    public function test_customer_can_list_their_bookings(): void
    {
        Booking::factory(3)->create([
            'user_id' => $this->customer->id,
            'ticket_id' => $this->ticket->id,
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->getJson('/api/bookings');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_customer_only_sees_own_bookings(): void
    {
        $otherCustomer = User::factory()->customer()->create();
        $otherCustomer->assignRole('customer');

        Booking::factory(2)->create([
            'user_id' => $this->customer->id,
            'ticket_id' => $this->ticket->id,
        ]);
        Booking::factory(5)->create([
            'user_id' => $otherCustomer->id,
            'ticket_id' => $this->ticket->id,
        ]);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->getJson('/api/bookings');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    // ─── Cancel Booking ───────────────────────────────────────────────────────

    public function test_customer_can_cancel_own_booking(): void
    {
        $booking = Booking::create([
            'user_id' => $this->customer->id,
            'ticket_id' => $this->ticket->id,
            'quantity' => 1,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->putJson("/api/bookings/{$booking->id}/cancel");

        $response->assertOk()
            ->assertJson(['message' => 'Booking cancelled successfully.']);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_customer_cannot_cancel_others_booking(): void
    {
        $otherCustomer = User::factory()->customer()->create();
        $otherCustomer->assignRole('customer');

        $booking = Booking::create([
            'user_id' => $otherCustomer->id,
            'ticket_id' => $this->ticket->id,
            'quantity' => 1,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->putJson("/api/bookings/{$booking->id}/cancel");

        $response->assertStatus(403);
    }

    public function test_cannot_cancel_already_cancelled_booking(): void
    {
        $booking = Booking::create([
            'user_id' => $this->customer->id,
            'ticket_id' => $this->ticket->id,
            'quantity' => 1,
            'status' => 'cancelled',
        ]);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->putJson("/api/bookings/{$booking->id}/cancel");

        $response->assertStatus(422);
    }

    public function test_unauthenticated_cannot_book(): void
    {
        $response = $this->postJson("/api/tickets/{$this->ticket->id}/bookings", [
            'quantity' => 1,
        ]);

        $response->assertStatus(401);
    }
}
