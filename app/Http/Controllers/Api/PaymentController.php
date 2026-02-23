<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {
    }

    /**
     * POST /api/bookings/{booking}/payment  (mock payment via PaymentService)
     */
    public function store(Request $request, Booking $booking): JsonResponse
    {
        // Only the booking owner can pay
        if ($booking->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        // Booking must not be cancelled
        if ($booking->status === 'cancelled') {
            return response()->json(['message' => 'Cannot pay for a cancelled booking.'], 422);
        }

        // Check if payment already exists
        if ($booking->payment()->exists()) {
            return response()->json(['message' => 'Payment already exists for this booking.'], 422);
        }

        // ── Delegate to PaymentService ──────────────────────────────────────
        $result = $this->paymentService->processPayment($booking, $request->user()->id);

        return response()->json([
            'message' => $result['message'],
            'payment' => $result['payment'],
            'booking' => $booking->fresh(),
        ], $result['success'] ? 201 : 422);
    }

    /**
     * GET /api/payments/{payment}
     * View a payment (owner only).
     */
    public function show(Request $request, Payment $payment): JsonResponse
    {
        if ($payment->user_id !== $request->user()->id && !$request->user()->hasRole('admin')) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return response()->json($payment->load('booking.ticket.event'));
    }
}
