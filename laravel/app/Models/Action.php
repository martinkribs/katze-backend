<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
    protected $fillable = ['action_name', 'action_type_id'];

    /**
     * Get the action types associated with the action.
     *
     * @return BelongsTo
     */
    public function actionType() : BelongsTo
    {
        return $this->belongsTo(ActionType::class);
    }

    /**
     * Get the players associated with the action.
     *
     * @return BelongsToMany
     */
    public function players() : BelongsToMany
    {
        return $this->belongsToMany(Player::class, 'player_action')->withTimestamps();
    }
}
