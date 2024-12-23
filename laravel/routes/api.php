<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\TranslationController;
use App\Http\Controllers\MessageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
|
*/

Route::group(['middleware' => ['api']], function () {
    // Health check route - publicly accessible
    Route::get('/health', [HealthController::class, 'check']);

    // Translation routes - publicly accessible
    Route::get('/translations/{lang}', [TranslationController::class, 'get']);
    Route::get('/translations/{lang}/{namespace}', [TranslationController::class, 'get']);
});

// Auth routes
Route::group([
    'middleware' => ['api', 'doNotCacheResponse']
], function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('token/refresh', [AuthController::class, 'refresh']);
    Route::delete('user', [AuthController::class, 'delete']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
    Route::post('verify-otp', [AuthController::class, 'verifyOtp']);

    // Email verification routes
    Route::post('email/verify', [AuthController::class, 'verifyEmail'])
        ->middleware(['throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verify/resend', [AuthController::class, 'sendVerificationEmail'])
        ->middleware(['throttle:6,1'])
        ->name('verification.send');
});

// Game routes - protected by auth and email verification
Route::middleware(['api', 'verified'])->group(function () {

    Route::get('user', [AuthController::class, 'user']);

    // Role routes
    Route::get('/roles', [RoleController::class, 'index']);
    Route::get('/roles/{role}/action-types', [RoleController::class, 'getActionTypes']);

    // Game routes
    Route::get('/games', [GameController::class, 'index']);
    Route::get('/games/{game}', [GameController::class, 'show']);
    Route::get('/games/{game}/settings', [GameController::class, 'getSettings']);

    // Game participation
    Route::middleware('doNotCacheResponse')->group(function () {
        Route::post('/games', [GameController::class, 'create']);
        Route::post('/games/{game}/invite', [GameController::class, 'invite']);
        Route::post('/games/{game}/join', [GameController::class, 'join']);
        Route::post('/games/{game}/leave', [GameController::class, 'leave']);
        Route::post('/games/{game}/kick/{player}', [GameController::class, 'kick']);
        Route::put('/games/{game}/settings', [GameController::class, 'updateSettings']);
        Route::post('/games/{game}/start', [GameController::class, 'start']);
        Route::post('/games/{game}/invite-link', [GameController::class, 'createInviteLink']);
        Route::post('/join-game/{token}', [GameController::class, 'joinViaToken']);
        Route::delete('/games/{game}', [GameController::class, 'delete']);
        Route::post('/games/{game}/actions', [GameController::class, 'performAction']);
        Route::get('/games/{game}/messages', [MessageController::class, 'index']);
    });
});
