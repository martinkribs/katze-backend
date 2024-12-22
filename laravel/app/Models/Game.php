<?php

namespace App\Models;

use App\Events\GameStarted;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
     * Get the users in this game.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'game_user_roles')
            ->withPivot(['role_id', 'connection_status', 'user_status', 'affected_user', 'is_game_master'])
            ->withTimestamps();
    }

    /**
     * Get the settings for this game.
     */
    public function settings(): HasOne
    {
        return $this->hasOne(GameSetting::class);
    }

    /**
     * Get all game user roles for this game.
     */
    public function gameUserRoles(): HasMany
    {
        return $this->hasMany(GameUserRole::class);
    }

    /**
     * Check if the game has ended and determine the winning team.
     */
    public function checkWinConditions(): bool
    {
        $alivePlayers = $this->gameUserRoles()
            ->where('user_status', 'alive')
            ->with('role')
            ->get();

        $aliveVillagers = 0;
        $aliveCats = 0;
        $aliveSerialKiller = 0;
        $aliveLovers = 0;
        $totalAlive = $alivePlayers->count();

        foreach ($alivePlayers as $player) {
            $role = $player->role;
            if (!$role) continue;

            // Count players by team
            if ($role->key === 'serial_killer') {
                $aliveSerialKiller++;
            } elseif ($role->team === self::TEAM_CATS) {
                $aliveCats++;
            } elseif ($role->team === self::TEAM_VILLAGERS) {
                $aliveVillagers++;
            }

            // Check if player is part of lovers
            if ($player->user_status === 'in_love') {
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
            'serial_killer' => 0,
            'amor' => 0,
            'detective' => 0,
            'witch' => 0,
            'hiv' => 0,
            'doctor' => 0,
            'mute' => 0,
            'peter' => 0,
            'pyro' => 0,
            'slut' => 0,
            'seer' => 0,
            'traitor' => 0,
            'guard' => 0,
            'groupie' => 0,
            'executioner' => 0,
            'jester' => 0,
            'king' => 0,
        ];

        // Always assign one cat
        $roles['cat'] = 1;
        $remainingPlayers = $userCount - 1;

        // If more than 6 players, add another cat
        if ($userCount > 6) {
            $roles['cat']++;
            $remainingPlayers--;
        }

        // Prioritize special roles based on player count
        $specialRoles = [
            'seer',      // Essential for villager team
            'witch',     // Powerful support role
            'doctor',    // Protection role
            'detective', // Investigation role
            'guard',     // Protection role
            'amor',      // Can create lovers
            'slut',      // Special night action
            'king',      // Special villager role
        ];

        foreach ($specialRoles as $role) {
            if ($remainingPlayers > 0 && $userCount >= 4) { // Only assign special roles in games with 4+ players
                $roles[$role] = 1;
                $remainingPlayers--;
            }
        }

        // Add neutral roles in larger games
        if ($remainingPlayers > 0 && $userCount >= 7) {
            $roles['serial_killer'] = 1;
            $remainingPlayers--;
        }

        if ($remainingPlayers > 0 && $userCount >= 8) {
            $roles['jester'] = 1;
            $remainingPlayers--;
        }

        // Only use villagers if we've run out of special roles to assign
        if ($remainingPlayers > 0) {
            $roles['villager'] = $remainingPlayers;
        }

        return $roles;
    }

    /**
     * Get default role configuration based on user count.
     */
    public function getDefaultRoleConfiguration(): array
    {
        $userCount = $this->gameUserRoles()->where('connection_status', 'joined')->count();
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
        $userCount = $this->gameUserRoles()->where('connection_status', 'joined')->count();
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

        // Dispatch GameStarted event
        event(new GameStarted($this->id));

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
        // Get all users including game master
        $allGameUserRoles = $this->gameUserRoles()->where('connection_status', 'joined')->get();

        // Get or create settings and get effective configuration
        /** @var $settings GameSetting */
        $settings = $this->settings ?? GameSetting::create([
            'game_id' => $this->id,
            'use_default' => true,
            'role_configuration' => $this->getDefaultRoleConfiguration()
        ]);

        // Shuffle users to randomize role assignment
        $shuffledGameUserRoles = $allGameUserRoles->shuffle();

        // Track assigned roles
        $assignedRoles = [];

        // Assign roles based on configuration
        foreach ($settings['role_configuration'] as $roleId => $count) {
            for ($i = 0; $i < $count; $i++) {
                if ($shuffledGameUserRoles->isEmpty()) break;

                $gameUserRole = $shuffledGameUserRoles->shift();

                // Update user's role
                $gameUserRole->update([
                    'role_id' => $roleId,
                    'user_status' => 'alive'
                ]);

                $assignedRoles[] = $gameUserRole->user_id;
            }
        }

        // Assign remaining users as default role (typically villager)
        $defaultRoleId = Role::where('key', 'villager')->first()->id;
        foreach ($shuffledGameUserRoles as $gameUserRole) {
            $gameUserRole->update([
                'role_id' => $defaultRoleId,
                'user_status' => 'alive'
            ]);
        }
    }
}
