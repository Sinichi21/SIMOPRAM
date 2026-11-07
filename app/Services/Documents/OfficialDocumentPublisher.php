<?php

namespace App\Services\Documents;

use App\Models\ReportVerification;
use App\Models\School;
use App\Models\ScoutGroup;
use App\Services\ReportVerificationService;
use App\Support\GudepDocumentLetterhead;
use App\Support\OfficialDocumentLastPageFooter;
use App\Support\SchoolContext;
use Barryvdh\DomPDF\Facade\Pdf;
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
     * Preferred entrypoint for administrative forms: central QR/footer wrapper.
     * Content view must be a trusted application Blade partial, not user input.
     *
     * @param  array<string, mixed>  $contentData
     * @param  array<string, mixed>  $metadata
     * @param  array<int, int|null>|null  $signatoryUserIds
     */
    public function publishBlade(
        int $schoolId,
        string $sourceType,
        int $sourceId,
        string $documentType,
        string $snapshotChecksum,
        string $filename,
        string $contentView,
        array $contentData = [],
        ?string $title = null,
        ?string $documentNumber = null,
        array $metadata = [],
        ?int $issuedBy = null,
        ?array $signatoryUserIds = null,
        string $seriesKey = 'general',
    ): ReportVerification {
        if (preg_match('/^reports\\.pdf\\.administration\\.[a-z][a-z0-9_-]*$/', $contentView) !== 1) {
            throw ValidationException::withMessages([
                'document' => 'Template administrasi tidak dikenal.',
            ]);
        }

        $letterhead = null;
        $gudep = null;
        if ($sourceType === ScoutGroup::class) {
            $gudep = ScoutGroup::query()->where('school_id', $schoolId)->findOrFail($sourceId);
        } elseif (($contentData['gudep'] ?? null) instanceof ScoutGroup) {
            $gudep = $contentData['gudep'];
            abort_unless((int) $gudep->school_id === $schoolId, 404);
        }
        if ($gudep !== null) {
            $school = School::query()->findOrFail($schoolId);
            $letterhead = GudepDocumentLetterhead::resolve($school, $gudep, 'mabigus');
            // Header changes generate a new immutable document revision rather than
            // quietly reusing a PDF containing an outdated Gudep identity.
            $snapshotChecksum = hash('sha256', $snapshotChecksum.'|'.$this->verification->contentChecksum($letterhead));
        }

        return $this->publish(
            schoolId: $schoolId,
            sourceType: $sourceType,
            sourceId: $sourceId,
            documentType: $documentType,
            snapshotChecksum: $snapshotChecksum,
            filename: $filename,
            renderPdf: function (ReportVerification $verification) use ($contentView, $contentData, $letterhead): string {
                $pdf = Pdf::loadView('reports.pdf.administration-official', [
                    'verification' => $verification,
                    'verificationQrDataUri' => $this->verification->qrDataUri($verification),
                    'contentView' => $contentView,
                    'contentData' => $contentData,
                    'gudepLetterhead' => $letterhead,
                ])->setPaper('a4', 'portrait');

                $dompdf = $pdf->getDomPDF();
                $dompdf->render();
                if ($contentView === 'reports.pdf.administration.gudep-program-sheet') {
                    $copies = $contentData['snapshot']['cc_recipients'] ?? [];
                    OfficialDocumentLastPageFooter::attach($dompdf, $copies);
                }

                return $dompdf->output();
            },
            title: $title,
            documentNumber: $documentNumber,
            metadata: $metadata,
            issuedBy: $issuedBy,
            signatoryUserIds: $signatoryUserIds,
            seriesKey: $seriesKey,
        );
    }

    /**
     * @param  Closure(ReportVerification):string  $renderPdf
     * @param  array<string, mixed>  $metadata
     * @param  array<int, int|null>|null  $signatoryUserIds
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
        string $seriesKey = 'general',
    ): ReportVerification {
        abort_unless(app(SchoolContext::class)->id() === $schoolId, 404);
        abort_unless(auth()->user()?->can('reports.export'), 403);

        if (preg_match('/^[a-z0-9][a-z0-9_-]{0,99}$/', $seriesKey) !== 1
            || $sourceType === '' || $sourceId < 1 || preg_match('/^[a-z][a-z0-9_]{1,49}$/', $documentType) !== 1
            || preg_match('/^[a-f0-9]{64}$/', $snapshotChecksum) !== 1
            || str_ends_with(strtolower($filename), '.pdf') === false) {
            throw ValidationException::withMessages(['document' => 'Identitas penerbitan tidak valid.']);
        }

        $created = null;

        try {
            return DB::transaction(function () use (
                $schoolId, $sourceType, $sourceId, $documentType,
                $snapshotChecksum, $filename, $renderPdf, $title,
                $documentNumber, $metadata, $issuedBy, $signatoryUserIds, $seriesKey, &$created
            ): ReportVerification {
                // Serialize issuance per school on MySQL and avoid concurrent revision numbers.
                School::query()->whereKey($schoolId)->lockForUpdate()->firstOrFail();

                $latest = ReportVerification::query()
                    ->where('school_id', $schoolId)
                    ->where('source_type', $sourceType)
                    ->where('source_id', $sourceId)
                    ->where('document_type', $documentType)
                    ->where('series_key', $seriesKey)
                    ->orderByDesc('revision')
                    ->orderByDesc('id')
                    ->first();

                if ($latest && $latest->hasArchivedPdf() === false) {
                    throw ValidationException::withMessages([
                        'document' => 'Penerbitan sebelumnya belum mempunyai arsip PDF.',
                    ]);
                }

                // Repeated clicks / downloads on an unchanged snapshot do not issue a new QR.
                if ($latest && $latest->isRevoked() === false
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
                    seriesKey: $seriesKey,
                    replacesId: $latest?->id,
                    documentNumber: $documentNumber,
                    title: $title,
                    metadata: $metadata,
                    issuedBy: $issuedBy ?? (auth()->id() !== null ? (int) auth()->id() : null),
                );

                $binary = $renderPdf($created);
                if (str_starts_with($binary, '%PDF-') === false) {
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
