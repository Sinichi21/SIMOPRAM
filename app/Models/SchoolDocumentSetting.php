<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolDocumentSetting extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'responsible_coach_id',
        'principal_name',
        'principal_nip',
        'coordinator_name',
        'coordinator_nip',
        'gudep_male_number',
        'gudep_female_number',
        'signing_city',
        'parent_agency',
        'extracurricular_weekday',
        'extracurricular_start_time',
        'extracurricular_end_time',
        'extracurricular_location',
        'document_note',
    ];

    protected function casts(): array
    {
        return [
            'extracurricular_weekday' => 'integer',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(
            School::class
        );
    }

    public function responsibleCoach(): BelongsTo
    {
        return $this
            ->belongsTo(
                Coach::class,
                'responsible_coach_id'
            )
            ->withTrashed();
    }
}
