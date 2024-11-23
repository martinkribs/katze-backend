<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Game extends Model
{
    use HasFactory;

    // Team constants for win conditions
    public const TEAM_VILLAGERS = 'villagers';
    public const TEAM_CATS = 'cats';
    public const TEAM_SERIAL_KILLER = 'serial_killer';
    public const TEAM_LOVERS = 'lovers';

    protected $fillable = [
        'created_by',
        'timezone',
        'name',
        'description',
        'min_players',
        'is_private',
        'is_day',
        'join_code',
        'status',
        'winning_team'
    ];

    protected $casts = [
        'is_private' => 'boolean',
        'is_day' => 'boolean',
    ];

    protected $attributes = [
        'min_players' => 3,
    ];

    /**
     * Get the settings for this game.
     */
    public function settings(): HasOne
    {
        return $this->hasOne(GameSetting::class);
    }

    /**
     * Check if the game has ended and determine the winning team.
     */
    public function checkWinConditions(): bool
    {
        $alivePlayers = $this->users()
            ->where('game_user_role.user_status', 'alive')
            ->with('roles')
            ->get();

        $aliveVillagers = 0;
        $aliveCats = 0;
        $aliveSerialKiller = 0;
        $aliveLovers = 0;
        $totalAlive = $alivePlayers->count();

        foreach ($alivePlayers as $player) {
            $role = $player->roles->first();
            
            // Count players by team
            if ($role->key === 'serial_killer') {
                $aliveSerialKiller++;
            } elseif ($role->team === self::TEAM_CATS) {
                $aliveCats++;
            } elseif ($role->team === self::TEAM_VILLAGERS) {
                $aliveVillagers++;
            }

            // Check if player is part of lovers
            if ($this->isPlayerLover($player->id)) {
                $aliveLovers++;
            }
        }

        // Serial Killer wins if they're the last one alive
        if ($aliveSerialKiller > 0 && $totalAlive === 1) {
            $this->endGame(self::TEAM_SERIAL_KILLER);
            return true;
        }

        // Cats win if all villagers are dead (and serial killer)
        if ($aliveCats > 0 && $aliveVillagers === 0 && $aliveSerialKiller === 0) {
            $this->endGame(self::TEAM_CATS);
            return true;
        }

        // Villagers win if all cats and serial killer are dead
        if ($aliveVillagers > 0 && $aliveCats === 0 && $aliveSerialKiller === 0) {
            $this->endGame(self::TEAM_VILLAGERS);
            return true;
        }

        // Lovers win if they're the only ones alive
        if ($aliveLovers === $totalAlive && $totalAlive === 2) {
            $this->endGame(self::TEAM_LOVERS);
            return true;
        }
        
        return false;
    }

    /**
     * End the game with a winning team
     */
    private function endGame(string $winningTeam): void
    {
        $this->status = 'completed';
        $this->winning_team = $winningTeam;
        $this->save();
    }

    /**
     * Calculate role distribution based on player count
     */
    protected static function calculateRoleDistribution(int $userCount): array
    {
        $roles = [
            'villager' => 0,
            'cat' => 0,
            'serial_killer' => 1, // Always one serial killer
            'seer' => 0,
            'witch' => 0,
        ];

        // Basic role distribution logic
        $roles['cat'] = max(round($userCount * 0.2), 1); // Around 20% cats, at least 1
        
        // Fill remaining slots with villagers
        $totalAssignedRoles = array_sum($roles);
        $roles['villager'] = max(0, $userCount - $totalAssignedRoles);

        return $roles;
    }

    /**
     * Get default role configuration based on user count.
     */
    public function getDefaultRoleConfiguration(): array
    {
        $userCount = $this->users()->where('connection_status', 'joined')->count();
        $distribution = self::calculateRoleDistribution($userCount);

        return $this->mapRoleKeysToIds($distribution);
    }

    /**
     * Map role keys to their corresponding database IDs.
     */
    private function mapRoleKeysToIds(array $roleKeys): array
    {
        $roles = Role::whereIn('key', array_keys($roleKeys))->get();
        
        $roleConfiguration = [];
        foreach ($roles as $role) {
            $roleConfiguration[$role->id] = $roleKeys[$role->key];
        }

        return $roleConfiguration;
    }

    /**
     * Get the user that created the game.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the users in this game with their roles.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'game_user_role')
            ->withPivot('role_id', 'connection_status', 'user_status', 'is_game_master')
            ->withTimestamps();
    }

    /**
     * Get the invitations for this game.
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(GameInvitation::class);
    }

    /**
     * Check if the game can start.
     */
    public function canStart(): bool
    {
        $userCount = $this->users()->where('connection_status', 'joined')->count();
        return $userCount >= $this->min_players;
    }

    /**
     * Generate a unique join code for private games.
     */
    public static function generateJoinCode(): string
    {
        do {
            $code = Str::upper(Str::random(8));
        } while (static::where('join_code', $code)->exists());

        return $code;
    }

    /**
     * Start the game.
     */
    public function start(): object
    {
        if (!$this->canStart()) {
            return (object)[
                'success' => false,
                'message' => 'Cannot start game. Need at least ' . $this->min_players . ' users.'
            ];
        }

        // Update game status
        $this->status = 'in_progress';
        $this->save();

        // Assign roles to users
        $this->assignRoles();

        return (object)[
            'success' => true,
            'message' => 'Game started successfully',
            'game_id' => $this->id
        ];
    }

    /**
     * Assign roles to users in the game.
     */
    protected function assignRoles(): void
    {
        // Get joined users
        $joinedUsers = $this->users()->where('connection_status', 'joined')->get();

        // Get or create settings and get effective configuration
        $settings = $this->settings ?? GameSetting::create([
            'game_id' => $this->id,
            'use_default' => true,
            'role_configuration' => $this->getDefaultRoleConfiguration()
        ]);
        
        $roleConfig = $settings->getEffectiveConfiguration();

        // Shuffle users to randomize role assignment
        $shuffledUsers = $joinedUsers->shuffle();

        // Track assigned roles
        $assignedRoles = [];

        // Assign roles based on configuration
        foreach ($roleConfig as $roleId => $count) {
            for ($i = 0; $i < $count; $i++) {
                if ($shuffledUsers->isEmpty()) break;

                $user = $shuffledUsers->shift();
                
                // Update user's role in pivot table
                $this->users()->updateExistingPivot($user->id, [
                    'role_id' => $roleId,
                    'user_status' => 'alive'
                ]);

                $assignedRoles[] = $user->id;
            }
        }

        // Assign remaining users as default role (typically villager)
        $defaultRoleId = Role::where('key', 'villager')->first()->id;
        foreach ($shuffledUsers as $user) {
            $this->users()->updateExistingPivot($user->id, [
                'role_id' => $defaultRoleId,
                'user_status' => 'alive'
            ]);
        }
    }
}
