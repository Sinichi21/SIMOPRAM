<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $school_id
 * @property int $scout_level_id
 * @property int $academic_year_id
 * @property int|null $scout_group_id
 * @property int|null $gudep_formation_id
 * @property string $name
 */
class ScoutUnit extends Model
{
    use BelongsToSchool;
    use SoftDeletes;

    protected $fillable = [
        'scout_group_id',
        'gudep_formation_id',
        'scout_level_id',
        'academic_year_id',
        'name',
        'unit_type',
        'leader_student_id',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(
            School::class
        );
    }

    /** @return BelongsTo<GudepFormation, $this> */
    public function gudepFormation(): BelongsTo
    {
        return $this->belongsTo(GudepFormation::class);
    }

    public function scoutGroup(): BelongsTo
    {
        return $this->belongsTo(ScoutGroup::class);
    }

    /** @return BelongsTo<ScoutLevel, $this> */
    public function scoutLevel(): BelongsTo
    {
        return $this->belongsTo(
            ScoutLevel::class
        );
    }

    /** @return BelongsTo<AcademicYear, $this> */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(
            AcademicYear::class
        );
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(
            Student::class,
            'leader_student_id'
        );
    }

    /** @return HasMany<ScoutUnitMember, $this> */
    public function memberships(): HasMany
    {
        return $this->hasMany(
            ScoutUnitMember::class
        );
    }
}
