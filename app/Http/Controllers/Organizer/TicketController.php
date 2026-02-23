<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    /**
     * List tickets for an organizer's event.
     */
    public function index(Request $request, Event $event): JsonResponse
    {
        if ($event->created_by !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return response()->json($event->tickets()->paginate(15));
    }

    /**
     * Create a ticket for an organizer's event.
     */
    public function store(Request $request, Event $event): JsonResponse
    {
        if ($event->created_by !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $validated = $request->validate([
            'type' => ['required', 'in:VIP,Standard,Economy'],
            'price' => ['required', 'numeric', 'min:0'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $ticket = $event->tickets()->create($validated);

        return response()->json(['message' => 'Ticket created.', 'ticket' => $ticket], 201);
    }

    /**
     * Show a specific ticket (only if event is owned by organizer).
     */
    public function show(Request $request, Event $event, Ticket $ticket): JsonResponse
    {
        if ($event->created_by !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return response()->json($ticket->load('bookings'));
    }

    /**
     * Update a ticket (only if event is owned by organizer).
     */
    public function update(Request $request, Event $event, Ticket $ticket): JsonResponse
    {
        if ($event->created_by !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $validated = $request->validate([
            'type' => ['sometimes', 'in:VIP,Standard,Economy'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'quantity' => ['sometimes', 'integer', 'min:1'],
        ]);

        $ticket->update($validated);

        return response()->json(['message' => 'Ticket updated.', 'ticket' => $ticket->fresh()]);
    }

    /**
     * Delete a ticket (only if event is owned by organizer).
     */
    public function destroy(Request $request, Event $event, Ticket $ticket): JsonResponse
    {
        if ($event->created_by !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $ticket->delete();

        return response()->json(['message' => 'Ticket deleted.']);
    }
}
