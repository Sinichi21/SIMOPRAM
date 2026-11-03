<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $school_id
 * @property int $scout_group_id
 * @property int $student_id
 * @property string $category
 * @property string $requirement_code
 * @property string $requirement_name
 * @property string $status
 */
class GudepCompetencyRecord extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'scout_group_id', 'student_id', 'scout_level_id',
        'coach_id', 'category', 'requirement_code', 'requirement_name',
        'status', 'assessed_at', 'notes',
    ];

    protected function casts(): array
    {
        return ['assessed_at' => 'date'];
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<Coach, $this> */
    public function coach(): BelongsTo
    {
        return $this->belongsTo(Coach::class);
    }

    /** @return BelongsTo<ScoutLevel, $this> */
    public function scoutLevel(): BelongsTo
    {
        return $this->belongsTo(ScoutLevel::class);
    }
}
