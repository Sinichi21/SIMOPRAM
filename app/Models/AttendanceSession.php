<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttendanceSession extends Model
{
    use BelongsToSchool;
    use SoftDeletes;

    protected $fillable = [
        'activity_id',
        'created_by',
        'name',
        'participant_scope',
        'participant_scope_id',
        'open_at',
        'late_after',
        'close_at',
        'allow_manual',
        'allow_self_checkin',
        'latitude',
        'longitude',
        'radius_m',
        'max_accuracy_m',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'open_at' => 'datetime',
            'late_after' => 'datetime',
            'close_at' => 'datetime',

            'allow_manual' => 'boolean',
            'allow_self_checkin' => 'boolean',
            'is_active' => 'boolean',

            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',

            'radius_m' => 'integer',
            'max_accuracy_m' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function participants(): HasMany
    {
        return $this->hasMany(
            AttendanceSessionParticipant::class
        );
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(
            Attendance::class
        );
    }
}
