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
        'allowed_phases'
    ];

    protected $casts = [
        'usage_limit' => 'integer',
        'target_type' => 'string',
        'allowed_phases' => 'array'
    ];

    /**
     * Check if this action type can be used in the given phase
     */
    public function isAllowedInPhase(string $phase): bool
    {
        return in_array($phase, $this->allowed_phases);
    }

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
     * Get the role action type relationships.
     */
    public function roleActionTypes(): HasMany
    {
        return $this->hasMany(RoleActionType::class);
    }

    /**
     * Get the roles that can use this action type.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_action_types')
                    ->withTimestamps();
    }
}
