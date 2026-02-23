<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    /**
     * List only the authenticated organizer's events.
     */
    public function index(Request $request): JsonResponse
    {
        $events = Event::where('created_by', $request->user()->id)
            ->with('tickets')
            ->paginate(15);

        return response()->json($events);
    }

    /**
     * Create an event owned by the authenticated organizer.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'date' => ['required', 'date', 'after:now'],
            'location' => ['required', 'string', 'max:255'],
        ]);

        $validated['created_by'] = $request->user()->id;

        $event = Event::create($validated);

        return response()->json(['message' => 'Event created.', 'event' => $event], 201);
    }

    /**
     * Show a single event (only if owned by organizer).
     */
    public function show(Request $request, Event $event): JsonResponse
    {
        if ($event->created_by !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return response()->json($event->load('tickets', 'tickets.bookings'));
    }

    /**
     * Update an event (only if owned by organizer).
     */
    public function update(Request $request, Event $event): JsonResponse
    {
        if ($event->created_by !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'date' => ['sometimes', 'date', 'after:now'],
            'location' => ['sometimes', 'string', 'max:255'],
        ]);

        $event->update($validated);

        return response()->json(['message' => 'Event updated.', 'event' => $event->fresh()]);
    }

    /**
     * Delete an event (only if owned by organizer).
     */
    public function destroy(Request $request, Event $event): JsonResponse
    {
        if ($event->created_by !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $event->delete();

        return response()->json(['message' => 'Event deleted.']);
    }
}
