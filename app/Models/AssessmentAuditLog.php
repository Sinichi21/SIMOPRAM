<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentAuditLog extends Model
{
    use BelongsToSchool;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'module',
        'subject_type',
        'subject_id',
        'description',
        'old_values',
        'new_values',
        'metadata',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',

            'new_values' => 'array',

            'metadata' => 'array',

            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class
        );
    }
}
