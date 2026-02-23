<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $organizer;
    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->admin()->create();
        $this->admin->assignRole('admin');

        $this->organizer = User::factory()->organizer()->create();
        $this->organizer->assignRole('organizer');

        $this->customer = User::factory()->customer()->create();
        $this->customer->assignRole('customer');
    }

    // ─── Browse Events (public) ───────────────────────────────────────────────

    public function test_anyone_can_browse_events(): void
    {
        Event::factory(3)->create(['created_by' => $this->organizer->id]);

        $response = $this->getJson('/api/events');

        $response->assertOk()
            ->assertJsonStructure(['data', 'current_page', 'total']);
    }

    public function test_events_can_be_searched_by_title(): void
    {
        Event::factory()->create([
            'title' => 'Live Music Concert',
            'created_by' => $this->organizer->id,
        ]);
        Event::factory()->create([
            'title' => 'Tech Conference',
            'created_by' => $this->organizer->id,
        ]);

        $response = $this->getJson('/api/events?search=Music');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Live Music Concert', $response->json('data.0.title'));
    }

    public function test_events_can_be_filtered_by_location(): void
    {
        Event::factory()->create([
            'location' => 'Delhi, India',
            'created_by' => $this->organizer->id,
        ]);
        Event::factory()->create([
            'location' => 'Mumbai, India',
            'created_by' => $this->organizer->id,
        ]);

        $response = $this->getJson('/api/events?location=Delhi');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_events_can_be_filtered_by_date_range(): void
    {
        Event::factory()->create([
            'date' => '2026-04-01',
            'created_by' => $this->organizer->id,
        ]);
        Event::factory()->create([
            'date' => '2026-08-01',
            'created_by' => $this->organizer->id,
        ]);

        $response = $this->getJson('/api/events?date_from=2026-03-01&date_to=2026-05-01');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_events_support_pagination(): void
    {
        Event::factory(20)->create(['created_by' => $this->organizer->id]);

        $response = $this->getJson('/api/events?per_page=5');

        $response->assertOk();
        $this->assertCount(5, $response->json('data'));
        $this->assertEquals(20, $response->json('total'));
    }

    // ─── Show Event ───────────────────────────────────────────────────────────

    public function test_anyone_can_view_single_event_with_tickets(): void
    {
        $event = Event::factory()->create(['created_by' => $this->organizer->id]);
        Ticket::factory()->create(['event_id' => $event->id, 'type' => 'VIP']);

        $response = $this->getJson("/api/events/{$event->id}");

        $response->assertOk()
            ->assertJsonStructure(['id', 'title', 'tickets']);
    }

    // ─── Create Event (organizer) ─────────────────────────────────────────────

    public function test_organizer_can_create_event(): void
    {
        $response = $this->actingAs($this->organizer, 'sanctum')
            ->postJson('/api/events', [
                'title' => 'New Concert',
                'description' => 'An amazing show',
                'date' => '2026-12-01 18:00:00',
                'location' => 'Delhi, India',
            ]);

        $response->assertStatus(201)
            ->assertJson(['message' => 'Event created successfully.']);

        $this->assertDatabaseHas('events', [
            'title' => 'New Concert',
            'created_by' => $this->organizer->id,
        ]);
    }

    public function test_customer_cannot_create_event(): void
    {
        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/events', [
                'title' => 'Hacked Event',
                'date' => '2026-12-01 18:00:00',
                'location' => 'Nowhere',
            ]);

        $response->assertStatus(403);
    }

    public function test_unauthenticated_cannot_create_event(): void
    {
        $response = $this->postJson('/api/events', [
            'title' => 'Anon Event',
            'date' => '2026-12-01 18:00:00',
            'location' => 'Nowhere',
        ]);

        $response->assertStatus(401);
    }

    // ─── Update Event ─────────────────────────────────────────────────────────

    public function test_organizer_can_update_own_event(): void
    {
        $event = Event::factory()->create(['created_by' => $this->organizer->id]);

        $response = $this->actingAs($this->organizer, 'sanctum')
            ->putJson("/api/events/{$event->id}", [
                'title' => 'Updated Title',
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('events', ['id' => $event->id, 'title' => 'Updated Title']);
    }

    public function test_organizer_cannot_update_others_event(): void
    {
        $otherOrganizer = User::factory()->organizer()->create();
        $otherOrganizer->assignRole('organizer');
        $event = Event::factory()->create(['created_by' => $otherOrganizer->id]);

        $response = $this->actingAs($this->organizer, 'sanctum')
            ->putJson("/api/events/{$event->id}", [
                'title' => 'Hijacked',
            ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_update_any_event(): void
    {
        $event = Event::factory()->create(['created_by' => $this->organizer->id]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/events/{$event->id}", [
                'title' => 'Admin Updated',
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('events', ['id' => $event->id, 'title' => 'Admin Updated']);
    }

    // ─── Delete Event ─────────────────────────────────────────────────────────

    public function test_organizer_can_delete_own_event(): void
    {
        $event = Event::factory()->create(['created_by' => $this->organizer->id]);

        $response = $this->actingAs($this->organizer, 'sanctum')
            ->deleteJson("/api/events/{$event->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('events', ['id' => $event->id]);
    }

    public function test_organizer_cannot_delete_others_event(): void
    {
        $otherOrganizer = User::factory()->organizer()->create();
        $otherOrganizer->assignRole('organizer');
        $event = Event::factory()->create(['created_by' => $otherOrganizer->id]);

        $response = $this->actingAs($this->organizer, 'sanctum')
            ->deleteJson("/api/events/{$event->id}");

        $response->assertStatus(403);
    }

    public function test_event_creation_validates_required_fields(): void
    {
        $response = $this->actingAs($this->organizer, 'sanctum')
            ->postJson('/api/events', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'date', 'location']);
    }
}
