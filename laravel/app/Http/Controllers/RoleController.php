<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Game;
use App\Models\Action;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
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
        $userId = Auth::id();
        if ($userId === null) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Get user's active game
        $game = Game::whereHas('users', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->where('status', 'in_progress')->first();

        $actionTypes = $role->actionTypes->map(function ($actionType) use ($userId, $game) {
            // Count actions used today for this action type
            $actionsUsedToday = 0;
            if ($game) {
                $startOfDay = now()->startOfDay();
                $actionsUsedToday = Action::where('executing_player_id', $userId)
                    ->where('game_id', $game->id)
                    ->where('action_type_id', $actionType->id)
                    ->where('created_at', '>=', $startOfDay)
                    ->count();
            }

            return [
                'id' => $actionType->id,
                'name' => $actionType->name,
                'description' => $actionType->description,
                'usage_limit' => $actionType->usage_limit,
                'target_type' => $actionType->target_type,
                'is_day_action' => $actionType->is_day_action,
                'actions_used' => $actionsUsedToday,
                'can_use' => $game ? ($actionType->usage_limit === null || $actionsUsedToday < $actionType->usage_limit) && $actionType->is_day_action === $game->is_day : false
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
