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
        'calculated_at',
        'calculated_by',
    ];

    protected function casts(): array
    {
        return [
            'final_score' => 'decimal:2',
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
}