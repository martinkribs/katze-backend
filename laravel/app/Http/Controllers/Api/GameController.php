<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\GameInvitation;
use App\Models\Player;
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
            'role_configuration' => 'sometimes|array',
            'role_configuration.*' => 'integer|min:0',
            'min_players' => 'required|integer|min:7|max:15',
            'max_players' => 'required|integer|min:7|max:15|gte:min_players',
            'day_duration_minutes' => 'integer|min:5|max:30|default:10',
            'night_duration_minutes' => 'integer|min:3|max:15|default:5',
            'is_private' => 'boolean',
            'timezone' => 'required|string|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $game = DB::transaction(function () use ($request) {
                // Prepare role configuration
                $roleConfiguration = $request->role_configuration ?? null;

                $game = Game::create([
                    'created_by' => Auth::id(),
                    'name' => $request->name,
                    'description' => $request->description,
                    'role_configuration' => $roleConfiguration,
                    'min_players' => $request->min_players,
                    'max_players' => $request->max_players,
                    'day_duration_minutes' => $request->day_duration_minutes ?? 10,
                    'night_duration_minutes' => $request->night_duration_minutes ?? 5,
                    'is_private' => $request->is_private ?? false,
                    'join_code' => $request->is_private ? Game::generateJoinCode() : null,
                    'timezone' => $request->timezone,
                    'status' => 'waiting'
                ]);

                // Create a game instance
                $gameInstance = $game->createInstance();

                // Create a player for the game creator as game master
                Player::create([
                    'game_instance_id' => $gameInstance->id,
                    'user_id' => Auth::id(),
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
     * Invite a player to the game.
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
                    if ($game->players()->where('user_id', $value)->exists()) {
                        $fail('User is already in the game');
                    }
                }
            ]
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check game capacity
        if ($game->isFullyBooked()) {
            return response()->json(['message' => 'Game is fully booked'], 400);
        }

        try {
            $invitation = GameInvitation::create([
                'game_id' => $game->id,
                'invited_by' => Auth::id(),
                'user_id' => $request->user_id,
                'status' => 'pending',
                'expires_at' => Carbon::now()->addDays(1)
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
                if ($game->players()->where('user_id', $request->user()->id)->exists()) {
                    return response()->json(['message' => 'Already joined this game'], 400);
                }

                // Check game capacity
                if ($game->isFullyBooked()) {
                    return response()->json(['message' => 'Game is fully booked'], 400);
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

                // Get the current game instance (or create if not exists)
                $gameInstance = $game->gameInstances()->where('status', 'waiting')->first()
                    ?? $game->createInstance();

                // Create player
                Player::create([
                    'game_instance_id' => $gameInstance->id,
                    'user_id' => $user->id,
                    'is_game_master' => false
                ]);

                return response()->json([
                    'message' => 'Successfully joined the game',
                    'game_instance_id' => $gameInstance->id
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
     * Start the game.
     */
    public function start(Game $game): JsonResponse
    {
        // Ensure only the game creator can start the game
        if ($game->created_by !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            return DB::transaction(function () use ($game) {
                // Attempt to start the game
                $gameInstance = $game->start();

                if (!$gameInstance) {
                    return response()->json([
                        'message' => 'Cannot start game. Need between ' .
                            $game->min_players . ' and ' . $game->max_players . ' players.'
                    ], 400);
                }

                return response()->json([
                    'message' => 'Game started successfully',
                    'game_instance_id' => $gameInstance->id,
                    'roles' => $gameInstance->players->load('role')->map(function ($player) {
                        return [
                            'user_id' => $player->user_id,
                            'role_name' => $player->role->name,
                            'team' => $player->role->team
                        ];
                    })
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
     * List available games.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Game::where('status', 'waiting')
            ->where(function ($query) {
                $query->where('is_private', false)
                    ->orWhere('created_by', Auth::id())
                    ->orWhereHas('invitations', function ($subQuery) {
                        $subQuery->where('user_id', Auth::id())
                            ->where('status', 'pending');
                    });
            });

        $games = $query->with('creator')
            ->withCount('players')
            ->paginate(10);

        return response()->json($games);
    }

    /**
     * Get game details.
     */
    public function show(Game $game): JsonResponse
    {
        $game->load([
            'creator',
            'gameInstances' => function ($query) {
                $query->where('status', 'waiting');
            },
            'gameInstances.players.user'
        ]);

        // Only show roles if game is completed or user is game master
        $currentInstance = $game->getCurrentInstance();
        if ($currentInstance &&
            ($currentInstance->status === 'completed' ||
             $currentInstance->gameMaster()?->user_id === Auth::id())) {
            $game->load('gameInstances.players.role');
        }

        return response()->json($game);
    }
}
