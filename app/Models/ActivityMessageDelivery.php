<?php

namespace App\Models;

use Database\Factories\ActivityMessageDeliveryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityMessageDelivery extends Model
{
    /** @use HasFactory<ActivityMessageDeliveryFactory> */
    use HasFactory;

    protected $fillable = ['activity_registration_id', 'title', 'body', 'channel', 'status', 'sent_at'];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime'];
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(ActivityRegistration::class, 'activity_registration_id');
    }
}
