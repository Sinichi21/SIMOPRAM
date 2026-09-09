<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolLetterSetting extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'gudep_code', 'number_format', 'agenda_format', 'year_format',
        'sequence_reset', 'letterhead_title', 'letterhead_subtitle', 'letterhead_address',
        'city', 'default_signatory_name', 'default_signatory_position', 'default_signatory_identity',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
