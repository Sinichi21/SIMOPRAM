<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Letter extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $fillable = [
        'school_id', 'direction', 'agenda_number', 'letter_number', 'letter_type_id', 'letter_field_id', 'template_id',
        'letter_date', 'received_date', 'sender', 'recipient', 'subject', 'classification', 'security_classification',
        'archive_code', 'archive_category', 'retention_years', 'archive_status', 'body', 'metadata',
        'signatory_name', 'signatory_position', 'signatory_identity', 'status', 'created_by', 'updated_by',
        'published_by', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'letter_date' => 'date', 'received_date' => 'date', 'published_at' => 'datetime',
            'retention_years' => 'integer', 'metadata' => 'array',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function letterType(): BelongsTo
    {
        return $this->belongsTo(LetterType::class);
    }

    public function letterField(): BelongsTo
    {
        return $this->belongsTo(LetterField::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(LetterTemplate::class, 'template_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(LetterAttachment::class);
    }

    public function dispositions(): HasMany
    {
        return $this->hasMany(LetterDisposition::class);
    }

    public function publication(): HasOne
    {
        return $this->hasOne(ReportVerification::class, 'source_id')
            ->where('source_type', self::class)
            ->where('document_type', 'letter');
    }
}
