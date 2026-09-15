<?php

namespace App\Models;

use Database\Factories\ActivityRegistrationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityRegistration extends Model
{
    /** @use HasFactory<ActivityRegistrationFactory> */
    use HasFactory;

    protected $fillable = ['activity_id', 'entry_id', 'is_reserve', 'user_id', 'student_id', 'coach_id', 'origin_school_id', 'name', 'identifier', 'school_name', 'role', 'channel', 'destination', 'identity_key', 'status'];

    protected $hidden = ['token_hash', 'destination', 'identity_key', 'access_version'];

    protected function casts(): array
    {
        return ['destination' => 'encrypted', 'is_reserve' => 'boolean', 'sent_at' => 'datetime', 'link_requested_at' => 'datetime', 'checked_in_at' => 'datetime', 'access_version' => 'integer'];
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class)->withoutGlobalScope('school');
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(ActivityEntry::class, 'entry_id');
    }
}
