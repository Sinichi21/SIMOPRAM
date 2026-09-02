<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinalGrade extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'assessment_config_id',
        'student_id',
        'final_score',
        'letter_grade',
        'description',
        'attendance_source_version',
        'calculated_at',
        'calculated_by',
    ];

    protected function casts(): array
    {
        return [
            'final_score' => 'float',
            'attendance_source_version' => 'integer',
            'calculated_at' => 'datetime',
        ];
    }

    public function config(): BelongsTo
    {
        return $this->belongsTo(
            AssessmentConfig::class,
            'assessment_config_id'
        );
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(
            Student::class
        );
    }

    public function calculatedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'calculated_by'
        );
    }
}