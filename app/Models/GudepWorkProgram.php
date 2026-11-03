<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $school_id
 * @property int $scout_group_id
 * @property int $academic_year_id
 * @property int|null $gudep_formation_id
 * @property int|null $activity_id
 * @property string $title
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $planned_for
 */
class GudepWorkProgram extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'scout_group_id', 'academic_year_id',
        'gudep_formation_id', 'activity_id', 'title', 'objective',
        'planned_for', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return ['planned_for' => 'date'];
    }

    /** @return BelongsTo<ScoutGroup, $this> */
    public function scoutGroup(): BelongsTo
    {
        return $this->belongsTo(ScoutGroup::class);
    }

    /** @return BelongsTo<Activity, $this> */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    /** @return BelongsTo<GudepFormation, $this> */
    public function formation(): BelongsTo
    {
        return $this->belongsTo(GudepFormation::class, 'gudep_formation_id');
    }
}
