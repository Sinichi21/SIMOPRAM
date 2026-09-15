<?php

namespace App\Models;

use Database\Factories\ActivityDelegateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityDelegate extends Model
{
    /** @use HasFactory<ActivityDelegateFactory> */
    use HasFactory;

    protected $fillable = ['activity_id', 'user_id', 'requested_by', 'requesting_school_id', 'status', 'reviewed_by', 'reviewed_at', 'review_note'];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class)->withoutGlobalScope('school');
    }
}
