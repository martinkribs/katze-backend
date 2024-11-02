<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Player extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_instance_id',
        'user_id',
        'role_id',
        'is_alive',
        'special_status'
    ];

    protected $casts = [
        'is_alive' => 'boolean'
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
     * Get the role of this player.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the actions executed by this player.
     */
    public function executedActions(): HasMany
    {
        return $this->hasMany(Action::class, 'executing_player_id');
    }

    /**
     * Get the actions targeting this player.
     */
    public function targetedActions(): HasMany
    {
        return $this->hasMany(Action::class, 'target_player_id');
    }

    /**
     * Get the votes cast by this player.
     */
    public function votesGiven(): HasMany
    {
        return $this->hasMany(Vote::class, 'voter_id');
    }

    /**
     * Get the votes received by this player.
     */
    public function votesReceived(): HasMany
    {
        return $this->hasMany(Vote::class, 'target_id');
    }
}
