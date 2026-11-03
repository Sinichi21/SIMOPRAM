<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $school_id
 * @property int $scout_group_id
 * @property int|null $coach_id
 * @property int|null $gudep_formation_id
 * @property string $scope
 * @property string $position
 * @property string $person_name
 * @property Carbon $started_at
 * @property Carbon|null $ended_at
 * @property string|null $decree_number
 * @property Carbon|null $decree_date
 * @property string|null $notes
 */
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

    /** @return BelongsTo<ScoutGroup, $this> */
    public function scoutGroup(): BelongsTo
    {
        return $this->belongsTo(ScoutGroup::class);
    }

    /** @return BelongsTo<GudepFormation, $this> */
    public function formation(): BelongsTo
    {
        return $this->belongsTo(GudepFormation::class, 'gudep_formation_id');
    }

    /** @return BelongsTo<Coach, $this> */
    public function coach(): BelongsTo
    {
        return $this->belongsTo(Coach::class);
    }
}
