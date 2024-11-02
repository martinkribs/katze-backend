<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\ActionController;

// Public Authentication Routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware('guest')
        ->name('register');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('guest')
        ->name('login');
});

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {

    // Email verification routes
    Route::get('/verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['throttle:6,1'])
        ->name('verification.verify');

    Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware(['throttle:6,1'])
        ->name('verification.send');

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');

    Route::middleware('verified')->group(function () {
        // User Info
        Route::get('/user', function (Request $request) {
            return $request->user();
        });

        // Game Routes
        Route::prefix('games')->group(function () {
            Route::post('/', [GameController::class, 'create']);
            Route::post('/{game}/start', [GameController::class, 'start']);
            Route::get('/{game}/state', [GameController::class, 'getCurrentState']);
            Route::post('/{game}/actions', [ActionController::class, 'perform']);
        });
    });
});
