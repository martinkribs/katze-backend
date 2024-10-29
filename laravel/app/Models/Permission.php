<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = ['permission_name'];

    /**
     * Get the roles associated with the permission.
     *
     * @return HasMany
     */
    public function roles() {
        return $this->belongsToMany(Role::class, 'role_permission');
    }
}
