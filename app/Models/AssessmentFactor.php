<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssessmentFactor extends Model
{
    use BelongsToSchool, HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'source_type',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function configItems(): HasMany
    {
        return $this->hasMany(
            AssessmentConfigItem::class
        );
    }
}
