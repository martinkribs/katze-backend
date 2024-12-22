<?php

namespace App\Events;

use App\Models\Game;
use App\Models\Message;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageReceived implements ShouldQueue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Game $game,
        public User $user,
        public string $content,
        public bool $isNightChat
    ) {}

    public function handle(): void
    {
        // Check if user can participate in night chat
        if ($this->isNightChat) {
            $userRole = $this->game->gameUserRoles()
                ->where('user_id', $this->user->id)
                ->with('role')
                ->first();

            if (!$userRole || !in_array($userRole->role->team, [Game::TEAM_CATS, Game::TEAM_SERIAL_KILLER])) {
                return;
            }
        }

        // Create and save the message
        $message = $this->game->messages()->create([
            'user_id' => $this->user->id,
            'content' => $this->content,
            'is_night_chat' => $this->isNightChat,
        ]);

        $message->load('user:id,name');
    }
}
