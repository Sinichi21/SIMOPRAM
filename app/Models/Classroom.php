<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Classroom extends Model
{
    use BelongsToSchool;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'grade',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function enrollments()
    {
        return $this->hasMany(StudentEnrollment::class);
    }
}