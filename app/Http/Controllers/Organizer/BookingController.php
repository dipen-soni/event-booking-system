<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    /**
     * List bookings for events owned by the authenticated organizer.
     */
    public function index(Request $request): JsonResponse
    {
        $bookings = Booking::whereHas('ticket.event', function ($query) use ($request) {
            $query->where('created_by', $request->user()->id);
        })->with('user', 'ticket.event', 'payment')->paginate(15);

        return response()->json($bookings);
    }

    /**
     * Show a booking (only if it belongs to one of the organizer's events).
     */
    public function show(Request $request, Booking $booking): JsonResponse
    {
        $booking->load('ticket.event');

        if ($booking->ticket->event->created_by !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return response()->json($booking->load('user', 'payment'));
    }
}
