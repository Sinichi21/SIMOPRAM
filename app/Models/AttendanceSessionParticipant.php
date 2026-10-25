<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AttendanceSessionParticipant extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'attendance_session_id',
        'student_id',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(
            AttendanceSession::class,
            'attendance_session_id'
        );
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(
            Student::class
        );
    }

    public function attendance(): HasOne
    {
        return $this->hasOne(Attendance::class, 'student_id', 'student_id')
            ->where('attendance_session_id', $this->attendance_session_id);
    }
}
