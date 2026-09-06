<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Database\Factories\ActivityParticipationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityParticipation extends Model
{
    use BelongsToSchool;

    /** @use HasFactory<ActivityParticipationFactory> */
    use HasFactory;

    protected $fillable = ['assessment_config_id', 'activity_id', 'student_id', 'points', 'notes', 'entered_by'];

    protected function casts(): array
    {
        return ['points' => 'float'];
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
