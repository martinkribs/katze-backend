<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\GameInvitation;
use App\Models\Role;
use App\Models\GameSetting;
use App\Models\Action;
use App\Models\GameUserRole;
use App\Http\Requests\Game\GameCreateRequest;
use App\Http\Requests\Game\GameJoinRequest;
use App\Http\Requests\Game\GameInviteRequest;
use App\Http\Requests\Game\GameStartRequest;
use App\Http\Requests\Game\GameIndexRequest;
use App\Http\Requests\Game\GameShowRequest;
use App\Http\Requests\Game\GameSettingsRequest;
use App\Http\Requests\Game\GameActionRequest;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class GameController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Create a new game.
     *
     * @throws Exception
     */
    public function create(GameCreateRequest $request): JsonResponse
    {
        try {
            $userId = Auth::id();
            if ($userId === null) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            /** @var Game $game */
            $game = DB::transaction(function () use ($request, $userId): Game {
                /** @var array<string, mixed> */
                $gameData = $request->getGameData();

                /** @var Game $game */
                $game = Game::create([
                    'created_by' => $userId,
                    'name' => $gameData['name'],
                    'description' => $gameData['description'],
                    'is_private' => $gameData['is_private'],
                    'is_day' => true,
                    'join_code' => $gameData['is_private'] ? Game::generateJoinCode() : null,
                    'timezone' => $gameData['timezone'],
                    'status' => 'pending',
                    'min_players' => $gameData['min_players'] ?? 3
                ]);

                // Add game creator to game_user_role with game master status
                GameUserRole::create([
                    'game_id' => $game->id,
                    'user_id' => $userId,
                    'connection_status' => 'joined',  // Override default 'invited' status
                    'is_game_master' => true
                ]);

                return $game;
            });

            return response()->json([
                'message' => 'Game created successfully',
                'gameId' => $game->id,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to create game',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a game.
     *
     * @throws Exception
     */
    public function delete(Game $game): JsonResponse
    {
        $userId = Auth::id();
        if ($userId === null) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Check if user is game master
        $userRole = GameUserRole::where('game_id', $game->id)
            ->where('user_id', $userId)
            ->first();
        if (!$userRole || !$userRole->is_game_master) {
            return response()->json(['message' => 'Only game master can delete the game'], 403);
        }

        try {
            DB::transaction(function () use ($game) {
                // Delete related records first
                $game->settings()->delete();
                GameUserRole::where('game_id', $game->id)->delete();
                $game->invitations()->delete();
                $game->delete();
            });

            return response()->json([
                'message' => 'Game deleted successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to delete game',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Kick a player from the game.
     *
     * @throws Exception
     */
    public function kick(Game $game, int $playerId): JsonResponse
    {
        $userId = Auth::id();
        if ($userId === null) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            // Check if user is game master
            $userRole = GameUserRole::where('game_id', $game->id)
                ->where('user_id', $userId)
                ->first();
            if (!$userRole || !$userRole->is_game_master) {
                return response()->json(['message' => 'Only game master can kick players'], 403);
            }

            // Check if game is in progress
            if ($game->status === 'in_progress') {
                return response()->json(['message' => 'Cannot kick players during game'], 403);
            }

            // Check if player exists in game
            $playerRole = GameUserRole::where('game_id', $game->id)
                ->where('user_id', $playerId)
                ->first();
            if (!$playerRole) {
                return response()->json(['message' => 'Player not found in game'], 404);
            }

            // Cannot kick game master
            if ($playerRole->is_game_master) {
                return response()->json(['message' => 'Cannot kick game master'], 403);
            }

            // Remove player from game
            $playerRole->delete();

            return response()->json([
                'message' => 'Player kicked successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to kick player',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Leave a game.
     *
     * @throws Exception
     */
    public function leave(Game $game): JsonResponse
    {
        $userId = Auth::id();
        if ($userId === null) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            // Check if user is in the game
            $userRole = GameUserRole::where('game_id', $game->id)
                ->where('user_id', $userId)
                ->first();

            if (!$userRole) {
                return response()->json(['message' => 'User is not in this game'], 404);
            }

            // Check if user is game master
            if ($userRole->is_game_master) {
                return response()->json(['message' => 'Game master cannot leave the game'], 403);
            }

            // Check if game is in progress
            if ($game->status === 'in_progress') {
                return response()->json(['message' => 'Cannot leave a game in progress'], 403);
            }

            // Remove user from game
            $userRole->delete();

            return response()->json([
                'message' => 'Successfully left the game'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to leave game',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get game settings.
     *
     * Returns the current game settings, including:
     * - use_default: whether to use default role configuration
     * - role_configuration: custom role configuration if not using default (as object)
     * - effective_configuration: the actual role configuration that will be used
     */
    public function getSettings(Game $game): JsonResponse
    {
        $userId = Auth::id();
        if ($userId === null) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Check if user is game master
        $userRole = GameUserRole::where('game_id', $game->id)
            ->where('user_id', $userId)
            ->first();
        if (!$userRole || !$userRole->is_game_master) {
            return response()->json(['message' => 'Only game master can view settings'], 403);
        }

        // Get settings if they exist
        $settings = $game->settings;

        // If game hasn't started and no settings exist, return default configuration
        if (!$settings && $game->status === 'pending') {
            return response()->json([
                'settings' => [
                    'use_default' => true,
                    'role_configuration' => new \stdClass(), // Empty object for Flutter
                    'effective_configuration' => $game->getDefaultRoleConfiguration()
                ]
            ]);
        }

        // Convert role_configuration to object if it's null or empty array
        $roleConfig = $settings->role_configuration;
        if (empty($roleConfig)) {
            $roleConfig = new \stdClass();
        }

        // If settings exist, return them along with the effective configuration
        return response()->json([
            'settings' => [
                'use_default' => $settings->use_default,
                'role_configuration' => $roleConfig,
                'effective_configuration' => $settings->getEffectiveConfiguration()
            ]
        ]);
    }

    /**
     * Update game settings.
     *
     * Allows updating:
     * - use_default: whether to use default role configuration
     * - role_configuration: custom role configuration (used when use_default is false)
     */
    public function updateSettings(GameSettingsRequest $request, Game $game): JsonResponse
    {
        try {
            // Only allow settings update if game hasn't started
            if ($game->status !== 'pending') {
                return response()->json(['message' => 'Cannot update settings after game has started'], 403);
            }

            $settingsData = $request->getSettingsData();

            // Ensure role_configuration is an object
            if (empty($settingsData['role_configuration'])) {
                $settingsData['role_configuration'] = new \stdClass();
            }

            // Update or create settings
            $settings = $game->settings ?? new GameSetting(['game_id' => $game->id]);
            $settings->fill($settingsData);
            $settings->save();

            return response()->json([
                'message' => 'Settings updated successfully',
                'settings' => [
                    'use_default' => $settings->use_default,
                    'role_configuration' => $settings->role_configuration ?: new \stdClass(),
                    'effective_configuration' => $settings->getEffectiveConfiguration()
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to update settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start the game.
     *
     * Creates game settings with role configuration if they don't exist,
     * then starts the game using the effective configuration.
     *
     * @throws Exception
     */
    public function start(GameStartRequest $request, Game $game): JsonResponse
    {
        try {
            /** @var JsonResponse */
            return DB::transaction(function () use ($game): JsonResponse {

                // Attempt to start the game
                $startResult = $game->start();

                if (!$startResult->success) {
                    return response()->json([
                        'message' => $startResult->message
                    ], 400);
                }

                return response()->json([
                    'message' => $startResult->message,
                    'game_id' => $startResult->game_id
                ]);
            });
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to start game',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create or get an invitation link for the game.
     *
     * @throws Exception
     */
    public function createInviteLink(Game $game): JsonResponse
    {
        $userId = Auth::id();
        if ($userId === null) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Ensure only the game creator can create invite links
        if ($game->created_by !== $userId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $invitation = GameInvitation::getOrCreateForGame($game, $userId);

            return response()->json([
                'message' => 'Invite link created successfully',
                'token' => $invitation->token,
                'invite_link' => url("/join-game/{$invitation->token}"), // Base path for deep linking
                'expires_at' => $invitation->expires_at
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to create invite link',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Join a game via invitation token.
     *
     * @throws Exception
     */
    public function joinViaToken(string $token): JsonResponse
    {
        try {
            /** @var JsonResponse */
            return DB::transaction(function () use ($token): JsonResponse {
                $invitation = GameInvitation::where('token', $token)
                    ->where('status', 'active')
                    ->first();

                if (!$invitation) {
                    return response()->json(['message' => 'Invalid invitation token'], 404);
                }

                if ($invitation->hasExpired()) {
                    return response()->json(['message' => 'Invitation has expired'], 403);
                }

                $game = $invitation->game;
                $user = Auth::user();

                if ($user === null) {
                    return response()->json(['message' => 'Unauthorized'], 401);
                }

                // Check if user is already in the game
                if (GameUserRole::where('game_id', $game->id)
                    ->where('user_id', $user->id)
                    ->exists()) {
                    return response()->json(['message' => 'Already joined this game'], 400);
                }

                // Add user to game with joined status
                GameUserRole::create([
                    'game_id' => $game->id,
                    'user_id' => $user->id,
                    'connection_status' => 'joined'  // Override default 'invited' status
                ]);

                return response()->json([
                    'message' => 'Successfully joined the game',
                    'game' => [
                        'id' => $game->id,
                        'name' => $game->name
                    ]
                ]);
            });
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to join game',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Invite a user to the game.
     *
     * @throws Exception
     */
    public function invite(GameInviteRequest $request, Game $game): JsonResponse
    {
        try {
            // Add user to game_user_role with invited status (uses default 'invited' status)
            GameUserRole::create([
                'game_id' => $game->id,
                'user_id' => $request->getUserId(),
                'connection_status' => 'invited'
            ]);

            return response()->json([
                'message' => 'Invitation sent successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to send invitation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Join a game.
     *
     * @throws Exception
     */
    public function join(GameJoinRequest $request, Game $game): JsonResponse
    {
        $user = Auth::user();
        if ($user === null) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            /** @var JsonResponse */
            return DB::transaction(function () use ($game, $user, $request): JsonResponse {
                // Check if user is already in the game
                if (GameUserRole::where('game_id', $game->id)
                    ->where('user_id', $user->id)
                    ->exists()) {
                    return response()->json(['message' => 'Already joined this game'], 400);
                }

                // For private games, verify invitation or join code
                if ($game->is_private) {
                    $credentials = $request->getJoinCredentials();
                    $invitation = GameInvitation::where('game_id', $game->id)
                        ->where('status', 'active')
                        ->first();

                    if (!$invitation || $invitation->hasExpired()) {
                        return response()->json(['message' => 'Invalid or expired invitation'], 403);
                    }

                    if ($credentials['token'] !== $invitation->token && $credentials['join_code'] !== $game->join_code) {
                        return response()->json(['message' => 'Invalid token or join code'], 403);
                    }
                }

                // Add user to game with joined status
                GameUserRole::create([
                    'game_id' => $game->id,
                    'user_id' => $user->id,
                    'connection_status' => 'joined'  // Override default 'invited' status
                ]);

                return response()->json([
                    'message' => 'Successfully joined the game'
                ]);
            });
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to join game',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * List games where user is a participant.
     */
    public function index(GameIndexRequest $request): JsonResponse
    {
        $userId = Auth::id();
        if ($userId === null) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $pagination = $request->getPaginationParams();
        $filters = $request->getFilterParams();

        $query = Game::whereHas('users', function (Builder $query) use ($userId) {
            $query->where('user_id', $userId);
        });

        // Apply status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Apply search filter
        if (!empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        $games = $query->withCount('users')
            ->paginate($pagination['per_page'], ['*'], 'page', $pagination['page']);

        $transformedGames = $games->map(function (Game $game) {
            return [
                'id' => $game->id,
                'name' => $game->name,
                'status' => $game->status,
                'playerCount' => $game->users_count,
                'isPrivate' => $game->is_private,
                'createdBy' => $game->created_by,
            ];
        });

        return response()->json([
            'games' => $transformedGames,
            'meta' => [
                'current_page' => $games->currentPage(),
                'last_page' => $games->lastPage(),
                'per_page' => $games->perPage(),
                'total' => $games->total()
            ]
        ]);
    }

    /**
     * Get game details.
     */
    public function show(GameShowRequest $request, Game $game): JsonResponse
    {
        /** @var ?int */
        $userId = Auth::id();
        if ($userId === null) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Find the current user's game role
        $userGameRole = GameUserRole::where('game_id', $game->id)
            ->where('user_id', $userId)
            ->with('role')
            ->first();

        // Get current user's role details
        $currentUserRole = null;
        if ($userGameRole && $userGameRole->role) {
            $currentUserRole = [
                'id' => $userGameRole->role->id,
                'name' => $userGameRole->role->name,
                'team' => $userGameRole->role->team,
                'description' => $userGameRole->role->description,
                'can_use_night_action' => $userGameRole->role->can_use_night_action
            ];
        }

        // Get available actions for current user if game is in progress
        $availableActions = [];
        if ($game->status === 'in_progress' && $userGameRole && $userGameRole->role) {
            $availableActions = $userGameRole->role->actionTypes()
                ->where('is_day_action', $game->is_day)
                ->get()
                ->map(function ($actionType) {
                    return [
                        'id' => $actionType->id,
                        'name' => $actionType->name,
                        'description' => $actionType->description,
                        'targetType' => $actionType->target_type,
                        'usageLimit' => $actionType->usage_limit,
                    ];
                });
        }

        // Get all game user roles with their relationships
        $gameUserRoles = GameUserRole::where('game_id', $game->id)
            ->with(['user', 'role'])
            ->get();

        // Prepare players with detailed information
        $players = $gameUserRoles->map(function ($gameUserRole) use ($game, $userId) {
            $role = null;

            if ($gameUserRole->role) {
                // Determine role visibility:
                // 1. If game is completed - show all roles
                // 2. If current user - show their role
                // 3. Otherwise - hide role
                $shouldShowRole = $game->status === 'completed' || $gameUserRole->user_id === $userId;

                if (!$shouldShowRole) {
                    $role = [
                        'id' => null,
                        'name' => 'Hidden',
                        'team' => null,
                        'description' => null,
                        'can_use_night_action' => null
                    ];
                } else {
                    $role = [
                        'id' => $gameUserRole->role->id,
                        'name' => $gameUserRole->role->name,
                        'team' => $gameUserRole->role->team,
                        'description' => $gameUserRole->role->description,
                        'can_use_night_action' => $gameUserRole->role->can_use_night_action
                    ];
                }
            }

            return [
                'id' => $gameUserRole->user->id,
                'name' => $gameUserRole->user->name,
                'role' => $role,
                'isGameMaster' => (bool) $gameUserRole->is_game_master,
                'status' => [
                    'connection' => $gameUserRole->connection_status,
                    'user' => $gameUserRole->user_status,
                ],
                'joinedAt' => $gameUserRole->created_at,
            ];
        });

        // Prepare response
        return response()->json([
            'id' => $game->id,
            'name' => $game->name,
            'description' => $game->description,
            'status' => $game->status,
            'isPrivate' => $game->is_private,
            'currentUser' => [
                'id' => $userId,
                'role' => $currentUserRole,
                'isGameMaster' => $userGameRole ? (bool) $userGameRole->is_game_master : false,
                'status' => $userGameRole ? [
                    'connection' => $userGameRole->connection_status,
                    'user' => $userGameRole->user_status,
                ] : null,
                'availableActions' => $availableActions,
            ],
            'gameDetails' => [
                'isDay' => $game->is_day,
                'timezone' => $game->timezone,
                'createdAt' => $game->created_at,
                'startedAt' => $game->started_at,
                'completedAt' => $game->completed_at,
                'winningTeam' => $game->status === 'completed' ? $game->winning_team : null,
            ],
            'players' => $players,
            'playerCount' => $players->count(),
            'minPlayers' => $game->min_players,
        ]);
    }

    /**
     * Perform an action in the game.
     */
    public function performAction(GameActionRequest $request, Game $game): JsonResponse
    {
        $userId = Auth::id();
        if ($userId === null) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Verify game is in progress
        if ($game->status !== 'in_progress') {
            return response()->json(['message' => 'Game is not in progress'], 400);
        }

        // Get user's role
        $userGameRole = GameUserRole::where('game_id', $game->id)
            ->where('user_id', $userId)
            ->with('role')
            ->first();

        if (!$userGameRole || !$userGameRole->role) {
            return response()->json(['message' => 'User has no role in this game'], 403);
        }

        // Get action type from request
        $actionType = $request->getActionType();
        $targets = $request->getTargets();

        // Verify user's role can perform this action
        $canPerformAction = $userGameRole->role->actionTypes()
            ->where('action_types.id', $actionType->id)  // Specify the table name
            ->where('is_day_action', $game->is_day)
            ->exists();

        if (!$canPerformAction) {
            return response()->json(['message' => 'Action not available for this role'], 403);
        }

        try {
            // Create the action
            $action = Action::create([
                'round_id' => $game->current_round_id,
                'executing_player_id' => $userId,
                'target_player_id' => $targets[0] ?? null, // For single target actions
                'action_type_id' => $actionType->id,
                'result_type_id' => $actionType->result_type_id,
                'action_notes' => json_encode($targets), // Store all targets in notes for multi-target actions
                'is_successful' => true // Default to true, can be modified by interference
            ]);

            return response()->json([
                'message' => 'Action performed successfully',
                'action' => [
                    'id' => $action->id,
                    'type' => $actionType->name,
                    'targets' => $targets,
                    'resultType' => $action->resultType ? [
                        'id' => $action->resultType->id,
                        'name' => $action->resultType->name
                    ] : null
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to perform action',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
