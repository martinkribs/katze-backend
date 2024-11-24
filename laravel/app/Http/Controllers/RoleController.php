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
        $roles = Role::with('actionTypes')->get();
        
        // Group roles by team
        $groupedRoles = $roles->groupBy('team')->map(function ($teamRoles) {
            return $teamRoles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'key' => $role->key,
                    'name' => $role->name,
                    'description' => $role->description,
                    'can_use_night_action' => $role->can_use_night_action,
                    'action_types' => $role->actionTypes->map(function ($actionType) {
                        return [
                            'id' => $actionType->id,
                            'name' => $actionType->name,
                            'description' => $actionType->description,
                            'usage_limit' => $actionType->usage_limit,
                            'target_type' => $actionType->target_type,
                            'is_day_action' => $actionType->is_day_action,
                        ];
                    }),
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

    /**
     * Get action types for a specific role.
     */
    public function getActionTypes(Role $role): JsonResponse
    {
        $actionTypes = $role->actionTypes->map(function ($actionType) {
            return [
                'id' => $actionType->id,
                'name' => $actionType->name,
                'description' => $actionType->description,
                'usage_limit' => $actionType->usage_limit,
                'target_type' => $actionType->target_type,
                'is_day_action' => $actionType->is_day_action,
            ];
        });

        return response()->json([
            'role' => [
                'id' => $role->id,
                'key' => $role->key,
                'name' => $role->name,
                'description' => $role->description,
            ],
            'action_types' => $actionTypes,
        ]);
    }
}
