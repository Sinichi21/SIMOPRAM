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
        'gudep_male_number',
        'gudep_female_number',
        'signing_city',
        'document_note',
    ];

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
