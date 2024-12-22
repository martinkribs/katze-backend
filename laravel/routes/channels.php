<?php

use App\Events\MessageReceived;
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

// Handle incoming messages
Broadcast::event('client-message', function ($user, $data) {
    if (!isset($data['game_id'], $data['content'], $data['is_night_chat'])) {
        return false;
    }

    $game = Game::find($data['game_id']);
    if (!$game) {
        return false;
    }

    // Check if user is a participant in the game
    $isParticipant = $game->users()
        ->where('users.id', $user->id)
        ->exists();

    if (!$isParticipant) {
        return false;
    }

    // Dispatch the message received event
    event(new MessageReceived(
        game: $game,
        user: $user,
        content: $data['content'],
        isNightChat: (bool) $data['is_night_chat']
    ));

    return true;
});
