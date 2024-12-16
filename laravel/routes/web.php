<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return response()->json(['status' => 'Ok']);
});

Route::get('/game-invite', function () {
    $token = request()->query('token');
    if (!$token) {
        return response()->json([
            'status' => 'error',
            'message' => 'Token is required',
            'playStoreUrl' => 'https://play.google.com/store/apps/details?id=app.katze.game'
        ], 400);
    }

    return response()->json([
        'status' => 'success',
        'data' => [
            'appScheme' => 'katze://game-invite?token=' . $token,
            'playStoreUrl' => 'https://play.google.com/store/apps/details?id=app.katze.game'
        ]
    ]);
});
