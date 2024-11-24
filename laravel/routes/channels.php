<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Game;

Broadcast::channel('game.{gameId}', function ($user, $gameId) {
    // Check if user is a participant in the game
    return Game::query()
        ->where('id', $gameId)
        ->whereHas('users', function ($query) use ($user) {
            $query->where('users.id', $user->id);
        })
        ->exists();
});
