<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ActionType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'usage_limit',
        'result_type_id',
        'target_type',
        'is_day_action'
    ];

    protected $casts = [
        'usage_limit' => 'integer',
        'target_type' => 'string',
        'is_day_action' => 'boolean'
    ];

    /**
     * Get the result type that represents this action's result.
     */
    public function resultType(): BelongsTo
    {
        return $this->belongsTo(ResultType::class, 'result_type_id');
    }

    /**
     * Get the actions of this type.
     */
    public function actions(): HasMany
    {
        return $this->hasMany(Action::class);
    }

    /**
     * Get the roles that can use this action type.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'player_action_type')
                    ->withPivot('role_name');
    }
}
