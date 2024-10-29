<?php

use App\Models\Game;
use App\Models\User;
use App\Models\Player;
use App\Models\Round;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->users = User::factory()->count(8)->create();
    $this->authenticatedUser = $this->users->first();
    $this->actingAs($this->authenticatedUser);
});

test('game can be created with valid players', function () {
    $response = $this->postJson('/api/games', [
        'player_ids' => $this->users->pluck('id')->toArray()
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['message', 'game_id']);

    $gameId = $response->json('game_id');
    $game = Game::find($gameId);

    expect($game->status)->toBe('waiting')
        ->and($game->start_time)->toBeNull()
        ->and($game->end_time)->toBeNull();

    // Verify role distribution
    $players = Player::where('game_id', $gameId)->get();
    $roleCounts = $players->groupBy('role')->map->count();

    expect($players)->toHaveCount(8)
        ->and($roleCounts['cat'])->toBe(2)
        ->and($roleCounts['seer'])->toBe(1)
        ->and($roleCounts['witch'])->toBe(1)
        ->and($roleCounts['detective'])->toBe(1)
        ->and($roleCounts['villager'])->toBe(3);
});

test('game creation fails with insufficient players', function () {
    $response = $this->postJson('/api/games', [
        'player_ids' => $this->users->take(5)->pluck('id')->toArray()
    ]);

    $response->assertStatus(422);
});

test('game can be started', function () {
    $game = Game::create(['status' => 'waiting']);
    
    foreach ($this->users as $user) {
        Player::create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'role' => 'villager',
            'is_alive' => true
        ]);
    }

    $response = $this->postJson("/api/games/{$game->id}/start");

    $response->assertStatus(200)
        ->assertJson(['message' => 'Game started successfully']);

    $game->refresh();
    expect($game->status)->toBe('in_progress')
        ->and($game->start_time)->not->toBeNull();

    $round = Round::where('game_id', $game->id)->first();
    expect($round)->not->toBeNull()
        ->and($round->round_number)->toBe(1);
});

test('started game cannot be started again', function () {
    $game = Game::create(['status' => 'in_progress']);

    $response = $this->postJson("/api/games/{$game->id}/start");

    $response->assertStatus(400)
        ->assertJson(['error' => 'Game cannot be started']);
});

test('current game state can be retrieved', function () {
    $game = Game::create(['status' => 'in_progress', 'start_time' => now()]);
    
    foreach ($this->users as $user) {
        Player::create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'role' => 'villager',
            'is_alive' => true
        ]);
    }

    Round::create([
        'game_id' => $game->id,
        'round_number' => 1,
        'start_time' => now(),
        'end_time' => now()->addHours(2),
        'is_day' => true
    ]);

    $response = $this->getJson("/api/games/{$game->id}/state");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'game_status',
            'current_round',
            'alive_players',
            'is_day',
            'time_until_next_phase'
        ]);

    expect($response->json('game_status'))->toBe('in_progress')
        ->and($response->json('is_day'))->toBeTrue()
        ->and($response->json('alive_players'))->toHaveCount(8);
});

test('new round is created when current round ends', function () {
    $game = Game::create(['status' => 'in_progress', 'start_time' => now()]);
    
    Round::create([
        'game_id' => $game->id,
        'round_number' => 1,
        'start_time' => now()->subHours(3),
        'end_time' => now()->subHour(),
        'is_day' => true
    ]);

    $this->getJson("/api/games/{$game->id}/state");

    $latestRound = Round::where('game_id', $game->id)
        ->orderBy('round_number', 'desc')
        ->first();

    expect($latestRound->round_number)->toBe(2)
        ->and($latestRound->end_time)->toBeGreaterThan(now());
});

test('day night cycle follows correct timing', function () {
    $game = Game::create(['status' => 'in_progress']);
    
    // Test morning transition (6 AM)
    Carbon::setTestNow('2024-01-01 06:00:00');
    $response = $this->getJson("/api/games/{$game->id}/state");
    expect($response->json('is_day'))->toBeTrue();
    
    // Test evening transition (8 PM)
    Carbon::setTestNow('2024-01-01 20:00:00');
    $response = $this->getJson("/api/games/{$game->id}/state");
    expect($response->json('is_day'))->toBeFalse();
    
    // Reset time
    Carbon::setTestNow();
});
