<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Announcement extends Model
{
    use BelongsToSchool;
    use SoftDeletes;

    protected $fillable = [
        'created_by',
        'updated_by',
        'title',
        'body',
        'status',
        'is_public',
        'publish_at',
        'published_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',

            'publish_at' => 'datetime',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(
            School::class
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

    public function targets(): HasMany
    {
        return $this->hasMany(
            AnnouncementTarget::class
        );
    }

    public function notificationLogs(): HasMany
    {
        return $this->hasMany(
            NotificationLog::class
        );
    }
}
