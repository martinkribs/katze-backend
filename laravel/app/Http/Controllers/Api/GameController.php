<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\GameInvitation;
use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class GameController extends Controller
{
    /**
     * Create a new game.
     */
    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_private' => 'boolean',
            'timezone' => 'required|string|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $game = DB::transaction(function () use ($request) {
                $game = Game::create([
                    'created_by' => Auth::id(),
                    'name' => $request->name,
                    'description' => $request->description,
                    'role_configuration' => '{}',
                    'is_private' => $request->is_private ?? false,
                    'is_day' => true,
                    'join_code' => $request->is_private ? Game::generateJoinCode() : null,
                    'timezone' => $request->timezone,
                    'status' => 'waiting'
                ]);

                // Add game creator to game_user_role with game master status
                $game->users()->attach(Auth::id(), [
                    'connection_status' => 'joined',
                    'is_game_master' => true
                ]);

                return $game;
            });

            return response()->json([
                'message' => 'Game created successfully',
                'game' => $game->load('creator'),
                'join_code' => $game->is_private ? $game->join_code : null
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
     */
    public function start(Request $request, Game $game): JsonResponse
    {
        // Ensure only the game creator can start the game
        if ($game->created_by !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'role_configuration' => 'required|array',
            'role_configuration.*' => 'integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            return DB::transaction(function () use ($request, $game) {
                // Update game with role configuration
                $game->update([
                    'role_configuration' => $request->role_configuration
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
     * Invite a user to the game.
     */
    public function invite(Request $request, Game $game): JsonResponse
    {
        // Ensure only the game creator can invite
        if ($game->created_by !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'user_id' => [
                'required',
                'exists:users,id',
                // Prevent duplicate invitations
                Rule::unique('game_invitations')->where(function ($query) use ($game) {
                    return $query->where('game_id', $game->id)
                                ->where('status', 'pending');
                }),
                // Prevent inviting if already in the game
                function ($attribute, $value, $fail) use ($game) {
                    if ($game->users()->where('user_id', $value)->exists()) {
                        $fail('User is already in the game');
                    }
                }
            ]
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $invitation = GameInvitation::create([
                'game_id' => $game->id,
                'invited_by' => Auth::id(),
                'user_id' => $request->user_id,
                'status' => 'pending',
                'expires_at' => Carbon::now()->addDays(1)
            ]);

            // Add user to game_user_role with invited status
            $game->users()->attach($request->user_id, [
                'connection_status' => 'invited'
            ]);

            return response()->json([
                'message' => 'Invitation sent successfully',
                'invitation' => $invitation
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
     */
    public function join(Request $request, Game $game): JsonResponse
    {
        $user = Auth::user();

        try {
            return DB::transaction(function () use ($game, $user, $request) {
                // Check if user is already in the game
                if ($game->users()->where('user_id', $user->id)->exists()) {
                    return response()->json(['message' => 'Already joined this game'], 400);
                }

                // For private games, verify invitation or join code
                if ($game->is_private) {
                    $invitation = GameInvitation::where('game_id', $game->id)
                        ->where('user_id', $user->id)
                        ->where('status', 'pending')
                        ->first();

                    if (!$invitation && $request->join_code !== $game->join_code) {
                        return response()->json(['message' => 'Invalid invitation or join code'], 403);
                    }

                    // Mark invitation as accepted if exists
                    if ($invitation) {
                        $invitation->update(['status' => 'accepted']);
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
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        $query = Game::where(function ($query) {
                $query->where('is_private', false)
                    ->orWhere('created_by', Auth::id())
                    ->orWhereHas('invitations', function ($subQuery) {
                        $subQuery->where('user_id', Auth::id())
                            ->where('status', 'pending');
                    });
            });

        $games = $query->withCount('users')
            ->paginate($perPage, ['*'], 'page', $page);

        $transformedGames = $games->map(function ($game) {
            return [
                'id' => $game->id,
                'name' => $game->name,
                'status' => $game->status,
                'playerCount' => $game->users_count
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
    public function show(Game $game): JsonResponse
    {
        // Find the current user's game role
        $userGameRole = $game->users()
            ->where('user_id', Auth::id())
            ->first();

        // Prepare players with detailed information
        $players = $game->users->map(function ($user) {
            $pivot = $user->pivot;
            return [
                'name' => $user->name,
                'role' => $pivot->role_id ? Role::find($pivot->role_id)->name : null,
                'isGameMaster' => $pivot->is_game_master,
                'userStatus' => $pivot->user_status
            ];
        });

        // Prepare response
        return response()->json([
            'name' => $game->name,
            'isGameMaster' => $userGameRole ? $userGameRole->pivot->is_game_master : false,
            'players' => $players,
            'gameStatus' => $game->status,
        ]);
    }
}
