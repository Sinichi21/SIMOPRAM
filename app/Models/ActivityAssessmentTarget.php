<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActivityAssessmentTarget extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'activity_assessment_id',
        'student_id',
        'scout_unit_id',
        'total_score',
        'normalized_score',
        'notes',
        'assessed_by',
        'assessed_at',
    ];

    protected function casts(): array
    {
        return [
            'total_score' => 'float',

            'normalized_score' => 'float',

            'assessed_at' => 'datetime',
        ];
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(
            ActivityAssessment::class,
            'activity_assessment_id'
        );
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(
            Student::class
        );
    }

    public function scoutUnit(): BelongsTo
    {
        return $this->belongsTo(
            ScoutUnit::class
        );
    }

    public function members(): HasMany
    {
        return $this->hasMany(
            ActivityAssessmentTargetMember::class
        );
    }

    public function scores(): HasMany
    {
        return $this->hasMany(
            ActivityAssessmentScore::class
        );
    }

    public function assessor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'assessed_by'
        );
    }
}
