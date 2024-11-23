<?php

use App\Http\Middleware\EnsureEmailIsVerified;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Spatie\ResponseCache\Middlewares\CacheResponse;
use Spatie\ResponseCache\Middlewares\DoNotCacheResponse;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Middleware for API routes
        $middleware->group('api', [
            ThrottleRequests::class.':api',
            SubstituteBindings::class,
            CacheResponse::class
        ]);

        // Middleware for web routes
        $middleware->group('web', [
            SubstituteBindings::class,
        ]);

        // Middleware aliases
        $middleware->alias([
            'verified' => EnsureEmailIsVerified::class,
            'doNotCacheResponse' => DoNotCacheResponse::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
