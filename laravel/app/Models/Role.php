<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\App;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'description',
        'team',
        'can_use_night_action'
    ];

    protected $casts = [
        'can_use_night_action' => 'boolean'
    ];

    /**
     * Get the translated name of the role.
     */
    public function getTranslatedNameAttribute(): string
    {
        $locale = App::getLocale();
        return __("roles.{$this->key}.name");
    }

    /**
    * Get the translated description of the role.
    */
    public function getTranslatedDescriptionAttribute(): string
    {
        $locale = App::getLocale();
        return __("roles.{$this->key}.description");
    }

    /**
     * Get the game user roles for this role.
     */
    public function gameUserRoles(): HasMany
    {
        return $this->hasMany(GameUserRole::class);
    }

    /**
     * Get the role action type relationships.
     */
    public function roleActionTypes(): HasMany
    {
        return $this->hasMany(RoleActionType::class);
    }

    /**
     * Get the action types available to this role.
     */
    public function actionTypes(): BelongsToMany
    {
        return $this->belongsToMany(ActionType::class, 'role_action_types')
                    ->withTimestamps()
                    ->withPivot(['action_type_id'])  // Explicitly include pivot columns
                    ->select('action_types.*');      // Explicitly select from action_types table
    }

    /**
     * Get the users that have this role through game user roles.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'game_user_roles')
                    ->withPivot(['game_id', 'connection_status', 'user_status', 'is_game_master'])
                    ->withTimestamps();
    }
}
