<?php

namespace App\Events;

use App\Models\Game;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameDayNightChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $gameId,
        public bool $isDay
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('game.' . $this->gameId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'game.daynight.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'is_day' => $this->isDay
        ];
    }
}
