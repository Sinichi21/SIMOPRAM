<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityAssessmentScore extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'activity_assessment_target_id',
        'activity_assessment_criterion_id',
        'score',
        'weighted_score',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'float',

            'weighted_score' => 'float',
        ];
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(
            ActivityAssessmentTarget::class,
            'activity_assessment_target_id'
        );
    }

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(
            ActivityAssessmentCriterion::class,
            'activity_assessment_criterion_id'
        );
    }
}
