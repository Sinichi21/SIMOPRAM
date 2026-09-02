<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnnouncementTarget extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'announcement_id',
        'target_type',
        'target_id',
    ];

    public function announcement(): BelongsTo
    {
        return $this->belongsTo(
            Announcement::class
        );
    }
}