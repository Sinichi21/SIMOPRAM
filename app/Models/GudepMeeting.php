<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GudepMeeting extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'scout_group_id', 'academic_year_id', 'activity_id', 'title',
        'held_at', 'location', 'chairperson', 'minute_taker', 'attendees',
        'agenda', 'minutes', 'status', 'finalized_at', 'recorded_by',
    ];

    protected function casts(): array
    {
        return ['held_at' => 'datetime', 'finalized_at' => 'datetime'];
    }

    /** @return HasMany<GudepMeetingDecision, $this> */
    public function decisions(): HasMany
    {
        return $this->hasMany(GudepMeetingDecision::class);
    }
}
