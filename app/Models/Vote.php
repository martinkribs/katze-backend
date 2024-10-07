<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Vote
 *
 * Represents a vote model in the application.
 *
 * @package App\Models
 */
class Vote extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['round_id', 'voter_id', 'target_id'];

    /**
     * Get the round associated with the vote.
     *
     * @return BelongsTo
     */
    public function round(): BelongsTo
    {
        return $this->belongsTo(Round::class);
    }

    /**
     * Get the player who cast the vote.
     *
     * @return BelongsTo
     */
    public function voter(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'voter_id');
    }

    /**
     * Get the target player of the vote.
     *
     * @return BelongsTo
     */
    public function target(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'target_id');
    }
}
