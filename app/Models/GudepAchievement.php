<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $school_id
 * @property int $scout_group_id
 * @property int|null $student_id
 * @property int|null $activity_id
 * @property string $kind
 * @property string $title
 */
class GudepAchievement extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'scout_group_id', 'academic_year_id', 'student_id', 'activity_id',
        'kind', 'title', 'awarded_at', 'issuer', 'reference_number', 'notes',
    ];

    protected function casts(): array
    {
        return ['awarded_at' => 'date'];
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<Activity, $this> */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
