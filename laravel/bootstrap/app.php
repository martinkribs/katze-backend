<?php

use App\Http\Middleware\EnsureEmailIsVerified;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Spatie\ResponseCache\Middlewares\CacheResponse;
use Spatie\ResponseCache\Middlewares\DoNotCacheResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

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
        $exceptions->renderable(function (\Throwable $e) {
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                return new JsonResponse([
                    'error' => 'Not Found',
                ], Response::HTTP_NOT_FOUND);
            }

            $status = $e instanceof \Illuminate\Http\Exceptions\HttpResponseException
                ? $e->getResponse()->getStatusCode()
                : ($e->getCode() >= 100 && $e->getCode() < 600 ? $e->getCode() : 500);

            return new JsonResponse([
                'error' => $e->getMessage() ?: 'Server Error',
            ], $status);
        });
    })->create();
