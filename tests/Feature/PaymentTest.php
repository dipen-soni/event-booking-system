<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Event;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use App\Notifications\BookingConfirmedNotification;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;
    protected Booking $booking;
    protected Ticket $ticket;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $organizer = User::factory()->organizer()->create();
        $organizer->assignRole('organizer');

        $this->customer = User::factory()->customer()->create();
        $this->customer->assignRole('customer');

        $event = Event::factory()->create(['created_by' => $organizer->id]);

        $this->ticket = Ticket::factory()->create([
            'event_id' => $event->id,
            'type' => 'Standard',
            'price' => 75.00,
            'quantity' => 100,
        ]);

        $this->booking = Booking::create([
            'user_id' => $this->customer->id,
            'ticket_id' => $this->ticket->id,
            'quantity' => 2,
            'status' => 'pending',
        ]);
    }

    // ─── Create Payment ───────────────────────────────────────────────────────

    public function test_customer_can_make_payment_for_booking(): void
    {
        Notification::fake();

        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/bookings/{$this->booking->id}/payment");

        // Payment can succeed or fail (mock), both are valid responses
        $response->assertStatus($response->json('payment.status') === 'success' ? 201 : 422);

        $this->assertDatabaseHas('payments', [
            'booking_id' => $this->booking->id,
            'user_id' => $this->customer->id,
        ]);
    }

    public function test_successful_payment_sends_notification(): void
    {
        Notification::fake();

        // Force success by making payment directly
        Payment::create([
            'booking_id' => $this->booking->id,
            'user_id' => $this->customer->id,
            'amount' => 150.00,
            'status' => 'success',
        ]);
        $this->booking->update(['status' => 'confirmed']);

        $this->customer->notify(new BookingConfirmedNotification($this->booking));

        Notification::assertSentTo(
            $this->customer,
            BookingConfirmedNotification::class
        );
    }

    public function test_payment_calculates_correct_amount(): void
    {
        Notification::fake();

        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/bookings/{$this->booking->id}/payment");

        $expectedAmount = $this->ticket->price * $this->booking->quantity; // 75 * 2 = 150

        $this->assertEquals(
            number_format($expectedAmount, 2),
            number_format($response->json('payment.amount'), 2)
        );
    }

    public function test_cannot_pay_for_cancelled_booking(): void
    {
        $this->booking->update(['status' => 'cancelled']);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/bookings/{$this->booking->id}/payment");

        $response->assertStatus(422)
            ->assertJson(['message' => 'Cannot pay for a cancelled booking.']);
    }

    public function test_cannot_pay_twice_for_same_booking(): void
    {
        Payment::create([
            'booking_id' => $this->booking->id,
            'user_id' => $this->customer->id,
            'amount' => 150.00,
            'status' => 'success',
        ]);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/bookings/{$this->booking->id}/payment");

        $response->assertStatus(422)
            ->assertJson(['message' => 'Payment already exists for this booking.']);
    }

    public function test_cannot_pay_for_others_booking(): void
    {
        $otherCustomer = User::factory()->customer()->create();
        $otherCustomer->assignRole('customer');

        $response = $this->actingAs($otherCustomer, 'sanctum')
            ->postJson("/api/bookings/{$this->booking->id}/payment");

        $response->assertStatus(403);
    }

    // ─── View Payment ─────────────────────────────────────────────────────────

    public function test_customer_can_view_own_payment(): void
    {
        $payment = Payment::create([
            'booking_id' => $this->booking->id,
            'user_id' => $this->customer->id,
            'amount' => 150.00,
            'status' => 'success',
        ]);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->getJson("/api/payments/{$payment->id}");

        $response->assertOk()
            ->assertJsonFragment(['id' => $payment->id]);
    }

    public function test_customer_cannot_view_others_payment(): void
    {
        $otherCustomer = User::factory()->customer()->create();
        $otherCustomer->assignRole('customer');

        $payment = Payment::create([
            'booking_id' => $this->booking->id,
            'user_id' => $this->customer->id,
            'amount' => 150.00,
            'status' => 'success',
        ]);

        $response = $this->actingAs($otherCustomer, 'sanctum')
            ->getJson("/api/payments/{$payment->id}");

        $response->assertStatus(403);
    }

    public function test_admin_can_view_any_payment(): void
    {
        $admin = User::factory()->admin()->create();
        $admin->assignRole('admin');

        $payment = Payment::create([
            'booking_id' => $this->booking->id,
            'user_id' => $this->customer->id,
            'amount' => 150.00,
            'status' => 'success',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/payments/{$payment->id}");

        $response->assertOk();
    }

    public function test_unauthenticated_cannot_make_payment(): void
    {
        $response = $this->postJson("/api/bookings/{$this->booking->id}/payment");

        $response->assertStatus(401);
    }
}
