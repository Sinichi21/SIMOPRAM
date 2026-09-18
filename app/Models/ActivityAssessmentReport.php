<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityAssessmentReport extends Model
{
    protected $fillable = ['school_id', 'activity_assessment_id', 'code', 'format', 'with_signatures', 'snapshot', 'file_path', 'file_sha256', 'issued_by', 'issued_at'];

    protected function casts(): array
    {
        return ['snapshot' => 'array', 'with_signatures' => 'boolean', 'issued_at' => 'datetime'];
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(ActivityAssessment::class, 'activity_assessment_id')->withoutGlobalScope('school');
    }
}
