<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GudepAccreditationIndicator extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'scout_group_id', 'academic_year_id',
        'scout_level_id', 'code', 'title', 'description',
        'status', 'reviewed_by', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function evidenceLinks(): HasMany
    {
        return $this->hasMany(GudepAccreditationEvidenceLink::class, 'indicator_id');
    }
}
