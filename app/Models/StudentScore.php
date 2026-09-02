<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentScore extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'assessment_config_id',
        'student_id',
        'assessment_factor_id',
        'score',
        'source',
        'source_version',
        'source_synced_at',
        'entered_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'float',
            'source_version' => 'integer',
            'source_synced_at' => 'datetime',
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

    public function factor(): BelongsTo
    {
        return $this->belongsTo(
            AssessmentFactor::class,
            'assessment_factor_id'
        );
    }

    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'entered_by'
        );
    }
}