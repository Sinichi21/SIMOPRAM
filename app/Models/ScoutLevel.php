<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}