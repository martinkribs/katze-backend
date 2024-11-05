<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Player extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_instance_id',
        'user_id',
        'role_id',
        'is_alive',
        'is_game_master',
        'special_status',
        'additional_info'
    ];

    protected $casts = [
        'is_alive' => 'boolean',
        'is_game_master' => 'boolean',
        'additional_info' => 'array'
    ];

    /**
     * Get the game instance this player belongs to.
     */
    public function gameInstance(): BelongsTo
    {
        return $this->belongsTo(GameInstance::class);
    }

    /**
     * Get the user associated with this player.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the role assigned to this player.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Scope a query to only include alive players.
     */
    public function scopeAlive($query)
    {
        return $query->where('is_alive', true);
    }

    /**
     * Determine if the player can perform actions.
     */
    public function canPerformActions(): bool
    {
        return $this->is_alive && $this->role && $this->role->can_use_night_action;
    }

    /**
     * Mark the player as eliminated.
     */
    public function eliminate(): void
    {
        $this->update(['is_alive' => false]);
    }
}
