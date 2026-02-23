<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\PaymentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Section 3 — Flat API Routes
|--------------------------------------------------------------------------
|
| POST   /api/register
| POST   /api/login
| POST   /api/logout
| GET    /api/me
|
| GET    /api/events                         (pagination, search, filter)
| GET    /api/events/{id}                    (with tickets)
| POST   /api/events                        (organizer only)
| PUT    /api/events/{id}                   (organizer only)
| DELETE /api/events/{id}                   (organizer only)
|
| POST   /api/events/{event_id}/tickets     (organizer only)
| PUT    /api/tickets/{id}                  (organizer only)
| DELETE /api/tickets/{id}                  (organizer only)
|
| POST   /api/tickets/{id}/bookings         (customer)
| GET    /api/bookings                       (customer's bookings)
| PUT    /api/bookings/{id}/cancel           (customer)
|
| POST   /api/bookings/{id}/payment          (mock payment)
| GET    /api/payments/{id}
|
*/

// ─── Public (no auth) ─────────────────────────────────────────────────────────

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// ─── Public event browsing (no auth required) ─────────────────────────────────

Route::get('/events', [EventController::class, 'index']);
Route::get('/events/{event}', [EventController::class, 'show']);

// ─── Authenticated ────────────────────────────────────────────────────────────

Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // ── Events (organizer / admin) ────────────────────────────────────────
    Route::post('/events', [EventController::class, 'store'])
        ->middleware('role:organizer,admin');
    Route::put('/events/{event}', [EventController::class, 'update'])
        ->middleware('role:organizer,admin');
    Route::delete('/events/{event}', [EventController::class, 'destroy'])
        ->middleware('role:organizer,admin');

    // ── Tickets (organizer / admin) ───────────────────────────────────────
    Route::post('/events/{event}/tickets', [TicketController::class, 'store'])
        ->middleware('role:organizer,admin');
    Route::put('/tickets/{ticket}', [TicketController::class, 'update'])
        ->middleware('role:organizer,admin');
    Route::delete('/tickets/{ticket}', [TicketController::class, 'destroy'])
        ->middleware('role:organizer,admin');

    // ── Bookings (customer) ──────────────────────────────────────────────
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::post('/tickets/{ticket}/bookings', [BookingController::class, 'store'])
        ->middleware('prevent.double.booking');
    Route::put('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);

    // ── Payments ─────────────────────────────────────────────────────────
    Route::post('/bookings/{booking}/payment', [PaymentController::class, 'store']);
    Route::get('/payments/{payment}', [PaymentController::class, 'show']);
});

// ─── Admin panel routes (role-prefixed, kept for admin management) ────────────

Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::apiResource('users', \App\Http\Controllers\Admin\UserController::class);
    Route::apiResource('events', \App\Http\Controllers\Admin\EventController::class);
    Route::apiResource('events.tickets', \App\Http\Controllers\Admin\TicketController::class);
    Route::apiResource('bookings', \App\Http\Controllers\Admin\BookingController::class);
    Route::apiResource('payments', \App\Http\Controllers\Admin\PaymentController::class);
});
