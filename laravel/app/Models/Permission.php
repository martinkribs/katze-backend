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
    protected $fillable = [
        'role',
        'permission'
    ];

    /**
     * Check if a role has a specific permission
     *
     * @param string $role
     * @param string $permission
     * @return bool
     */
    public static function hasPermission(string $role, string $permission): bool
    {
        return static::where('role', $role)
            ->where('permission', $permission)
            ->exists();
    }

    /**
     * Get all permissions for a specific role
     *
     * @param string $role
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getPermissionsForRole(string $role)
    {
        return static::where('role', $role)->get();
    }

    /**
     * Get all roles that have a specific permission
     *
     * @param string $permission
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getRolesWithPermission(string $permission)
    {
        return static::where('permission', $permission)
            ->pluck('role')
            ->unique()
            ->values();
    }
}
