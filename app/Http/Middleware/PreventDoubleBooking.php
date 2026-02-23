<?php

namespace App\Http\Middleware;

use App\Models\Booking;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventDoubleBooking
{
    /**
     * Prevent a user from making duplicate active bookings for the same ticket.
     *
     * Applied on: POST /api/tickets/{ticket}/bookings
     * An active booking = status is 'pending' or 'confirmed'.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $ticket = $request->route('ticket');  // resolved via route model binding

        if ($user && $ticket) {
            $existingBooking = Booking::where('user_id', $user->id)
                ->where('ticket_id', $ticket->id)
                ->whereIn('status', ['pending', 'confirmed'])
                ->first();

            if ($existingBooking) {
                return response()->json([
                    'message' => 'You already have an active booking for this ticket.',
                    'existing_booking' => $existingBooking->only('id', 'quantity', 'status', 'created_at'),
                ], 409);   // 409 Conflict
            }
        }

        return $next($request);
    }
}
