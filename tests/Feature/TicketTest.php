<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketTest extends TestCase
{
    use RefreshDatabase;

    protected User $organizer;
    protected User $customer;
    protected Event $event;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->organizer = User::factory()->organizer()->create();
        $this->organizer->assignRole('organizer');

        $this->customer = User::factory()->customer()->create();
        $this->customer->assignRole('customer');

        $this->event = Event::factory()->create(['created_by' => $this->organizer->id]);
    }

    // ─── Create Ticket ────────────────────────────────────────────────────────

    public function test_organizer_can_create_ticket_for_own_event(): void
    {
        $response = $this->actingAs($this->organizer, 'sanctum')
            ->postJson("/api/events/{$this->event->id}/tickets", [
                'type' => 'VIP',
                'price' => 250.00,
                'quantity' => 50,
            ]);

        $response->assertStatus(201)
            ->assertJson(['message' => 'Ticket created successfully.']);

        $this->assertDatabaseHas('tickets', [
            'event_id' => $this->event->id,
            'type' => 'VIP',
        ]);
    }

    public function test_organizer_cannot_create_ticket_for_others_event(): void
    {
        $otherOrganizer = User::factory()->organizer()->create();
        $otherOrganizer->assignRole('organizer');

        $response = $this->actingAs($otherOrganizer, 'sanctum')
            ->postJson("/api/events/{$this->event->id}/tickets", [
                'type' => 'VIP',
                'price' => 250.00,
                'quantity' => 50,
            ]);

        $response->assertStatus(403);
    }

    public function test_customer_cannot_create_ticket(): void
    {
        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/events/{$this->event->id}/tickets", [
                'type' => 'Standard',
                'price' => 50.00,
                'quantity' => 100,
            ]);

        $response->assertStatus(403);
    }

    // ─── Update Ticket ────────────────────────────────────────────────────────

    public function test_organizer_can_update_ticket_on_own_event(): void
    {
        $ticket = Ticket::factory()->create([
            'event_id' => $this->event->id,
            'price' => 100.00,
        ]);

        $response = $this->actingAs($this->organizer, 'sanctum')
            ->putJson("/api/tickets/{$ticket->id}", [
                'price' => 120.00,
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'price' => 120.00]);
    }

    // ─── Delete Ticket ────────────────────────────────────────────────────────

    public function test_organizer_can_delete_ticket_on_own_event(): void
    {
        $ticket = Ticket::factory()->create(['event_id' => $this->event->id]);

        $response = $this->actingAs($this->organizer, 'sanctum')
            ->deleteJson("/api/tickets/{$ticket->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('tickets', ['id' => $ticket->id]);
    }

    // ─── Validation ───────────────────────────────────────────────────────────

    public function test_ticket_creation_validates_fields(): void
    {
        $response = $this->actingAs($this->organizer, 'sanctum')
            ->postJson("/api/events/{$this->event->id}/tickets", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type', 'price', 'quantity']);
    }

    public function test_ticket_type_must_be_valid_enum(): void
    {
        $response = $this->actingAs($this->organizer, 'sanctum')
            ->postJson("/api/events/{$this->event->id}/tickets", [
                'type' => 'INVALID',
                'price' => 50.00,
                'quantity' => 10,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('type');
    }
}
