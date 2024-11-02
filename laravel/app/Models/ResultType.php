<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResultType extends Model
{
    use HasFactory;

    protected $fillable = [
        'result'
    ];

    /**
     * Get the actions with this result type.
     */
    public function actions(): HasMany
    {
        return $this->hasMany(Action::class);
    }
}
