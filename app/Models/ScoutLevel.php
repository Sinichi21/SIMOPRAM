<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ScoutLevel extends Model
{
    protected $fillable = [
        'code',
        'name',
        'minimum_age',
        'maximum_age',
        'sort_order',
    ];

    public function scoutUnits()
    {
        return $this->hasMany(ScoutUnit::class);
    }

    public function activities(): BelongsToMany
    {
        return $this->belongsToMany(
            Activity::class,
            'activity_scout_level'
        )->withTimestamps();
    }
}
