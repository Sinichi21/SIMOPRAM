<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramLinkToken extends Model
{
    protected $fillable = [
        'school_id',
        'user_id',
        'token_hash',
        'expires_at',
        'used_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
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

    public function isExpired(): bool
    {
        return now()->greaterThan(
            $this->expires_at
        );
    }

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }
}
