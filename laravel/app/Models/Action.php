<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Action
 *
 * Represents an action model in the application.
 *
 * @package App\Models
 */
class Action extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['round_id', 'player_id', 'action_type', 'target_player_id', 'result'];

    /**
     * Get the round associated with the action.
     *
     * @return BelongsTo
     */
    public function round(): BelongsTo
    {
        return $this->belongsTo(Round::class);
    }

    /**
     * Get the player who performed the action.
     *
     * @return BelongsTo
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    /**
     * Get the type of action performed.
     *
     * @return BelongsTo
     */
    public function actionType(): BelongsTo
    {
        return $this->belongsTo(ActionType::class);
    }

    /**
     * Get the target player of the action.
     *
     * @return BelongsTo
     */
    public function targetPlayer(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'target_player_id');
    }
}
