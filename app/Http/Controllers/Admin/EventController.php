<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Event::with('organizer', 'tickets')->paginate(15));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'date' => ['required', 'date', 'after:now'],
            'location' => ['required', 'string', 'max:255'],
            'created_by' => ['required', 'exists:users,id'],
        ]);

        $event = Event::create($validated);
        return response()->json(['message' => 'Event created.', 'event' => $event], 201);
    }

    public function show(Event $event): JsonResponse
    {
        return response()->json($event->load('organizer', 'tickets', 'tickets.bookings'));
    }

    public function update(Request $request, Event $event): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'date' => ['sometimes', 'date', 'after:now'],
            'location' => ['sometimes', 'string', 'max:255'],
            'created_by' => ['sometimes', 'exists:users,id'],
        ]);

        $event->update($validated);
        return response()->json(['message' => 'Event updated.', 'event' => $event->fresh()]);
    }

    public function destroy(Event $event): JsonResponse
    {
        $event->delete();
        return response()->json(['message' => 'Event deleted.']);
    }
}
