<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GudepAccreditationEvidenceLink extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'indicator_id', 'source_kind', 'source_id', 'linked_by',
    ];

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(GudepAccreditationIndicator::class, 'indicator_id');
    }
}
