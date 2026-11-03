<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $student_id
 * @property int $scout_unit_id
 * @property string|null $position
 * @property \Illuminate\Support\Carbon|null $joined_at
 * @property \Illuminate\Support\Carbon|null $left_at
 */
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

    /** @return BelongsTo<ScoutUnit, $this> */
    public function scoutUnit(): BelongsTo
    {
        return $this->belongsTo(
            ScoutUnit::class
        );
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(
            Student::class
        );
    }
}
