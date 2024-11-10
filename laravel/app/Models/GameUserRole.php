<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameUserRole extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'user_id',
        'role_id',
        'connection_status',
        'user_status',
        'affected_user',
        'is_game_master'
    ];

    /**
     * Get the game this instance belongs to.
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * Get the user for this game role.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the role for this game user.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the affected user in this game instance.
     */
    public function affectedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'affected_user');
    }

    /**
     * Determine if this is a game master instance.
     */
    public function isGameMaster(): bool
    {
        return $this->is_game_master === true;
    }
}
