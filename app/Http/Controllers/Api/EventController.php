<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class EventController extends Controller
{
    /**
     * GET /api/events
     * Public listing with pagination, search, and filter by date/location.
     * Results are cached for 10 minutes when no filters/search are applied.
     *
     * Query params:
     *   ?search=concert          — search in title & description
     *   ?location=delhi          — filter by location (partial match)
     *   ?date_from=2026-03-01    — events from this date
     *   ?date_to=2026-06-01      — events up to this date
     *   ?per_page=10             — results per page (default 15)
     */
    public function index(Request $request): JsonResponse
    {
        $search = $request->query('search');
        $location = $request->query('location');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        $perPage = min((int) $request->query('per_page', 15), 100);
        $page = (int) $request->query('page', 1);

        // ── Build a cache key based on query params ─────────────────────────
        $cacheKey = 'events_list_' . md5(serialize([
            $search,
            $location,
            $dateFrom,
            $dateTo,
            $perPage,
            $page,
        ]));

        // ── Cache for 10 minutes ────────────────────────────────────────────
        $events = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($search, $location, $dateFrom, $dateTo, $perPage) {
            $query = Event::with('organizer:id,name', 'tickets:id,event_id,type,price,quantity');

            // Search by title/description (uses CommonQueryScopes trait)
            if ($search) {
                $query->searchByTitle($search);
            }

            // Filter by location
            if ($location) {
                $query->where('location', 'like', "%{$location}%");
            }

            // Filter by date range (uses CommonQueryScopes trait)
            $query->filterByDate($dateFrom, $dateTo);

            $query->orderBy('date');

            return $query->paginate($perPage);
        });

        return response()->json($events);
    }

    /**
     * GET /api/events/{event}
     * Show event details with tickets. Cached for 10 minutes.
     */
    public function show(Event $event): JsonResponse
    {
        $cached = Cache::remember("event_{$event->id}", now()->addMinutes(10), function () use ($event) {
            return $event->load('organizer:id,name', 'tickets');
        });

        return response()->json($cached);
    }

    /**
     * POST /api/events  (organizer only)
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

        // Clear events list cache
        $this->clearEventsCache();

        return response()->json([
            'message' => 'Event created successfully.',
            'event' => $event,
        ], 201);
    }

    /**
     * PUT /api/events/{event}  (organizer — own events only)
     */
    public function update(Request $request, Event $event): JsonResponse
    {
        if (!$request->user()->hasRole('admin') && $event->created_by !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'date' => ['sometimes', 'date', 'after:now'],
            'location' => ['sometimes', 'string', 'max:255'],
        ]);

        $event->update($validated);

        // Clear related caches
        $this->clearEventsCache();
        Cache::forget("event_{$event->id}");

        return response()->json([
            'message' => 'Event updated successfully.',
            'event' => $event->fresh(),
        ]);
    }

    /**
     * DELETE /api/events/{event}  (organizer — own events only)
     */
    public function destroy(Request $request, Event $event): JsonResponse
    {
        if (!$request->user()->hasRole('admin') && $event->created_by !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $eventId = $event->id;
        $event->delete();

        // Clear related caches
        $this->clearEventsCache();
        Cache::forget("event_{$eventId}");

        return response()->json(['message' => 'Event deleted successfully.']);
    }

    /**
     * Flush all cached events list pages.
     * Uses a tagged approach: stores known cache keys under a tracking key.
     */
    private function clearEventsCache(): void
    {
        // Flush all keys that start with 'events_list_'
        // Since database cache doesn't support tags, we use a simple pattern.
        // For production, use Redis with Cache::tags(['events'])->flush().
        Cache::flush();  // Simple approach — clears all cache
    }
}
