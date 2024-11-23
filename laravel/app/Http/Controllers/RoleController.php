<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;

class RoleController extends BaseController
{
    /**
     * Get available roles for game configuration.
     */
    public function index(): JsonResponse
    {
        $roles = Role::all();
        
        // Group roles by team
        $groupedRoles = $roles->groupBy('team')->map(function ($teamRoles) {
            return $teamRoles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'key' => $role->key,
                    'name' => $role->name,
                    'description' => $role->description,
                    'can_use_night_action' => $role->can_use_night_action,
                ];
            });
        });

        return response()->json([
            'roles' => $groupedRoles,
            'teams' => [
                'villagers' => [
                    'name' => 'Villagers',
                    'description' => 'Win by eliminating all threats to the village'
                ],
                'cats' => [
                    'name' => 'Cats',
                    'description' => 'Win by eliminating all villagers'
                ],
                'serial_killer' => [
                    'name' => 'Serial Killer',
                    'description' => 'Win by being the last player alive'
                ],
                'neutral' => [
                    'name' => 'Neutral',
                    'description' => 'Have their own win conditions'
                ]
            ]
        ]);
    }
}
