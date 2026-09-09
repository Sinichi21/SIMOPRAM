<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LetterDisposition extends Model
{
    protected $fillable = [
        'letter_id', 'from_user_id', 'to_user_id', 'instruction', 'notes', 'status', 'disposed_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return ['disposed_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function letter(): BelongsTo
    {
        return $this->belongsTo(Letter::class);
    }

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }
}
