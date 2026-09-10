<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ReportVerification extends Model
{
    protected $fillable = [
        'school_id',
        'semester_closure_id',
        'source_id',
        'source_type',
        'code',
        'document_type',
        'document_number',
        'title',
        'snapshot_checksum',
        'metadata',
        'file_disk',
        'file_path',
        'file_name',
        'file_sha256',
        'file_size',
        'archived_at',
        'issued_by',
        'issued_at',
        'verification_count',
        'last_verified_at',
        'revoked_at',
        'revoked_by',
        'revocation_reason',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'required_signatories' => 'array',
            'required_signatory_ids' => 'array',
            'signatory_approvals' => 'array',
            'approval_completed_at' => 'datetime',
            'file_size' => 'integer',
            'archived_at' => 'datetime',
            'issued_at' => 'datetime',
            'verification_count' => 'integer',
            'last_verified_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function closure(): BelongsTo
    {
        return $this->belongsTo(SemesterClosure::class, 'semester_closure_id')
            ->withoutGlobalScopes();
    }

    public function source(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'source_type', 'source_id');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function revoker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function hasArchivedPdf(): bool
    {
        return filled($this->file_path) && filled($this->file_sha256);
    }

    public function publicStatus(): string
    {
        if ($this->isRevoked()) {
            return 'revoked';
        }

        if ($this->closure && $this->closure->isReopened()) {
            return 'superseded';
        }

        return $this->pendingSignatoryIds() === [] ? 'valid' : 'pending';
    }

    /** @return array<int, int> */
    public function pendingSignatoryIds(): array
    {
        $approved = collect($this->signatory_approvals ?? [])
            ->filter(fn (array $approval): bool => $this->file_sha256 !== null
                && ($approval['file_sha256'] ?? null) === $this->file_sha256)
            ->pluck('user_id')->all();

        return array_values(array_diff(
            array_column($this->required_signatories ?? [], 'user_id'),
            $approved
        ));
    }

    public function documentTypeLabel(): string
    {
        return match ($this->document_type) {
            'grades' => 'Rekap Nilai',
            'attendance' => 'Rekap Absensi',
            'lpj' => 'LPJ Kegiatan',
            'letter' => 'Surat Keluar',
            default => str($this->document_type)->replace('_', ' ')->title()->toString(),
        };
    }

    public function publicTitle(): string
    {
        $classification = (string) data_get($this->metadata, 'security_classification', 'Biasa');

        if (in_array($classification, ['Terbatas', 'Rahasia'], true)) {
            return 'Dokumen berklasifikasi '.$classification;
        }

        return $this->title ?: $this->documentTypeLabel();
    }
}
