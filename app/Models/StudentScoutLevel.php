<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentScoutLevel extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'student_id',
        'scout_level_id',
        'academic_year_id',
        'started_at',
        'ended_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'date',
            'ended_at' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(
            Student::class
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
}
