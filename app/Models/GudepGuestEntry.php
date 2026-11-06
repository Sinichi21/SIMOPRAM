<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class GudepGuestEntry extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'scout_group_id', 'academic_year_id', 'visited_at',
        'guest_name', 'organization', 'purpose', 'received_by', 'notes', 'recorded_by',
    ];

    protected function casts(): array
    {
        return ['visited_at' => 'datetime'];
    }
}
