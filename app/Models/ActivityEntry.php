<?php

namespace App\Models;

use Database\Factories\ActivityEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActivityEntry extends Model
{
    /** @use HasFactory<ActivityEntryFactory> */
    use HasFactory;

    protected $fillable = ['activity_id', 'registered_by', 'name', 'category', 'status', 'validation_status', 'validated_by', 'validated_at', 'answers', 'form_snapshot', 'terms_snapshot', 'declaration_accepted_at', 'terms_accepted_at', 'attachments'];

    protected function casts(): array
    {
        return ['answers' => 'array', 'form_snapshot' => 'array', 'attachments' => 'array', 'validated_at' => 'datetime', 'declaration_accepted_at' => 'datetime', 'terms_accepted_at' => 'datetime'];
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class)->withoutGlobalScope('school');
    }

    public function members(): HasMany
    {
        return $this->hasMany(ActivityRegistration::class, 'entry_id');
    }
}
