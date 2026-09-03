<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActivityAssessment extends Model
{
    use BelongsToSchool, HasFactory;

    protected $fillable = [
        'activity_id',
        'assessment_factor_id',
        'title',
        'mode',
        'status',
        'description',
        'created_by',
        'published_by',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(
            Activity::class
        );
    }

    public function factor(): BelongsTo
    {
        return $this->belongsTo(
            AssessmentFactor::class,
            'assessment_factor_id'
        );
    }

    public function criteria(): HasMany
    {
        return $this
            ->hasMany(
                ActivityAssessmentCriterion::class
            )
            ->orderBy(
                'sort_order'
            );
    }

    public function targets(): HasMany
    {
        return $this->hasMany(
            ActivityAssessmentTarget::class
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'published_by'
        );
    }

    public function isIndividual(): bool
    {
        return $this->mode === 'individual';
    }

    public function isTeam(): bool
    {
        return $this->mode === 'team';
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }
}
