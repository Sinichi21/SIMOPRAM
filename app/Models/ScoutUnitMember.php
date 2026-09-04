<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScoutUnitMember extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'scout_unit_id',
        'student_id',
        'position',
        'joined_at',
        'left_at',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'date',
            'left_at' => 'date',
        ];
    }

    public function scoutUnit(): BelongsTo
    {
        return $this->belongsTo(
            ScoutUnit::class
        );
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(
            Student::class
        );
    }
}
