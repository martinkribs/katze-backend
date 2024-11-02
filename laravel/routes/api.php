<?php

use App\Http\Controllers\Api\ApiEmailVerificationNotificationController;
use App\Http\Controllers\Api\ApiPasswordResetLinkController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ApiRegisteredUserController;
use App\Http\Controllers\Api\ApiAuthenticatedSessionController;

// Public Authentication Routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [ApiRegisteredUserController::class, 'store'])
        ->middleware('guest');
    Route::post('/login', [ApiAuthenticatedSessionController::class, 'store'])
        ->middleware('guest');
    Route::post('/forgot-password', [ApiPasswordResetLinkController::class, 'store'])
        ->middleware('guest');
});

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {

    Route::post('email/verification-notification', [ApiEmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1');
    Route::post('/logout', [ApiAuthenticatedSessionController::class, 'destroy']);

    Route::middleware('verified-api')->group(function () {
        // User Info
        Route::get('/user', function (Request $request) {
            return $request->user();
        });
    });
});
