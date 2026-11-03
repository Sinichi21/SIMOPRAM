<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GudepOfficer extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'scout_group_id', 'gudep_formation_id', 'coach_id', 'scope', 'position',
        'person_name', 'started_at', 'ended_at', 'decree_number',
        'decree_date', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'date',
            'ended_at' => 'date',
            'decree_date' => 'date',
        ];
    }

    public function scoutGroup(): BelongsTo
    {
        return $this->belongsTo(ScoutGroup::class);
    }

    public function formation(): BelongsTo
    {
        return $this->belongsTo(GudepFormation::class, 'gudep_formation_id');
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(Coach::class);
    }
}
