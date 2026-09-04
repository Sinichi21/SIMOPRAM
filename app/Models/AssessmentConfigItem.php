<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentConfigItem extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'assessment_config_id',
        'assessment_factor_id',
        'weight',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    public function config(): BelongsTo
    {
        return $this->belongsTo(
            AssessmentConfig::class,
            'assessment_config_id'
        );
    }

    public function factor(): BelongsTo
    {
        return $this->belongsTo(
            AssessmentFactor::class,
            'assessment_factor_id'
        );
    }
}
