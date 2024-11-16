<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\GameInvitation;
use App\Models\Role;
use App\Http\Requests\Game\GameCreateRequest;
use App\Http\Requests\Game\GameJoinRequest;
use App\Http\Requests\Game\GameInviteRequest;
use App\Http\Requests\Game\GameStartRequest;
use App\Http\Requests\Game\GameIndexRequest;
use App\Http\Requests\Game\GameShowRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
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
     * @throws \Exception
     */
    public function create(GameCreateRequest $request): JsonResponse
    {
        try {
            $userId = Auth::id();
            if ($userId === null) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            /** @var Game */
            $game = DB::transaction(function () use ($request, $userId): Game {
                /** @var array<string, mixed> */
                $gameData = $request->getGameData();

                /** @var Game */
                $game = Game::create([
                    'created_by' => $userId,
                    'name' => $gameData['name'],
                    'description' => $gameData['description'],
                    'role_configuration' => '{}',
                    'is_private' => $gameData['is_private'],
                    'is_day' => true,
                    'join_code' => $gameData['is_private'] ? Game::generateJoinCode() : null,
                    'timezone' => $gameData['timezone'],
                    'status' => 'pending'
                ]);

                // Add game creator to game_user_role with game master status
                $game->users()->attach($userId, [
                    'connection_status' => 'joined',
                    'is_game_master' => true
                ]);

                return $game;
            });

            return response()->json([
                'message' => 'Game created successfully',
                'gameId' => $game->id,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create game',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start the game with role configuration.
     *
     * @throws \Exception
     */
    public function start(GameStartRequest $request, Game $game): JsonResponse
    {
        try {
            /** @var JsonResponse */
            return DB::transaction(function () use ($request, $game): JsonResponse {
                // Update game with role configuration
                $game->update([
                    'role_configuration' => $request->getRoleConfiguration()
                ]);

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
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to start game',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create or get an invitation link for the game.
     *
     * @throws \Exception
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
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create invite link',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Join a game via invitation token.
     *
     * @throws \Exception
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
                if ($game->users()->where('user_id', $user->id)->exists()) {
                    return response()->json(['message' => 'Already joined this game'], 400);
                }

                // Add user to game with joined status
                $game->users()->attach($user->id, [
                    'connection_status' => 'joined'
                ]);

                return response()->json([
                    'message' => 'Successfully joined the game',
                    'game' => [
                        'id' => $game->id,
                        'name' => $game->name
                    ]
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to join game',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Invite a user to the game.
     *
     * @throws \Exception
     */
    public function invite(GameInviteRequest $request, Game $game): JsonResponse
    {
        try {
            // Add user to game_user_role with invited status
            $game->users()->attach($request->getUserId(), [
                'connection_status' => 'invited'
            ]);

            return response()->json([
                'message' => 'Invitation sent successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send invitation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Join a game.
     *
     * @throws \Exception
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
                if ($game->users()->where('user_id', $user->id)->exists()) {
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
                $game->users()->attach($user->id, [
                    'connection_status' => 'joined'
                ]);

                return response()->json([
                    'message' => 'Successfully joined the game'
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to join game',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * List available games.
     */
    public function index(GameIndexRequest $request): JsonResponse
    {
        $userId = Auth::id();
        if ($userId === null) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $pagination = $request->getPaginationParams();
        $filters = $request->getFilterParams();

        $query = Game::where(function (Builder $query) use ($userId) {
                $query->where('is_private', false)
                    ->orWhere('created_by', $userId)
                    ->orWhereHas('invitations', function (Builder $subQuery) {
                        $subQuery->where('status', 'active');
                    });
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
        $userGameRole = $game->users()
            ->where('user_id', $userId)
            ->first();

        // Prepare players with detailed information
        $players = $game->users->map(function ($user) use ($game) {
            $pivot = $user->pivot;
            $role = null;

            if ($pivot->role_id) {
                $role = Role::find($pivot->role_id);
                // Only show role details if game is completed or user is game master
                if ($game->status !== 'completed' && !$pivot->is_game_master) {
                    $role = ['name' => 'Hidden'];
                }
            }

            return [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $role ? [
                    'name' => $role->name,
                    'team' => $game->status === 'completed' ? $role->team : null,
                ] : null,
                'isGameMaster' => (bool) $pivot->is_game_master,
                'status' => [
                    'connection' => $pivot->connection_status,
                    'user' => $pivot->user_status,
                ],
                'joinedAt' => $pivot->created_at,
            ];
        });

        // Prepare response
        return response()->json([
            'id' => $game->id,
            'name' => $game->name,
            'description' => $game->description,
            'status' => $game->status,
            'isPrivate' => $game->is_private,
            'currentUserRole' => [
                'isGameMaster' => $userGameRole ? (bool) $userGameRole->pivot->is_game_master : false,
                'status' => $userGameRole ? [
                    'connection' => $userGameRole->pivot->connection_status,
                    'user' => $userGameRole->pivot->user_status,
                ] : null,
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
}