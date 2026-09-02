<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeScale extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'grade_scale_config_id',
        'letter_grade',
        'min_score',
        'max_score',
        'description',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'min_score' => 'decimal:2',
            'max_score' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    public function config(): BelongsTo
    {
        return $this->belongsTo(
            GradeScaleConfig::class,
            'grade_scale_config_id'
        );
    }
}