<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssessmentConfig extends Model
{
    use BelongsToSchool;
    use SoftDeletes;

    protected $fillable = [
        'academic_year_id',
        'semester_id',
        'name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
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

    public function items(): HasMany
    {
        return $this->hasMany(
            AssessmentConfigItem::class
        )->orderBy('sort_order');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(
            StudentScore::class
        );
    }

    public function finalGrades(): HasMany
    {
        return $this->hasMany(
            FinalGrade::class
        );
    }
}