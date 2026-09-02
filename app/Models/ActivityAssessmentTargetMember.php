<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityAssessmentTargetMember extends Model
{
    use BelongsToSchool;


    protected $fillable = [
        'activity_assessment_target_id',
        'student_id',
    ];


    public function target(): BelongsTo
    {
        return $this->belongsTo(
            ActivityAssessmentTarget::class,
            'activity_assessment_target_id'
        );
    }


    public function student(): BelongsTo
    {
        return $this->belongsTo(
            Student::class
        );
    }
}