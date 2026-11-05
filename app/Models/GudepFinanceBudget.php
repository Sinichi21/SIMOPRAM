<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class GudepFinanceBudget extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'scout_group_id', 'academic_year_id',
        'category', 'allocated_amount', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return ['allocated_amount' => 'integer'];
    }
}
