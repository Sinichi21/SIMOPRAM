<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Journal extends Model
{
    use BelongsToSchool;
    use SoftDeletes;

    protected $fillable = [
        'activity_id',
        'attendance_session_id',
        'created_by',
        'updated_by',
        'objective',
        'material',
        'activity_description',
        'result',
        'evaluation',
        'follow_up',
        'notes',
        'status',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(
            School::class
        );
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(
            Activity::class
        );
    }

    public function attendanceSession(): BelongsTo
    {
        return $this->belongsTo(
            AttendanceSession::class
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by'
        );
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(
            JournalAttachment::class
        );
    }
}