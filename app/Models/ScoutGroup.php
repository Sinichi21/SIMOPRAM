<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScoutGroup extends Model
{
    use BelongsToSchool;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'male_number',
        'female_number',
        'kwarran',
        'kwarcab',
        'kwarda',
        'kamabigus_name',
        'head_coach_name',
        'secretariat_address',
        'inauguration_date',
        'logo',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'inauguration_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function school()
    {
        return $this->belongsTo(
            School::class
        );
    }
}
