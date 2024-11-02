<?php

use App\Http\Middleware\Api\ApiEnsureEmailIsVerified;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use App\Http\Middleware\EnsureEmailIsVerified;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Laravel\Sanctum\Http\Middleware\AuthenticateSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Middleware for Web routes
        $middleware->group('web', [
            StartSession::class,                // Handle sessions
            AuthenticateSession::class,         // Required for Sanctum to authenticate sessions
            VerifyCsrfToken::class,             // Protect against CSRF
            SubstituteBindings::class,          // Enable route model binding
            ShareErrorsFromSession::class,      // Share error messages with views
            AddQueuedCookiesToResponse::class   // Handle cookies in responses
        ]);

        // Middleware for API routes
        $middleware->group('api', [
            ThrottleRequests::class.':api',
            SubstituteBindings::class
        ]);

        // Middleware aliases
        $middleware->alias([
            'verified' => EnsureEmailIsVerified::class
        ]);
        $middleware->alias([
            'verified-api' => ApiEnsureEmailIsVerified::class
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
