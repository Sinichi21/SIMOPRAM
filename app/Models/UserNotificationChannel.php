<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotificationChannel extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'user_id',
        'channel',
        'destination',
        'is_verified',
        'is_active',
        'metadata',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'is_active' => 'boolean',
            'metadata' => 'array',
            'verified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class
        );
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(
            School::class
        );
    }
}
