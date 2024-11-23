<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'role_configuration',
        'use_default'
    ];

    protected $casts = [
        'role_configuration' => 'array',
        'use_default' => 'boolean'
    ];

    protected $attributes = [
        'use_default' => true
    ];

    /**
     * Get the game that owns the settings.
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * Get the effective role configuration.
     */
    public function getEffectiveConfiguration(): array
    {
        if ($this->use_default) {
            return $this->game->getDefaultRoleConfiguration();
        }

        return $this->role_configuration;
    }
}
