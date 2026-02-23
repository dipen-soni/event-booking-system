<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    /**
     * GET /api/bookings
     * List the authenticated customer's bookings.
     */
    public function index(Request $request): JsonResponse
    {
        $bookings = $request->user()
            ->bookings()
            ->with('ticket.event', 'payment')
            ->latest()
            ->paginate(15);

        return response()->json($bookings);
    }

    /**
     * POST /api/tickets/{ticket}/bookings  (customer)
     * Book tickets — creates a new booking with availability check.
     */
    public function store(Request $request, Ticket $ticket): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        // Check ticket availability
        $bookedQty = Booking::where('ticket_id', $ticket->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->sum('quantity');

        $available = $ticket->quantity - $bookedQty;

        if ($validated['quantity'] > $available) {
            return response()->json([
                'message' => "Only {$available} ticket(s) available.",
            ], 422);
        }

        $booking = Booking::create([
            'user_id' => $request->user()->id,
            'ticket_id' => $ticket->id,
            'quantity' => $validated['quantity'],
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Booking created successfully.',
            'booking' => $booking->load('ticket.event'),
        ], 201);
    }

    /**
     * PUT /api/bookings/{booking}/cancel (customer — own booking)
     */
    public function cancel(Request $request, Booking $booking): JsonResponse
    {
        if ($booking->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if ($booking->status === 'cancelled') {
            return response()->json(['message' => 'Booking is already cancelled.'], 422);
        }

        $booking->update(['status' => 'cancelled']);

        return response()->json([
            'message' => 'Booking cancelled successfully.',
            'booking' => $booking->fresh(),
        ]);
    }
}
