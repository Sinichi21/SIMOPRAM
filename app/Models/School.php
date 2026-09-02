<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class School extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'npsn',
        'name',
        'slug',
        'level',
        'address',
        'village',
        'district',
        'city',
        'province',
        'postal_code',
        'latitude',
        'longitude',
        'phone',
        'email',
        'website',
        'logo',
        'timezone',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_active' => 'boolean',
        ];
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(
            SchoolUserMembership::class
        );
    }

    public function academicYears(): HasMany
    {
        return $this->hasMany(
            AcademicYear::class
        );
    }

    public function semesters(): HasMany
    {
        return $this->hasMany(
            Semester::class
        );
    }

    public function scoutGroups(): HasMany
    {
        return $this->hasMany(
            ScoutGroup::class
        );
    }

    public function classrooms(): HasMany
    {
        return $this->hasMany(
            Classroom::class
        );
    }

    public function students(): HasMany
    {
        return $this->hasMany(
            Student::class
        );
    }

    public function coaches(): HasMany
    {
        return $this->hasMany(
            Coach::class
        );
    }

    public function scoutUnits(): HasMany
    {
        return $this->hasMany(
            ScoutUnit::class
        );
    }
}