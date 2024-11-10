<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'team',
        'can_use_night_action'
    ];

    protected $casts = [
        'can_use_night_action' => 'boolean'
    ];

    /**
     * Get the players with this role.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the action types available to this role.
     */
    public function actionTypes(): BelongsToMany
    {
        return $this->belongsToMany(ActionType::class, 'role_action_type')
                    ->withPivot('role_name');
    }
}
