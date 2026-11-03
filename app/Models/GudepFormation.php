<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $school_id
 * @property int $scout_group_id
 * @property int $scout_level_id
 * @property int $academic_year_id
 * @property string $name
 * @property string $formation_type
 */
class GudepFormation extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'scout_group_id', 'scout_level_id', 'academic_year_id',
        'name', 'formation_type', 'gender', 'is_active', 'notes',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** @return BelongsTo<ScoutGroup, $this> */
    public function scoutGroup(): BelongsTo
    {
        return $this->belongsTo(ScoutGroup::class);
    }

    /** @return BelongsTo<ScoutLevel, $this> */
    public function scoutLevel(): BelongsTo
    {
        return $this->belongsTo(ScoutLevel::class);
    }

    /** @return BelongsTo<AcademicYear, $this> */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /** @return HasMany<ScoutUnit, $this> */
    public function groups(): HasMany
    {
        return $this->hasMany(ScoutUnit::class, 'gudep_formation_id');
    }
}
