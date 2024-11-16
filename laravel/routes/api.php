<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
|
*/

// Health check route - publicly accessible
Route::get('/health', [HealthController::class, 'check']);

// Auth routes
Route::group([
    'middleware' => ['api', 'doNotCacheResponse'],
    'prefix' => 'auth'
], function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::get('me', [AuthController::class, 'me']);
    
    // Email verification routes
    Route::post('email/verify', [AuthController::class, 'verifyEmail'])
        ->middleware(['throttle:6,1'])
        ->name('verification.verify');
    
    Route::post('email/verify/resend', [AuthController::class, 'sendVerificationEmail'])
        ->middleware(['throttle:6,1'])
        ->name('verification.send');
});

// Game routes - protected by auth and email verification
Route::middleware(['auth:api', 'verified'])->group(function () {
    Route::get('/games', [GameController::class, 'index']);
    Route::get('/games/{game}', [GameController::class, 'show']);
    
    // Game participation
    Route::middleware('doNotCacheResponse')->group(function () {
        Route::post('/games', [GameController::class, 'create']);
        Route::post('/games/{game}/invite', [GameController::class, 'invite']);
        Route::post('/games/{game}/join', [GameController::class, 'join']);
        Route::post('/games/{game}/start', [GameController::class, 'start']);
        Route::post('/games/{game}/invite-link', [GameController::class, 'createInviteLink']);
        Route::post('/join-game/{token}', [GameController::class, 'joinViaToken']);
    });
});
