<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoleActionType extends Model
{
    use HasFactory;

    protected $table = 'role_action_types';

    protected $fillable = [
        'role_id',
        'action_type_id'
    ];

    /**
     * Get the role that owns this action type.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the action type that belongs to this role.
     */
    public function actionType(): BelongsTo
    {
        return $this->belongsTo(ActionType::class);
    }
}
