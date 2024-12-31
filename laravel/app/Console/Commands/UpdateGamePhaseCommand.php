<?php

namespace App\Console\Commands;

use App\Events\GamePhaseChanged;
use App\Models\Game;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateGamePhaseCommand extends Command
{
    protected $signature = 'game:update-phase';
    protected $description = 'Updates game phases based on time and conditions';

    public function handle(): void
    {
        // Get all active games
        $games = Game::where('status', 'in_progress')->get();
        
        foreach ($games as $game) {
            // Handle preparation phase transition
            if ($game->isPreparation() && $game->canExitPreparationPhase()) {
                $game->phase = 'day';
                $game->save();
                event(new GamePhaseChanged($game->id, 'day'));
                $this->info("Game {$game->id}: Exited preparation phase");
                continue; // Skip day/night check for this iteration
            }

            // Handle voting phase transition during day
            if ($game->isDay() && $game->shouldStartVoting()) {
                $game->phase = 'voting';
                $game->save();
                event(new GamePhaseChanged($game->id, 'voting'));
                $this->info("Game {$game->id}: Started voting phase");
                continue; // Skip day/night check for this iteration
            }

            // Only process day/night changes for games in day or night phase
            if (!$game->isDay() && !$game->isNight()) {
                continue;
            }

            $now = Carbon::now($game->timezone);
            
            // Create Carbon instances for time boundaries
            $morningStart = Carbon::create($now->year, $now->month, $now->day, 7, 30, 0, $game->timezone);
            $eveningStart = Carbon::create($now->year, $now->month, $now->day, 19, 30, 0, $game->timezone);
            
            // Determine if it should be day
            $shouldBeDay = $now->between($morningStart, $eveningStart);
            
            // Update phase if needed
            if (($shouldBeDay && $game->isNight()) || (!$shouldBeDay && $game->isDay())) {
                $newPhase = $shouldBeDay ? 'day' : 'night';
                $game->phase = $newPhase;
                $game->save();
                
                // Broadcast the state change
                event(new GamePhaseChanged($game->id, $newPhase));
                
                $this->info("Game {$game->id}: Changed to {$newPhase}");
            }
        }
    }
}
