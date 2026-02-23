<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',          // <-- register API routes
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Register the Sanctum stateful middleware for cookie-based sessions (optional for SPA)
        $middleware->statefulApi();

        // Register the custom role-checking middleware under the 'role' alias
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'prevent.double.booking' => \App\Http\Middleware\PreventDoubleBooking::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
