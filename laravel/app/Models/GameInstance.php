<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameInstance extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'user_id'
    ];

    /**
     * Get the game that this instance belongs to.
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * Get the user that this instance belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the players for this game instance.
     */
    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }
}
