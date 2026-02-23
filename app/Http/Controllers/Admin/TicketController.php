<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index(Event $event): JsonResponse
    {
        return response()->json($event->tickets()->paginate(15));
    }

    public function store(Request $request, Event $event): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:VIP,Standard,Economy'],
            'price' => ['required', 'numeric', 'min:0'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $ticket = $event->tickets()->create($validated);
        return response()->json(['message' => 'Ticket created.', 'ticket' => $ticket], 201);
    }

    public function show(Event $event, Ticket $ticket): JsonResponse
    {
        return response()->json($ticket->load('bookings'));
    }

    public function update(Request $request, Event $event, Ticket $ticket): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['sometimes', 'in:VIP,Standard,Economy'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'quantity' => ['sometimes', 'integer', 'min:1'],
        ]);

        $ticket->update($validated);
        return response()->json(['message' => 'Ticket updated.', 'ticket' => $ticket->fresh()]);
    }

    public function destroy(Event $event, Ticket $ticket): JsonResponse
    {
        $ticket->delete();
        return response()->json(['message' => 'Ticket deleted.']);
    }
}
