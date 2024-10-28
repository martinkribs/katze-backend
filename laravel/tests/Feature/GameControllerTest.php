<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\User;
use App\Models\Player;
use App\Models\Round;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class GameControllerTest extends TestCase
{
    use RefreshDatabase;

    private $users;
    private $authenticatedUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test users
        $this->users = User::factory()->count(8)->create();
        $this->authenticatedUser = $this->users->first();
        $this->actingAs($this->authenticatedUser);
    }

    public function test_game_creation_with_valid_players()
    {
        $response = $this->postJson('/api/games', [
            'player_ids' => $this->users->pluck('id')->toArray()
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'game_id']);

        $gameId = $response->json('game_id');
        $game = Game::find($gameId);

        $this->assertEquals('waiting', $game->status);
        $this->assertNull($game->start_time);
        $this->assertNull($game->end_time);

        // Verify role distribution
        $players = Player::where('game_id', $gameId)->get();
        $this->assertEquals(8, $players->count());
        
        // Check role counts
        $roleCounts = $players->groupBy('role')->map->count();
        $this->assertEquals(2, $roleCounts['cat']); // 8 players = 2 cats
        $this->assertEquals(1, $roleCounts['seer']);
        $this->assertEquals(1, $roleCounts['witch']);
        $this->assertEquals(1, $roleCounts['detective']); // 8+ players get detective
        $this->assertEquals(3, $roleCounts['villager']);
    }

    public function test_game_creation_with_insufficient_players()
    {
        $response = $this->postJson('/api/games', [
            'player_ids' => $this->users->take(5)->pluck('id')->toArray()
        ]);

        $response->assertStatus(422);
    }

    public function test_starting_game()
    {
        // Create a game
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
        $this->assertEquals('in_progress', $game->status);
        $this->assertNotNull($game->start_time);

        // Verify first round was created
        $round = Round::where('game_id', $game->id)->first();
        $this->assertNotNull($round);
        $this->assertEquals(1, $round->round_number);
    }

    public function test_cannot_start_already_started_game()
    {
        $game = Game::create(['status' => 'in_progress']);

        $response = $this->postJson("/api/games/{$game->id}/start");

        $response->assertStatus(400)
            ->assertJson(['error' => 'Game cannot be started']);
    }

    public function test_get_current_game_state()
    {
        // Create a game with a round
        $game = Game::create(['status' => 'in_progress', 'start_time' => now()]);
        $players = collect();
        foreach ($this->users as $user) {
            $players->push(Player::create([
                'user_id' => $user->id,
                'game_id' => $game->id,
                'role' => 'villager',
                'is_alive' => true
            ]));
        }

        $round = Round::create([
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

        $this->assertEquals('in_progress', $response->json('game_status'));
        $this->assertEquals(true, $response->json('is_day'));
        $this->assertEquals(8, count($response->json('alive_players')));
    }

    public function test_new_round_creation_when_current_round_ends()
    {
        // Create a game with an expired round
        $game = Game::create(['status' => 'in_progress', 'start_time' => now()]);
        Round::create([
            'game_id' => $game->id,
            'round_number' => 1,
            'start_time' => now()->subHours(3),
            'end_time' => now()->subHour(),
            'is_day' => true
        ]);

        $response = $this->getJson("/api/games/{$game->id}/state");

        $response->assertStatus(200);
        
        // Verify new round was created
        $latestRound = Round::where('game_id', $game->id)
            ->orderBy('round_number', 'desc')
            ->first();
        
        $this->assertEquals(2, $latestRound->round_number);
        $this->assertGreaterThan(now(), $latestRound->end_time);
    }

    public function test_day_night_cycle_timing()
    {
        $game = Game::create(['status' => 'in_progress']);
        
        // Test morning transition (6 AM)
        Carbon::setTestNow('2024-01-01 06:00:00');
        $response = $this->getJson("/api/games/{$game->id}/state");
        $this->assertTrue($response->json('is_day'));
        
        // Test evening transition (8 PM)
        Carbon::setTestNow('2024-01-01 20:00:00');
        $response = $this->getJson("/api/games/{$game->id}/state");
        $this->assertFalse($response->json('is_day'));
        
        // Reset time
        Carbon::setTestNow();
    }
}
