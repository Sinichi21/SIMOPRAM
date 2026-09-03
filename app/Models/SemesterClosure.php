<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SemesterClosure extends Model
{
    use BelongsToSchool;


    protected $fillable = [
        'academic_year_id',
        'semester_id',
        'assessment_config_id',
        'version',
        'status',
        'config_signature',
        'attendance_source_version',
        'snapshot_count',
        'snapshot_checksum',
        'metadata',
        'locked_by',
        'locked_at',
        'reopened_by',
        'reopened_at',
        'reopen_reason',
    ];


    protected function casts(): array
    {
        return [
            'version' =>
                'integer',

            'attendance_source_version' =>
                'integer',

            'snapshot_count' =>
                'integer',

            'metadata' =>
                'array',

            'locked_at' =>
                'datetime',

            'reopened_at' =>
                'datetime',
        ];
    }


    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(
            AcademicYear::class
        );
    }


    public function semester(): BelongsTo
    {
        return $this->belongsTo(
            Semester::class
        );
    }


    public function assessmentConfig(): BelongsTo
    {
        return $this->belongsTo(
            AssessmentConfig::class
        );
    }


    public function snapshots(): HasMany
    {
        return $this->hasMany(
            SemesterGradeSnapshot::class
        );
    }


    public function locker(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'locked_by'
        );
    }


    public function reopener(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'reopened_by'
        );
    }


    public function isLocked(): bool
    {
        return $this->status
            === 'locked';
    }


    public function isReopened(): bool
    {
        return $this->status
            === 'reopened';
    }
}