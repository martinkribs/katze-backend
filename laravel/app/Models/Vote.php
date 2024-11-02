<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vote extends Model
{
    use HasFactory;

    protected $fillable = [
        'round_id',
        'voter_id',
        'target_id',
        'reason'
    ];

    /**
     * Get the round this vote belongs to.
     */
    public function round(): BelongsTo
    {
        return $this->belongsTo(Round::class);
    }

    /**
     * Get the player who cast the vote.
     */
    public function voter(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'voter_id');
    }

    /**
     * Get the player who was voted for.
     */
    public function target(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'target_id');
    }
}
