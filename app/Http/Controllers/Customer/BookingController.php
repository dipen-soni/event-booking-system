<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    /**
     * List the authenticated customer's bookings.
     */
    public function index(Request $request): JsonResponse
    {
        $bookings = $request->user()
            ->bookings()
            ->with('ticket.event', 'payment')
            ->paginate(15);

        return response()->json($bookings);
    }

    /**
     * Book tickets (create a new booking).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ticket_id' => ['required', 'exists:tickets,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $ticket = Ticket::findOrFail($validated['ticket_id']);

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
     * Show a specific booking (only if owned by the customer).
     */
    public function show(Request $request, Booking $booking): JsonResponse
    {
        if ($booking->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return response()->json($booking->load('ticket.event', 'payment'));
    }

    /**
     * Cancel a booking (only if owned by customer and not already cancelled).
     */
    public function destroy(Request $request, Booking $booking): JsonResponse
    {
        if ($booking->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if ($booking->status === 'cancelled') {
            return response()->json(['message' => 'Booking is already cancelled.'], 422);
        }

        $booking->update(['status' => 'cancelled']);

        return response()->json(['message' => 'Booking cancelled successfully.']);
    }
}
