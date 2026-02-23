<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    /**
     * List available tickets for an event.
     */
    public function index(Event $event): JsonResponse
    {
        return response()->json($event->tickets);
    }

    /**
     * View ticket details.
     */
    public function show(Event $event, Ticket $ticket): JsonResponse
    {
        return response()->json($ticket);
    }
}
