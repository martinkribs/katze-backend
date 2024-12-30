<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Action extends Model
{
    use HasFactory;

    /**
     * Check if a player can perform an action type based on limits and game phase
     */
    public static function canPerformAction(User $player, ActionType $actionType, Game $game): bool 
    {
        // Check if action is allowed during current game phase
        if (!$actionType->isAllowedInPhase($game->phase)) {
            return false;
        }

        // If no usage limit, action can be performed
        if ($actionType->usage_limit === 0) {
            return true;
        }

        // Count how many times this action has been used by the player in the current day/night
        $startOfDay = now()->startOfDay();
        $actionCount = self::where('executing_player_id', $player->id)
            ->where('game_id', $game->id)
            ->where('action_type_id', $actionType->id)
            ->where('created_at', '>=', $startOfDay)
            ->count();

        return $actionCount < $actionType->usage_limit;
    }

    protected $fillable = [
        'game_id',
        'executing_player_id',
        'target_player_id',
        'action_type_id',
        'result_type_id',
        'action_notes',
        'is_successful'
    ];

    protected $casts = [
        'is_successful' => 'boolean'
    ];

    /**
     * Get the player executing the action.
     */
    public function executingUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executing_player_id');
    }

    /**
     * Get the player targeted by the action.
     */
    public function targetPlayer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_player_id');
    }

    /**
     * Get the type of this action.
     */
    public function actionType(): BelongsTo
    {
        return $this->belongsTo(ActionType::class);
    }

    /**
     * Get the result type of this action.
     */
    public function resultType(): BelongsTo
    {
        return $this->belongsTo(ResultType::class);
    }
}
