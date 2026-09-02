<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScoutUnit extends Model
{
    use BelongsToSchool;
    use SoftDeletes;

    protected $fillable = [
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

    public function scoutLevel(): BelongsTo
    {
        return $this->belongsTo(
            ScoutLevel::class
        );
    }

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

    public function memberships(): HasMany
    {
        return $this->hasMany(
            ScoutUnitMember::class
        );
    }
}