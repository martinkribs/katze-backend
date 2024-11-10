<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VotingRound extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'round_number',
        'is_day'
    ];

    protected $casts = [
        'is_day' => 'boolean'
    ];

    /**
     * Get the game that owns the round.
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * Get the actions for this round.
     */
    public function actions(): HasMany
    {
        return $this->hasMany(Action::class);
    }

    /**
     * Get the votes for this round.
     */
    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }
}
