<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    /**
     * Browse all upcoming events.
     */
    public function index(): JsonResponse
    {
        $events = Event::with('organizer:id,name', 'tickets:id,event_id,type,price,quantity')
            ->where('date', '>=', now())
            ->orderBy('date')
            ->paginate(15);

        return response()->json($events);
    }

    /**
     * View event details.
     */
    public function show(Event $event): JsonResponse
    {
        return response()->json($event->load('organizer:id,name', 'tickets'));
    }
}
