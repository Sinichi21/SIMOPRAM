<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LetterTemplate extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'letter_type_id', 'slug', 'name', 'document_kind', 'title', 'body_template',
        'default_field_code', 'requires_recipient', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['requires_recipient' => 'boolean', 'is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function letterType(): BelongsTo
    {
        return $this->belongsTo(LetterType::class);
    }
}
