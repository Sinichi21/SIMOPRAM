<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Activity extends Model
{
    use BelongsToSchool;
    use SoftDeletes;

    protected $fillable = [
        'academic_year_id',
        'semester_id',
        'created_by',
        'title',
        'activity_type',
        'description',
        'location',
        'latitude',
        'longitude',
        'start_at',
        'end_at',
        'status',
        'is_public',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'published_at' => 'datetime',
            'is_public' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function coaches(): BelongsToMany
    {
        return $this->belongsToMany(
            Coach::class,
            'activity_coach'
        )
            ->withPivot([
                'school_id',
                'role',
            ])
            ->withTimestamps();
    }

    public function attendanceSessions(): HasMany
    {
        return $this->hasMany(
            AttendanceSession::class
        );
    }

    public function journal(): HasOne
    {
        return $this->hasOne(
            Journal::class
        );
    }
}