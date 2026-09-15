<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Activity extends Model
{
    use BelongsToSchool, HasFactory, SoftDeletes;

    protected $attributes = ['approval_status' => 'approved', 'registration_open' => false];

    protected $fillable = [
        'academic_year_id',
        'semester_id',
        'created_by',
        'title',
        'activity_type',
        'routine_session_no',
        'description',
        'location',
        'latitude',
        'longitude',
        'start_at',
        'end_at',
        'status',
        'is_public',
        'published_at',
        'registration_open',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'parent_activity_id' => 'integer',
            'end_at' => 'datetime',
            'published_at' => 'datetime',
            'is_public' => 'boolean',
            'registration_open' => 'boolean',
            'registration_fields' => 'array',
            'registration_categories' => 'array',
            'attachments' => 'array',
            'team_min' => 'integer',
            'team_max' => 'integer',
            'reviewed_at' => 'datetime',
            'routine_session_no' => 'integer',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function organizerSchool(): BelongsTo
    {
        return $this->belongsTo(School::class, 'organizer_school_id');
    }

    public function parentActivity(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_activity_id');
    }

    public function subActivities(): HasMany
    {
        return $this->hasMany(self::class, 'parent_activity_id');
    }

    public function delegates(): HasMany
    {
        return $this->hasMany(ActivityDelegate::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(ActivityRegistration::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(ActivityEntry::class);
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

    public function scoutLevels(): BelongsToMany
    {
        return $this->belongsToMany(
            ScoutLevel::class,
            'activity_scout_level'
        )->withTimestamps();
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
