<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Action extends Model
{
    use HasFactory;

    protected $fillable = [
        'round_id',
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
