<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = ['round_id', 'phase_id', 'event_description'];

    public function round() {
        return $this->belongsTo(Round::class);
    }

    public function phase() {
        return $this->belongsTo(Phase::class);
    }
}
