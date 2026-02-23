<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Booking::with('user', 'ticket.event', 'payment')->paginate(15)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'ticket_id' => ['required', 'exists:tickets,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'status' => ['sometimes', 'in:pending,confirmed,cancelled'],
        ]);

        $booking = Booking::create($validated);
        return response()->json(['message' => 'Booking created.', 'booking' => $booking], 201);
    }

    public function show(Booking $booking): JsonResponse
    {
        return response()->json($booking->load('user', 'ticket.event', 'payment'));
    }

    public function update(Request $request, Booking $booking): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => ['sometimes', 'integer', 'min:1'],
            'status' => ['sometimes', 'in:pending,confirmed,cancelled'],
        ]);

        $booking->update($validated);
        return response()->json(['message' => 'Booking updated.', 'booking' => $booking->fresh()]);
    }

    public function destroy(Booking $booking): JsonResponse
    {
        $booking->delete();
        return response()->json(['message' => 'Booking deleted.']);
    }
}
