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
                    ->withTimestamps();
    }
}
