<?php

namespace App\Http\Controllers;

use App\Http\Requests\Game\MessageIndexRequest;
use App\Models\Game;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;

class MessageController extends BaseController
{
    public function index(Game $game, MessageIndexRequest $request): JsonResponse
    {
        $userId = Auth::id();
        $isNightChat = $request->boolean('night_chat');
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 50);

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
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json($messages);
    }
}
