<?php

use App\Http\Middleware\Api\ApiEnsureEmailIsVerified;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Middleware for API routes
        $middleware->group('api', [
            ThrottleRequests::class.':api',
            SubstituteBindings::class
        ]);

        // Middleware aliases
        $middleware->alias([
            'verified-api' => ApiEnsureEmailIsVerified::class
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
