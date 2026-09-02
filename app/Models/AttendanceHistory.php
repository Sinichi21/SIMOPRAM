<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceHistory extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'attendance_id',
        'changed_by',
        'old_status',
        'new_status',
        'source',
        'notes',
    ];

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(
            Attendance::class
        );
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'changed_by'
        );
    }
}