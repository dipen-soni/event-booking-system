<?php

namespace Tests\Unit;

use App\Models\Booking;
use App\Models\Event;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\BookingConfirmedNotification;
use App\Services\PaymentService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PaymentService $paymentService;
    protected User $customer;
    protected Booking $booking;
    protected Ticket $ticket;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->paymentService = new PaymentService();

        $organizer = User::factory()->organizer()->create();
        $organizer->assignRole('organizer');

        $this->customer = User::factory()->customer()->create();
        $this->customer->assignRole('customer');

        $event = Event::factory()->create(['created_by' => $organizer->id]);

        $this->ticket = Ticket::factory()->create([
            'event_id' => $event->id,
            'type' => 'VIP',
            'price' => 200.00,
            'quantity' => 100,
        ]);

        $this->booking = Booking::create([
            'user_id' => $this->customer->id,
            'ticket_id' => $this->ticket->id,
            'quantity' => 3,
            'status' => 'pending',
        ]);
    }

    // ─── processPayment() ─────────────────────────────────────────────────────

    public function test_process_payment_returns_correct_structure(): void
    {
        Notification::fake();

        $result = $this->paymentService->processPayment($this->booking, $this->customer->id);

        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('payment', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertInstanceOf(Payment::class, $result['payment']);
    }

    public function test_process_payment_calculates_correct_amount(): void
    {
        Notification::fake();

        $result = $this->paymentService->processPayment($this->booking, $this->customer->id);

        $expectedAmount = 200.00 * 3; // price × quantity = 600

        $this->assertEquals(
            number_format($expectedAmount, 2),
            number_format($result['payment']->amount, 2)
        );
    }

    public function test_process_payment_creates_payment_record(): void
    {
        Notification::fake();

        $this->paymentService->processPayment($this->booking, $this->customer->id);

        $this->assertDatabaseHas('payments', [
            'booking_id' => $this->booking->id,
            'user_id' => $this->customer->id,
        ]);
    }

    public function test_successful_payment_confirms_booking(): void
    {
        Notification::fake();

        // Run multiple times to get at least one success (80% rate)
        $confirmed = false;
        for ($i = 0; $i < 20; $i++) {
            // Reset booking for each attempt
            $booking = Booking::create([
                'user_id' => $this->customer->id,
                'ticket_id' => $this->ticket->id,
                'quantity' => 1,
                'status' => 'pending',
            ]);

            $result = $this->paymentService->processPayment($booking, $this->customer->id);

            if ($result['success']) {
                $this->assertEquals('confirmed', $booking->fresh()->status);
                $confirmed = true;
                break;
            }
        }

        $this->assertTrue($confirmed, 'Payment never succeeded in 20 attempts');
    }

    public function test_failed_payment_keeps_booking_pending(): void
    {
        Notification::fake();

        // Run multiple times to get at least one failure (20% rate)
        $foundFailure = false;
        for ($i = 0; $i < 50; $i++) {
            $booking = Booking::create([
                'user_id' => $this->customer->id,
                'ticket_id' => $this->ticket->id,
                'quantity' => 1,
                'status' => 'pending',
            ]);

            $result = $this->paymentService->processPayment($booking, $this->customer->id);

            if (!$result['success']) {
                $this->assertEquals('pending', $booking->fresh()->status);
                $this->assertEquals('failed', $result['payment']->status);
                $foundFailure = true;
                break;
            }
        }

        $this->assertTrue($foundFailure, 'Payment never failed in 50 attempts');
    }

    public function test_successful_payment_queues_notification(): void
    {
        Notification::fake();

        // Run until success
        for ($i = 0; $i < 20; $i++) {
            $booking = Booking::create([
                'user_id' => $this->customer->id,
                'ticket_id' => $this->ticket->id,
                'quantity' => 1,
                'status' => 'pending',
            ]);

            $result = $this->paymentService->processPayment($booking, $this->customer->id);

            if ($result['success']) {
                Notification::assertSentTo(
                    $this->customer,
                    BookingConfirmedNotification::class
                );
                return;
            }
        }

        $this->fail('Payment never succeeded — could not test notification');
    }

    public function test_payment_status_is_success_or_failed(): void
    {
        Notification::fake();

        $result = $this->paymentService->processPayment($this->booking, $this->customer->id);

        $this->assertContains($result['payment']->status, ['success', 'failed']);
    }

    // ─── processRefund() ──────────────────────────────────────────────────────

    public function test_process_refund_marks_payment_refunded(): void
    {
        $payment = Payment::create([
            'booking_id' => $this->booking->id,
            'user_id' => $this->customer->id,
            'amount' => 600.00,
            'status' => 'success',
        ]);

        $result = $this->paymentService->processRefund($payment);

        $this->assertTrue($result['success']);
        $this->assertEquals('refunded', $result['payment']->status);
    }

    public function test_process_refund_cancels_booking(): void
    {
        $this->booking->update(['status' => 'confirmed']);

        $payment = Payment::create([
            'booking_id' => $this->booking->id,
            'user_id' => $this->customer->id,
            'amount' => 600.00,
            'status' => 'success',
        ]);

        $this->paymentService->processRefund($payment);

        $this->assertEquals('cancelled', $this->booking->fresh()->status);
    }

    public function test_process_refund_returns_correct_message(): void
    {
        $payment = Payment::create([
            'booking_id' => $this->booking->id,
            'user_id' => $this->customer->id,
            'amount' => 600.00,
            'status' => 'success',
        ]);

        $result = $this->paymentService->processRefund($payment);

        $this->assertEquals('Refund processed successfully.', $result['message']);
    }
}
