<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActionType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'usage_limit',
        'effect',
        'target_type'
    ];

    protected $casts = [
        'usage_limit' => 'integer',
        'effect' => 'string',
        'target_type' => 'string'
    ];

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
    public function roles(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'player_action_type')
                    ->withPivot('role_name');
    }
}
