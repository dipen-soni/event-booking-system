<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use App\Notifications\BookingConfirmedNotification;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    /**
     * Simulate a payment for a booking.
     *
     * The mock processor randomly succeeds (~80%) or fails (~20%).
     * On success, confirms the booking and queues a notification.
     *
     * @param  Booking  $booking  The booking being paid for
     * @param  int      $userId   The ID of the user making the payment
     * @return array{success: bool, payment: Payment, message: string}
     */
    public function processPayment(Booking $booking, int $userId): array
    {
        $booking->load('ticket');
        $amount = $booking->ticket->price * $booking->quantity;

        // ── Simulate payment gateway (80% success rate) ─────────────────────
        $isSuccess = rand(1, 100) <= 80;
        $status = $isSuccess ? 'success' : 'failed';

        Log::info("PaymentService: Processing payment for Booking #{$booking->id}", [
            'user_id' => $userId,
            'amount' => $amount,
            'status' => $status,
        ]);

        // ── Create payment record ───────────────────────────────────────────
        $payment = Payment::create([
            'booking_id' => $booking->id,
            'user_id' => $userId,
            'amount' => $amount,
            'status' => $status,
        ]);

        // ── Update booking status based on payment result ───────────────────
        if ($isSuccess) {
            $booking->update(['status' => 'confirmed']);
            $message = 'Payment processed successfully. Booking confirmed.';

            // ── Queue notification to customer ──────────────────────────────
            $booking->load('user');
            $booking->user->notify(new BookingConfirmedNotification($booking));

            Log::info("PaymentService: BookingConfirmedNotification queued for User #{$userId}");
        } else {
            // Booking stays pending on failure — user can retry
            $message = 'Payment failed. Please try again.';
        }

        return [
            'success' => $isSuccess,
            'payment' => $payment,
            'message' => $message,
        ];
    }

    /**
     * Process a refund for a payment.
     *
     * @param  Payment  $payment
     * @return array{success: bool, payment: Payment, message: string}
     */
    public function processRefund(Payment $payment): array
    {
        Log::info("PaymentService: Processing refund for Payment #{$payment->id}");

        $payment->update(['status' => 'refunded']);
        $payment->booking->update(['status' => 'cancelled']);

        return [
            'success' => true,
            'payment' => $payment->fresh(),
            'message' => 'Refund processed successfully.',
        ];
    }
}
