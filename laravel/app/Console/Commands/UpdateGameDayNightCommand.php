<?php

namespace App\Console\Commands;

use App\Events\GameDayNightChanged;
use App\Models\Game;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateGameDayNightCommand extends Command
{
    protected $signature = 'game:update-day-night';
    protected $description = 'Updates the day/night state of active games based on time';

    public function handle(): void
    {
        $games = Game::where('status', 'in_progress')->get();
        
        foreach ($games as $game) {
            $now = Carbon::now($game->timezone);
            
            // Create Carbon instances for time boundaries
            $morningStart = Carbon::create($now->year, $now->month, $now->day, 7, 30, 0, $game->timezone);
            $eveningStart = Carbon::create($now->year, $now->month, $now->day, 19, 30, 0, $game->timezone);
            
            // Determine if it should be day
            $shouldBeDay = $now->between($morningStart, $eveningStart);
            
            // Only update if the state needs to change
            if ($game->is_day !== $shouldBeDay) {
                $game->is_day = $shouldBeDay;
                $game->save();
                
                // Broadcast the state change
                event(new GameDayNightChanged($game->id, $shouldBeDay));
                
                $this->info("Game {$game->id}: Changed to " . ($shouldBeDay ? 'day' : 'night'));
            }
        }
    }
}
