<?php

use App\Http\Controllers\Api\ApiAuthenticatedSessionController;
use App\Http\Controllers\Api\ApiEmailVerificationNotificationController;
use App\Http\Controllers\Api\ApiPasswordResetLinkController;
use App\Http\Controllers\Api\ApiRegisteredUserController;
use App\Http\Controllers\Api\GameController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


// Auth routes
Route::post('/register', [ApiRegisteredUserController::class, 'store'])
    ->middleware('guest')
    ->name('api.register');

Route::post('/login', [ApiAuthenticatedSessionController::class, 'store'])
    ->middleware('guest')
    ->name('api.login');

Route::post('/forgot-password', [ApiPasswordResetLinkController::class, 'store'])
    ->middleware('guest')
    ->name('api.password.email');

Route::post('/email/verification-notification', [ApiEmailVerificationNotificationController::class, 'store'])
    ->middleware(['auth:sanctum', 'throttle:6,1'])
    ->name('api.verification.send');

// Game routes - protected by auth and email verification
Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    // User Info
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Game management
    Route::get('/games', [GameController::class, 'index']);
    Route::post('/games', [GameController::class, 'create']);
    Route::get('/games/{game}', [GameController::class, 'show']);
    
    // Game participation
    Route::post('/games/{game}/invite', [GameController::class, 'invite']);
    Route::post('/games/{game}/join', [GameController::class, 'join']);
    Route::post('/games/{game}/start', [GameController::class, 'start']);
});

Route::post('/logout', [ApiAuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth:sanctum')
    ->name('api.logout');
