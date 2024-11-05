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
        'user_id',
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
     * Get the user who was invited.
     */
    public function invitee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Check if the invitation has expired.
     */
    public function hasExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Accept the invitation.
     */
    public function accept(): bool
    {
        if ($this->hasExpired() || $this->status !== 'pending') {
            return false;
        }

        if ($this->game->isFullyBooked()) {
            $this->update(['status' => 'expired']);
            return false;
        }

        $this->update(['status' => 'accepted']);
        return true;
    }

    /**
     * Decline the invitation.
     */
    public function decline(): void
    {
        $this->update(['status' => 'declined']);
    }
}
