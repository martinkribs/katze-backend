<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Player
 *
 * Represents a player model in the application.
 *
 * @package App\Models
 */
class Player extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['user_id', 'game_id', 'role', 'is_alive', 'special_status'];

    /**
     * Get the user associated with the player.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the game associated with the player.
     *
     * @return BelongsTo
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * Get the role associated with the player.
     *
     * @return BelongsTo
     */
    public function role() : BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the actions associated with the player.
     *
     * @return BelongsToMany
     */
    public function actions() : BelongsToMany
    {
        return $this->belongsToMany(Action::class, 'player_action')->withTimestamps();
    }

    /**
     * Get the votes associated with the player.
     *
     * @return HasMany
     */
    public function votes() : HasMany
    {
        return $this->hasMany(Vote::class, 'voter_id');
    }
}
