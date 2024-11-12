<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'is_private',
        'is_day',
        'join_code',
        'status'
    ];

    protected $casts = [
        'role_configuration' => 'array',
        'is_private' => 'boolean',
    ];

    // Updated default minimum players
    protected $attributes = [
        'min_players' => 3,
    ];

    // Improved role distribution strategy
    protected static function calculateRoleDistribution(int $userCount): array
    {
        $roles = [
            'Villager' => 0,
            'Cat' => 0,
            'Seer' => 0,
            'Guardian' => 0
        ];

        // Basic role distribution logic
        $roles['Villager'] = max(2, round($userCount * 0.1)); // At least 1 villager
        $roles['Cat'] = max(round($userCount * 0.2), 1); // Around 20% cat, at least 1
        $roles['Seer'] = max(1, round($userCount * 0.1)); // At least 1 seer
        $roles['Guardian'] = max(1, round($userCount * 0.1)); // At least 1 guardian

        // Ensure total roles match user count
        $totalAssignedRoles = array_sum($roles);
        if ($totalAssignedRoles > $userCount) {
            // Adjust villagers to match exact user count
            $roles['Villager'] -= ($totalAssignedRoles - $userCount);
        }

        return $roles;
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
     * Get default role configuration based on user count.
     */
    public function getDefaultRoleConfiguration(): array
    {
        $userCount = $this->users()->where('connection_status', 'joined')->count();
        $distribution = self::calculateRoleDistribution($userCount);

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
     * Start the game.
     * 
     * @return object Returns an object with game start information
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

        // Prepare role configuration
        $roleConfig = $this->role_configuration ?? $this->getDefaultRoleConfiguration();

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

        // Assign remaining users as default role (typically Villager)
        $defaultRoleId = Role::where('name', 'Villager')->first()->id;
        foreach ($shuffledUsers as $user) {
            $this->users()->updateExistingPivot($user->id, [
                'role_id' => $defaultRoleId,
                'user_status' => 'alive'
            ]);
        }
    }
}
