<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use BelongsToSchool;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'nis',
        'nisn',
        'name',
        'gender',
        'birth_place',
        'birth_date',
        'phone',
        'parent_phone',
        'address',
        'photo',
        'joined_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'joined_at' => 'date',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(
            School::class
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class
        );
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(
            StudentEnrollment::class
        );
    }

    public function scoutLevelHistories(): HasMany
    {
        return $this->hasMany(
            StudentScoutLevel::class
        );
    }

    public function scoutUnitMembers(): HasMany
    {
        return $this->hasMany(
            ScoutUnitMember::class
        );
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(
            Attendance::class
        );
    }

    public function attendanceParticipations(): HasMany
    {
        return $this->hasMany(
            AttendanceSessionParticipant::class
        );
    }

    public function scores(): HasMany
    {
        return $this->hasMany(
            StudentScore::class
        );
    }

    public function finalGrades(): HasMany
    {
        return $this->hasMany(
            FinalGrade::class
        );
    }
}
