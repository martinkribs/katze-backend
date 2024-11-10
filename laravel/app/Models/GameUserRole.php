<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

class GameUserRole extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'status',
        'started_at',
        'ended_at',
        'game_state'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'game_state' => 'array'
    ];

    /**
     * Get the game this instance belongs to.
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * Get all players in this game instance.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the game master for this game instance.
     */
    public function gameMaster(): ?User
    {
        return $this->players()->where('is_game_master', true)->first();
    }

    /**
     * Get all alive players.
     */
    public function alivePlayers()
    {
        return $this->players()->where('is_alive', true);
    }

    /**
     * Start the game instance.
     */
    public function start(): void
    {
        $this->update([
            'status' => 'in_progress',
            'started_at' => now(),
            'game_state' => [
                'current_phase' => 'day',
                'day_number' => 1,
                'votes' => [],
                'actions' => []
            ]
        ]);
    }

    /**
     * End the game instance.
     */
    public function end(string $winningTeam): void
    {
        $this->update([
            'status' => 'completed',
            'ended_at' => now(),
            'game_state' => array_merge($this->game_state ?? [], [
                'winner' => $winningTeam
            ])
        ]);
    }

    /**
     * Assign roles to players.
     */
    public function assignRoles(): void
    {
        $players = $this->players;
        $game = $this->game;

        // Get roles based on game configuration
        $roles = Role::whereIn('id', array_keys($game->role_configuration))->get();

        // Shuffle players to randomize role assignment
        $shuffledPlayers = $players->shuffle();

        // Assign roles
        $roleAssignments = [];
        foreach ($roles as $role) {
            $count = $game->role_configuration[$role->id] ?? 0;
            for ($i = 0; $i < $count; $i++) {
                if ($shuffledPlayers->isNotEmpty()) {
                    $player = $shuffledPlayers->shift();
                    $player->update([
                        'role_id' => $role->id
                    ]);
                    $roleAssignments[] = $player->id;
                }
            }
        }

        // Assign remaining players a default role (Villager)
        $defaultRoleId = Role::where('name', 'Villager')->first()->id;
        foreach ($shuffledPlayers as $player) {
            $player->update([
                'role_id' => $defaultRoleId
            ]);
        }

        // Log role distribution for debugging
        Log::info('Role Distribution', [
            'game_instance_id' => $this->id,
            'role_assignments' => $roleAssignments
        ]);
    }

    /**
     * Check if the game is over.
     */
    public function checkGameOver(): ?string
    {
        $players = $this->players;
        $catPlayers = $players->filter(function ($player) {
            return $player->is_alive && $player->role->team === 'cats';
        });
        $villagerPlayers = $players->filter(function ($player) {
            return $player->is_alive && $player->role->team === 'villagers';
        });

        if ($catPlayers->count() >= $villagerPlayers->count()) {
            return 'cats';
        }

        if ($catPlayers->count() === 0) {
            return 'villagers';
        }

        return null;
    }

    /**
     * Progress to the next phase.
     */
    public function progressPhase(): void
    {
        $gameState = $this->game_state;
        
        // Toggle between day and night
        $gameState['current_phase'] = 
            $gameState['current_phase'] === 'day' ? 'night' : 'day';
        
        // Increment day number if switching to day
        if ($gameState['current_phase'] === 'day') {
            $gameState['day_number']++;
        }

        // Reset votes and actions
        $gameState['votes'] = [];
        $gameState['actions'] = [];

        $this->update(['game_state' => $gameState]);

        // Check for game over condition
        $winner = $this->checkGameOver();
        if ($winner) {
            $this->end($winner);
        }
    }
}
