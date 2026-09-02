<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendance extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'attendance_session_id',
        'activity_id',
        'student_id',
        'status',
        'source',
        'checked_in_at',
        'latitude',
        'longitude',
        'accuracy_m',
        'distance_m',
        'verified_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'checked_in_at' => 'datetime',

            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',

            'accuracy_m' => 'decimal:2',
            'distance_m' => 'decimal:2',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(
            AttendanceSession::class,
            'attendance_session_id'
        );
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(
            Activity::class
        );
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(
            Student::class
        );
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'verified_by'
        );
    }

    public function histories(): HasMany
    {
        return $this->hasMany(
            AttendanceHistory::class
        );
    }
}