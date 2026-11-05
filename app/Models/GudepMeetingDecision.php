<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GudepMeetingDecision extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'scout_group_id', 'gudep_meeting_id',
        'decision', 'person_responsible', 'due_at', 'status',
    ];

    protected function casts(): array
    {
        return ['due_at' => 'date'];
    }

    /** @return BelongsTo<GudepMeeting, $this> */
    public function meeting(): BelongsTo
    {
        return $this->belongsTo(GudepMeeting::class, 'gudep_meeting_id');
    }
}
