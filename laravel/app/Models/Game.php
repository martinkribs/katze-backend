<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Game extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by',
        'timezone',
        'name',
        'description',
        'role_configuration',
        'min_players',
        'max_players',
        'day_duration_minutes',
        'night_duration_minutes',
        'is_private',
        'join_code',
        'status'
    ];

    protected $casts = [
        'role_configuration' => 'array',
        'is_private' => 'boolean',
        'min_players' => 'integer',
        'max_players' => 'integer',
        'day_duration_minutes' => 'integer',
        'night_duration_minutes' => 'integer',
    ];

    // Default game configuration
    protected static $defaultRoleDistribution = [
        'Villager' => 5,
        'Cat' => 2,
        'Seer' => 1,
        'Guardian' => 1
    ];

    /**
     * Get the user that created the game.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the game instances for this game.
     */
    public function gameInstances(): HasMany
    {
        return $this->hasMany(GameInstance::class);
    }

    /**
     * Get the invitations for this game.
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(GameInvitation::class);
    }

    /**
     * Get all players across all game instances.
     */
    public function players()
    {
        return $this->hasManyThrough(Player::class, GameInstance::class);
    }

    /**
     * Create a new game instance.
     */
    public function createInstance(): GameInstance
    {
        return $this->gameInstances()->create([
            'status' => 'waiting',
        ]);
    }

    /**
     * Check if the game has reached its maximum number of players.
     */
    public function isFullyBooked(): bool
    {
        return $this->players()->count() >= $this->max_players;
    }

    /**
     * Check if the game can start.
     */
    public function canStart(): bool
    {
        $playerCount = $this->players()->count();
        return $playerCount >= $this->min_players && $playerCount <= $this->max_players;
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
     * Validate role configuration.
     */
    public function validateRoleConfiguration(): bool
    {
        // If no role configuration is provided, use default
        if (empty($this->role_configuration)) {
            $this->role_configuration = $this->getDefaultRoleConfiguration();
        }

        $totalRoles = array_sum($this->role_configuration);
        $minPlayers = $this->min_players;
        $maxPlayers = $this->max_players;

        return $totalRoles >= $minPlayers && $totalRoles <= $maxPlayers;
    }

    /**
     * Get default role configuration based on player count.
     */
    public function getDefaultRoleConfiguration(): array
    {
        $playerCount = $this->players()->count();
        $distribution = self::$defaultRoleDistribution;

        // Adjust role counts based on player count
        if ($playerCount > 7) {
            $distribution['Villager'] += 2;
            $distribution['Cat'] += 1;
        }

        return $this->mapRoleNamesToIds($distribution);
    }

    /**
     * Map role names to their corresponding database IDs.
     */
    private function mapRoleNamesToIds(array $roleNames): array
    {
        $roles = Role::whereIn('name', array_keys($roleNames))->get();
        
        $roleConfiguration = [];
        foreach ($roles as $role) {
            $roleConfiguration[$role->id] = $roleNames[$role->name];
        }

        return $roleConfiguration;
    }

    /**
     * Get the current active game instance.
     */
    public function getCurrentInstance(): ?GameInstance
    {
        return $this->gameInstances()->where('status', 'in_progress')->first();
    }

    /**
     * Start the game with the current players.
     */
    public function start(): ?GameInstance
    {
        if (!$this->canStart()) {
            return null;
        }

        $gameInstance = $this->createInstance();
        
        // Assign players to the game instance
        $this->players()->update(['game_instance_id' => $gameInstance->id]);

        // Validate and set role configuration
        $this->validateRoleConfiguration();

        // Assign roles
        $gameInstance->assignRoles();

        // Start the game
        $gameInstance->start();

        return $gameInstance;
    }
}
