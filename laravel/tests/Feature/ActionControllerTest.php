<?php

namespace Tests\Feature;

use App\Models\Action;
use App\Models\ActionType;
use App\Models\Game;
use App\Models\Player;
use App\Models\Role;
use App\Models\Round;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActionControllerTest extends TestCase
{
    use RefreshDatabase;

    private $game;
    private $round;
    private $catRole;
    private $villagerRole;
    private $catKillAction;
    private $voteAction;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $this->catRole = Role::create([
            'name' => 'cat',
            'display_name' => 'Cat',
            'description' => 'Evil cats trying to eliminate villagers',
            'is_evil' => true
        ]);

        $this->villagerRole = Role::create([
            'name' => 'villager',
            'display_name' => 'Villager',
            'description' => 'Regular villager with voting ability',
            'is_evil' => false
        ]);

        // Create action types
        $this->catKillAction = ActionType::create([
            'name' => 'Cat Kill',
            'description' => 'Cats choose a player to eliminate during the night',
            'target_type' => 'single',
            'effect' => 'kill',
            'usage_limit' => null
        ]);

        $this->voteAction = ActionType::create([
            'name' => 'Civilian Vote',
            'description' => 'Vote to eliminate a player during the day',
            'target_type' => 'single',
            'effect' => 'kill',
            'usage_limit' => 1
        ]);

        // Set up permissions
        $this->catRole->actionTypes()->attach([$this->catKillAction->id, $this->voteAction->id]);
        $this->villagerRole->actionTypes()->attach([$this->voteAction->id]);

        // Create game and round
        $this->game = Game::create(['status' => 'in_progress']);
        $this->round = Round::create([
            'game_id' => $this->game->id,
            'number' => 1,
            'is_day' => false,
            'is_current' => true
        ]);
    }

    public function test_cat_can_kill_at_night(): void
    {
        $cat = User::factory()->create();
        $target = User::factory()->create();

        $catPlayer = Player::create([
            'user_id' => $cat->id,
            'game_id' => $this->game->id,
            'role_id' => $this->catRole->id,
            'is_alive' => true
        ]);

        $targetPlayer = Player::create([
            'user_id' => $target->id,
            'game_id' => $this->game->id,
            'role_id' => $this->villagerRole->id,
            'is_alive' => true
        ]);

        $response = $this->actingAs($cat)->postJson("/games/{$this->game->id}/actions", [
            'action_type_id' => $this->catKillAction->id,
            'target_player_ids' => [$targetPlayer->id]
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('actions', [
            'player_id' => $catPlayer->id,
            'action_type_id' => $this->catKillAction->id,
            'round_id' => $this->round->id
        ]);
        $this->assertFalse($targetPlayer->fresh()->is_alive);
    }

    public function test_cat_cannot_kill_during_day(): void
    {
        $this->round->update(['is_day' => true]);

        $cat = User::factory()->create();
        $target = User::factory()->create();

        $catPlayer = Player::create([
            'user_id' => $cat->id,
            'game_id' => $this->game->id,
            'role_id' => $this->catRole->id,
            'is_alive' => true
        ]);

        $targetPlayer = Player::create([
            'user_id' => $target->id,
            'game_id' => $this->game->id,
            'role_id' => $this->villagerRole->id,
            'is_alive' => true
        ]);

        $response = $this->actingAs($cat)->postJson("/games/{$this->game->id}/actions", [
            'action_type_id' => $this->catKillAction->id,
            'target_player_ids' => [$targetPlayer->id]
        ]);

        $response->assertStatus(403);
        $this->assertTrue($targetPlayer->fresh()->is_alive);
    }

    public function test_villager_cannot_use_cat_actions(): void
    {
        $villager = User::factory()->create();
        $target = User::factory()->create();

        $villagerPlayer = Player::create([
            'user_id' => $villager->id,
            'game_id' => $this->game->id,
            'role_id' => $this->villagerRole->id,
            'is_alive' => true
        ]);

        $targetPlayer = Player::create([
            'user_id' => $target->id,
            'game_id' => $this->game->id,
            'role_id' => $this->villagerRole->id,
            'is_alive' => true
        ]);

        $response = $this->actingAs($villager)->postJson("/games/{$this->game->id}/actions", [
            'action_type_id' => $this->catKillAction->id,
            'target_player_ids' => [$targetPlayer->id]
        ]);

        $response->assertStatus(403);
        $this->assertTrue($targetPlayer->fresh()->is_alive);
    }

    public function test_dead_player_cannot_perform_actions(): void
    {
        $cat = User::factory()->create();
        $target = User::factory()->create();

        $catPlayer = Player::create([
            'user_id' => $cat->id,
            'game_id' => $this->game->id,
            'role_id' => $this->catRole->id,
            'is_alive' => false
        ]);

        $targetPlayer = Player::create([
            'user_id' => $target->id,
            'game_id' => $this->game->id,
            'role_id' => $this->villagerRole->id,
            'is_alive' => true
        ]);

        $response = $this->actingAs($cat)->postJson("/games/{$this->game->id}/actions", [
            'action_type_id' => $this->catKillAction->id,
            'target_player_ids' => [$targetPlayer->id]
        ]);

        $response->assertStatus(403);
        $this->assertTrue($targetPlayer->fresh()->is_alive);
    }

    public function test_game_ends_when_cats_equal_villagers(): void
    {
        $cat = User::factory()->create();
        $target1 = User::factory()->create();
        $target2 = User::factory()->create();

        $catPlayer = Player::create([
            'user_id' => $cat->id,
            'game_id' => $this->game->id,
            'role_id' => $this->catRole->id,
            'is_alive' => true
        ]);

        // Create two villagers, one will be killed
        Player::create([
            'user_id' => $target1->id,
            'game_id' => $this->game->id,
            'role_id' => $this->villagerRole->id,
            'is_alive' => true
        ]);

        $targetPlayer = Player::create([
            'user_id' => $target2->id,
            'game_id' => $this->game->id,
            'role_id' => $this->villagerRole->id,
            'is_alive' => true
        ]);

        $response = $this->actingAs($cat)->postJson("/games/{$this->game->id}/actions", [
            'action_type_id' => $this->catKillAction->id,
            'target_player_ids' => [$targetPlayer->id]
        ]);

        $response->assertStatus(200);
        $this->assertEquals('finished', $this->game->fresh()->status);
        $this->assertEquals(['winner' => 'cats'], $this->game->fresh()->metadata);
    }
}
