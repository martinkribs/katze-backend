<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Player;
use App\Models\Round;
use Carbon\Carbon;
use Illuminate\Http\Request;

class GameController extends Controller
{

    public function create(Request $request)
    {
        $request->validate([
            'player_ids' => 'required|array|min:6',
            'player_ids.*' => 'exists:users,id'
        ]);

        return Game::transaction(function () use ($request) {
            $game = Game::create([
                'status' => 'waiting',
                'start_time' => null,
                'end_time' => null
            ]);

            $playerCount = count($request->player_ids);
            $roles = $this->assignRoles($playerCount);
            
            // Assign roles randomly to players
            $shuffledRoles = collect($roles)->shuffle();
            foreach ($request->player_ids as $index => $userId) {
                Player::create([
                    'user_id' => $userId,
                    'game_id' => $game->id,
                    'role' => $shuffledRoles[$index],
                    'is_alive' => true
                ]);
            }

            return response()->json([
                'message' => 'Game created successfully',
                'game_id' => $game->id
            ]);
        });
    }

    public function start(Game $game)
    {
        if ($game->status !== 'waiting') {
            return response()->json(['error' => 'Game cannot be started'], 400);
        }

        $now = Carbon::now();
        $game->update([
            'status' => 'in_progress',
            'start_time' => $now
        ]);

        // Create first round based on current time
        $isDay = $this->isDaytime($now);
        $roundEndTime = $this->calculateNextTransitionTime($now);
        
        Round::create([
            'game_id' => $game->id,
            'round_number' => 1,
            'start_time' => $now,
            'end_time' => $roundEndTime,
            'is_day' => $isDay
        ]);

        return response()->json(['message' => 'Game started successfully']);
    }

    public function getCurrentState(Game $game)
    {
        $currentRound = $game->rounds()->latest()->first();
        $now = Carbon::now();

        if ($currentRound && $now->gt($currentRound->end_time)) {
            // Create new round if current round has ended
            $newRoundNumber = $currentRound->round_number + 1;
            $isDay = $this->isDaytime($now);
            $roundEndTime = $this->calculateNextTransitionTime($now);

            $currentRound = Round::create([
                'game_id' => $game->id,
                'round_number' => $newRoundNumber,
                'start_time' => $now,
                'end_time' => $roundEndTime,
                'is_day' => $isDay
            ]);
        }

        return response()->json([
            'game_status' => $game->status,
            'current_round' => $currentRound,
            'alive_players' => $game->players()->where('is_alive', true)->get(),
            'is_day' => $currentRound ? $currentRound->is_day : null,
            'time_until_next_phase' => $currentRound ? $now->diffInSeconds($currentRound->end_time) : null
        ]);
    }

    private function isDaytime(Carbon $time): bool
    {
        $hour = $time->hour;
        return $hour >= 6 && $hour < 20; // Day is from 6 AM to 8 PM
    }

    private function calculateNextTransitionTime(Carbon $fromTime): Carbon
    {
        $hour = $fromTime->hour;
        
        if ($hour >= 20 || $hour < 6) {
            // It's night, next transition is at 6 AM
            return $fromTime->copy()->addDay()->setHour(6)->setMinute(0)->setSecond(0);
        } else {
            // It's day, next transition is at 8 PM
            return $fromTime->copy()->setHour(20)->setMinute(0)->setSecond(0);
        }
    }

    private function assignRoles(int $playerCount): array
    {
        $roles = [];
        
        // Calculate number of cats (werewolves)
        $catCount = max(1, floor($playerCount / 4));
        
        // Always include one seer and one witch
        $roles[] = 'seer';
        $roles[] = 'witch';
        
        // Add cats
        for ($i = 0; $i < $catCount; $i++) {
            $roles[] = 'cat';
        }
        
        // Add detective for games with 8+ players
        if ($playerCount >= 8) {
            $roles[] = 'detective';
        }
        
        // Fill remaining slots with villagers
        while (count($roles) < $playerCount) {
            $roles[] = 'villager';
        }
        
        return $roles;
    }
}
