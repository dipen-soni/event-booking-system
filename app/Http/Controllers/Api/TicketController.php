<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    /**
     * POST /api/events/{event}/tickets  (organizer only — own events)
     */
    public function store(Request $request, Event $event): JsonResponse
    {
        if (!$request->user()->hasRole('admin') && $event->created_by !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $validated = $request->validate([
            'type' => ['required', 'in:VIP,Standard,Economy'],
            'price' => ['required', 'numeric', 'min:0'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $ticket = $event->tickets()->create($validated);

        return response()->json([
            'message' => 'Ticket created successfully.',
            'ticket' => $ticket,
        ], 201);
    }

    /**
     * PUT /api/tickets/{ticket}  (organizer only — own events)
     */
    public function update(Request $request, Ticket $ticket): JsonResponse
    {
        $ticket->load('event');

        if (!$request->user()->hasRole('admin') && $ticket->event->created_by !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $validated = $request->validate([
            'type' => ['sometimes', 'in:VIP,Standard,Economy'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'quantity' => ['sometimes', 'integer', 'min:1'],
        ]);

        $ticket->update($validated);

        return response()->json([
            'message' => 'Ticket updated successfully.',
            'ticket' => $ticket->fresh(),
        ]);
    }

    /**
     * DELETE /api/tickets/{ticket}  (organizer only — own events)
     */
    public function destroy(Request $request, Ticket $ticket): JsonResponse
    {
        $ticket->load('event');

        if (!$request->user()->hasRole('admin') && $ticket->event->created_by !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $ticket->delete();

        return response()->json(['message' => 'Ticket deleted successfully.']);
    }
}
