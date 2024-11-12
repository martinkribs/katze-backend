<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'invited_by',
        'token',
        'status',
        'expires_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Get the game that the invitation is for.
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * Get the user who sent the invitation.
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Check if the invitation has expired.
     */
    public function hasExpired(): bool
    {
        return $this->expires_at->isPast() || $this->status === 'expired';
    }

    /**
     * Mark the invitation as expired.
     */
    public function markAsExpired(): void
    {
        $this->update(['status' => 'expired']);
    }

    /**
     * Get or create an active invitation for a game.
     */
    public static function getOrCreateForGame(Game $game, int $invitedBy): self
    {
        $invitation = self::where('game_id', $game->id)
            ->where('status', 'active')
            ->first();

        if ($invitation && !$invitation->hasExpired()) {
            return $invitation;
        }

        // If there's an expired invitation, mark it as expired
        if ($invitation) {
            $invitation->markAsExpired();
        }

        // Create new invitation
        return self::create([
            'game_id' => $game->id,
            'invited_by' => $invitedBy,
            'token' => \Illuminate\Support\Str::uuid(),
            'status' => 'active',
            'expires_at' => now()->addDays(1)
        ]);
    }
}
