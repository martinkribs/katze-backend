<?php

namespace App\Http\Controllers;

use App\Models\Action;
use App\Models\Game;
use App\Models\Player;
use App\Models\Round;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ActionController extends Controller
{
    public function perform(Request $request, Game $game)
    {
        $request->validate([
            'action_type_id' => 'required|exists:action_types,id',
            'target_player_ids' => 'required|array',
            'target_player_ids.*' => 'exists:players,id'
        ]);

        $player = $game->players()->where('user_id', auth()->id)->firstOrFail();
        $currentRound = $game->rounds()->latest()->firstOrFail();
        $actionType = \App\Models\ActionType::findOrFail($request->action_type_id);

        // Validate if action is allowed
        if (!$this->isActionAllowed($player, $actionType, $currentRound)) {
            return response()->json(['error' => 'Action not allowed'], 403);
        }

        // Validate target count
        if (!$this->validateTargets($actionType, $request->target_player_ids)) {
            return response()->json(['error' => 'Invalid target count'], 400);
        }

        return Action::transaction(function () use ($game, $player, $actionType, $request, $currentRound) {
            // Create the action record
            $action = Action::create([
                'player_id' => $player->id,
                'action_type_id' => $actionType->id,
                'round_id' => $currentRound->id,
                'game_id' => $game->id
            ]);

            // Process action effects
            $this->processActionEffects($action, $request->target_player_ids);

            // Check if game is over
            $this->checkGameEnd($game);

            return response()->json(['message' => 'Action performed successfully']);
        });
    }

    private function isActionAllowed(Player $player, \App\Models\ActionType $actionType, Round $currentRound): bool
    {
        if (!$player->is_alive) {
            return false;
        }

        // Check if action is appropriate for the time of day
        if ($actionType->name === 'Cat Kill' && $currentRound->is_day) {
            return false;
        }

        // Check if player has already used their limited actions
        if ($actionType->usage_limit !== null) {
            $usedCount = Action::where('player_id', $player->id)
                ->where('action_type_id', $actionType->id)
                ->count();
            
            if ($usedCount >= $actionType->usage_limit) {
                return false;
            }
        }

        // Check role-specific permissions
        return $this->checkRolePermission($player->role, $actionType->name);
    }

    private function checkRolePermission(string $role, string $actionName): bool
    {
        $permissions = [
            'cat' => ['Cat Kill', 'Civilian Vote'],
            'seer' => ['Seer Reveal', 'Civilian Vote'],
            'witch' => ['Witch Heal', 'Witch Kill', 'Civilian Vote'],
            'detective' => ['Detective Investigate', 'Civilian Vote'],
            'villager' => ['Civilian Vote']
        ];

        return isset($permissions[$role]) && in_array($actionName, $permissions[$role]);
    }

    private function validateTargets(\App\Models\ActionType $actionType, array $targetIds): bool
    {
        $targetCount = count($targetIds);

        return match ($actionType->target_type) {
            'single' => $targetCount === 1,
            'multiple' => $targetCount >= 1 && $targetCount <= 2,
            'self' => $targetCount === 0,
            'none' => $targetCount === 0,
            default => false,
        };
    }

    private function processActionEffects(Action $action, array $targetPlayerIds)
    {
        foreach ($targetPlayerIds as $targetPlayerId) {
            $targetPlayer = Player::find($targetPlayerId);
            
            switch ($action->actionType->effect) {
                case 'kill':
                    $targetPlayer->update(['is_alive' => false]);
                    break;
                case 'heal':
                    $targetPlayer->update(['is_alive' => true]);
                    break;
                case 'reveal':
                    // Store the revelation in the action's metadata for the client to handle
                    $action->update([
                        'metadata' => [
                            'revealed_role' => $targetPlayer->role
                        ]
                    ]);
                    break;
                case 'protect':
                    $targetPlayer->update(['special_status' => 'protected']);
                    break;
            }
        }
    }

    private function checkGameEnd(Game $game)
    {
        $alivePlayers = $game->players()->where('is_alive', true)->get();
        $aliveCats = $alivePlayers->where('role', 'cat')->count();
        $aliveVillagers = $alivePlayers->whereNotIn('role', ['cat'])->count();

        if ($aliveCats === 0) {
            // Villagers win
            $game->update([
                'status' => 'finished',
                'end_time' => Carbon::now(),
                'metadata' => ['winner' => 'villagers']
            ]);
        } elseif ($aliveCats >= $aliveVillagers) {
            // Cats win
            $game->update([
                'status' => 'finished',
                'end_time' => Carbon::now(),
                'metadata' => ['winner' => 'cats']
            ]);
        }
    }
}
