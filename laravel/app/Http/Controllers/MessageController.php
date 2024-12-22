<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Http\Requests\Game\MessageCreateRequest;
use App\Http\Requests\Game\MessageIndexRequest;
use App\Models\Game;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;

class MessageController extends BaseController
{
    public function index(Game $game, MessageIndexRequest $request): JsonResponse
    {
        $userId = Auth::id();
        $isNightChat = $request->boolean('night_chat');

        // Check if user can view night chat
        if ($isNightChat) {
            $userRole = $game->gameUserRoles()
                ->where('user_id', $userId)
                ->with('role')
                ->first();

            if (!$userRole || !in_array($userRole->role->team, [Game::TEAM_CATS, Game::TEAM_SERIAL_KILLER])) {
                return response()->json([
                    'message' => 'You are not allowed to view night chat'
                ], 403);
            }
        }

        $messages = $game->messages()
            ->when($isNightChat, function ($query) {
                $query->where('is_night_chat', true);
            })
            ->when(!$isNightChat, function ($query) {
                $query->where('is_night_chat', false);
            })
            ->with('user:id,name')
            ->latest()
            ->paginate(50);

        return response()->json($messages);
    }

    public function store(Game $game, MessageCreateRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $userId = Auth::id();

        // Check if user can participate in night chat
        if ($validated['is_night_chat']) {
            $userRole = $game->gameUserRoles()
                ->where('user_id', $userId)
                ->with('role')
                ->first();

            if (!$userRole || !in_array($userRole->role->team, [Game::TEAM_CATS, Game::TEAM_SERIAL_KILLER])) {
                return response()->json([
                    'message' => 'You are not allowed to participate in night chat'
                ], 403);
            }
        }

        $message = $game->messages()->create([
            'user_id' => $userId,
            'content' => $validated['content'],
            'is_night_chat' => $validated['is_night_chat'],
        ]);

        $message->load('user:id,name');

        // Broadcast the message
        broadcast(new MessageSent($message))->toOthers();

        return response()->json($message, 201);
    }
}
