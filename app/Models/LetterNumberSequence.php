<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class LetterNumberSequence extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'direction', 'year', 'last_number'];
}
