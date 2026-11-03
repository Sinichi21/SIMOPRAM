<?php

namespace App\Services\Documents;

use App\Models\ReportVerification;
use App\Models\School;
use App\Services\ReportVerificationService;
use App\Support\SchoolContext;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Shared publishing entrypoint for NEW official administrative PDFs.
 *
 * Existing letter/LPJ/inventory publishers remain compatible. Callers pass a
 * renderer that embeds $verification's QR via ReportVerificationService.
 * Never use this service for a draft/preview or an unapproved export.
 */
final class OfficialDocumentPublisher
{
    public function __construct(
        private readonly ReportVerificationService $verification,
    ) {}

    /**
     * @param Closure(ReportVerification):string $renderPdf
     * @param array<string, mixed> $metadata
     * @param array<int, int|null>|null $signatoryUserIds
     */
    public function publish(
        int $schoolId,
        string $sourceType,
        int $sourceId,
        string $documentType,
        string $snapshotChecksum,
        string $filename,
        Closure $renderPdf,
        ?string $title = null,
        ?string $documentNumber = null,
        array $metadata = [],
        ?int $issuedBy = null,
        ?array $signatoryUserIds = null,
    ): ReportVerification {
        abort_unless(app(SchoolContext::class)->id() === $schoolId, 404);
        abort_unless(auth()->user()?->can('reports.export'), 403);

        if ($sourceType === '' || $sourceId < 1 || ! preg_match('/^[a-z][a-z0-9_]{1,49}$/', $documentType)
            || ! preg_match('/^[a-f0-9]{64}$/', $snapshotChecksum)
            || ! str_ends_with(strtolower($filename), '.pdf')) {
            throw ValidationException::withMessages(['document' => 'Identitas penerbitan tidak valid.']);
        }

        $created = null;

        try {
            return DB::transaction(function () use (
                $schoolId, $sourceType, $sourceId, $documentType,
                $snapshotChecksum, $filename, $renderPdf, $title,
                $documentNumber, $metadata, $issuedBy, $signatoryUserIds, &$created
            ): ReportVerification {
                // Serialize issuance per school on MySQL and avoid concurrent revision numbers.
                School::query()->whereKey($schoolId)->lockForUpdate()->firstOrFail();

                $latest = ReportVerification::query()
                    ->where('school_id', $schoolId)
                    ->where('source_type', $sourceType)
                    ->where('source_id', $sourceId)
                    ->where('document_type', $documentType)
                    ->orderByDesc('revision')
                    ->orderByDesc('id')
                    ->first();

                if ($latest && ! $latest->hasArchivedPdf()) {
                    throw ValidationException::withMessages([
                        'document' => 'Penerbitan sebelumnya belum mempunyai arsip PDF.',
                    ]);
                }

                // Repeated clicks / downloads on an unchanged snapshot do not issue a new QR.
                if ($latest && ! $latest->isRevoked()
                    && hash_equals((string) $latest->snapshot_checksum, $snapshotChecksum)) {
                    return $latest;
                }

                if ($latest && $latest->publicStatus() === 'pending') {
                    throw ValidationException::withMessages([
                        'document' => 'Selesaikan persetujuan revisi sebelumnya terlebih dahulu.',
                    ]);
                }

                $created = $this->verification->issueDocumentVersion(
                    schoolId: $schoolId,
                    documentType: $documentType,
                    sourceType: $sourceType,
                    sourceId: $sourceId,
                    snapshotChecksum: $snapshotChecksum,
                    revision: $latest ? (int) $latest->revision + 1 : 1,
                    replacesId: $latest?->id,
                    documentNumber: $documentNumber,
                    title: $title,
                    metadata: $metadata,
                    issuedBy: $issuedBy ?? auth()->id(),
                );

                $binary = $renderPdf($created);
                if (! str_starts_with($binary, '%PDF-')) {
                    throw ValidationException::withMessages([
                        'document' => 'Hasil render bukan PDF yang valid.',
                    ]);
                }

                $archived = $this->verification->archivePdf($created, $binary, $filename, $signatoryUserIds);

                // Old document stays valid until its successor is fully approved.
                if ($latest && $archived->publicStatus() === 'valid') {
                    $latest->forceFill(['superseded_by_id' => $archived->id])->save();
                }

                return $archived;
            }, 3);
        } catch (Throwable $exception) {
            // Filesystem writes are not rolled back by a database transaction.
            if ($created) {
                Storage::disk($created->file_disk ?: 'local')->delete(
                    'report-verifications/'.$created->school_id.'/'.$created->code.'.pdf'
                );
            }

            throw $exception;
        }
    }
}
