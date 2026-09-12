<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolRegistrationRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_name',
        'npsn',
        'level',
        'city',
        'contact_name',
        'contact_phone',
        'contact_email',
        'notes',
    ];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
