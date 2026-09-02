<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceScoreSetting extends Model
{
    use BelongsToSchool;


    protected $fillable = [
        'present_weight',
        'late_weight',
        'sick_weight',
        'excused_weight',
        'absent_weight',
        'updated_by',
    ];


    protected function casts(): array
    {
        return [
            'present_weight' => 'float',
            'late_weight' => 'float',
            'sick_weight' => 'float',
            'excused_weight' => 'float',
            'absent_weight' => 'float',

            'version' => 'integer',
        ];
    }


    public static function defaultWeights(): array
    {
        return [
            'present' => 100.00,
            'late' => 75.00,
            'sick' => 75.00,
            'excused' => 75.00,
            'absent' => 0.00,
        ];
    }


    public function percentages(): array
    {
        return [
            'present' =>
                (float) $this->present_weight,

            'late' =>
                (float) $this->late_weight,

            'sick' =>
                (float) $this->sick_weight,

            'excused' =>
                (float) $this->excused_weight,

            'absent' =>
                (float) $this->absent_weight,
        ];
    }


    public function factors(): array
    {
        return collect(
            $this->percentages()
        )
            ->map(
                fn (float $value): float =>
                    $value / 100
            )
            ->all();
    }


    public function school(): BelongsTo
    {
        return $this->belongsTo(
            School::class
        );
    }


    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by'
        );
    }
}