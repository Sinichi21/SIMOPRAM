<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActivityAssessmentCriterion extends Model
{
    use BelongsToSchool;


    protected $fillable = [
        'activity_assessment_id',
        'name',
        'description',
        'max_score',
        'weight',
        'sort_order',
    ];


    protected function casts(): array
    {
        return [
            'max_score' =>
                'float',

            'weight' =>
                'float',

            'sort_order' =>
                'integer',
        ];
    }


    public function assessment(): BelongsTo
    {
        return $this->belongsTo(
            ActivityAssessment::class,
            'activity_assessment_id'
        );
    }


    public function scores(): HasMany
    {
        return $this->hasMany(
            ActivityAssessmentScore::class
        );
    }
}