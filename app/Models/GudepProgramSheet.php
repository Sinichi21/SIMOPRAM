<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GudepProgramSheet extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'scout_group_id', 'academic_year_id', 'semester_id',
        'scout_level_id', 'gudep_formation_id', 'letterhead_type',
        'acknowledged_by_ref', 'signed_by_ref', 'parent_id', 'activity_id', 'kind', 'title', 'scheduled_for',
        'entries', 'notes', 'document_layout', 'cc_recipients', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'entries' => 'array',
            'cc_recipients' => 'array',
            'scheduled_for' => 'date',
        ];
    }

    public function scoutGroup(): BelongsTo
    {
        return $this->belongsTo(ScoutGroup::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function gudepFormation(): BelongsTo
    {
        return $this->belongsTo(GudepFormation::class);
    }

    public function scoutLevel(): BelongsTo
    {
        return $this->belongsTo(ScoutLevel::class);
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }
}
