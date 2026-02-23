<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Payment::with('booking.user', 'booking.ticket.event')->paginate(15)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'booking_id' => ['required', 'exists:bookings,id', 'unique:payments,booking_id'],
            'user_id' => ['required', 'exists:users,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'status' => ['sometimes', 'in:success,failed,refunded'],
        ]);

        $payment = Payment::create($validated);
        return response()->json(['message' => 'Payment created.', 'payment' => $payment], 201);
    }

    public function show(Payment $payment): JsonResponse
    {
        return response()->json($payment->load('booking.user', 'booking.ticket.event'));
    }

    public function update(Request $request, Payment $payment): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:success,failed,refunded'],
        ]);

        $payment->update($validated);
        return response()->json(['message' => 'Payment updated.', 'payment' => $payment->fresh()]);
    }

    public function destroy(Payment $payment): JsonResponse
    {
        $payment->delete();
        return response()->json(['message' => 'Payment deleted.']);
    }
}
