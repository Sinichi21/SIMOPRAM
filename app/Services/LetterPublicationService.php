<?php

namespace App\Services;

use App\Models\Letter;
use App\Models\LetterAttachment;
use App\Models\ReportVerification;
use App\Models\SchoolDocumentSetting;
use App\Models\ScoutGroup;
use App\Support\SchoolContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class LetterPublicationService
{
    public function __construct(
        private readonly ReportVerificationService $verificationService,
        private readonly LetterTemplateHtmlSanitizer $sanitizer,
        private readonly LetterTemplateRenderer $renderer,
    ) {}

    public function publish(Letter $letter): ReportVerification
    {
        abort_unless($letter->direction === 'outgoing', 422, 'Hanya surat keluar yang dapat diterbitkan.');
        abort_unless($letter->status === 'published', 422, 'Surat harus berstatus Terbit.');
        abort_unless(filled($letter->letter_number), 422, 'Nomor surat belum tersedia.');

        $schoolId = app(SchoolContext::class)->id();
        abort_unless($schoolId && (int) $schoolId === (int) $letter->school_id, 404);

        $letter->loadMissing([
            'school',
            'letterType',
            'letterField',
            'publisher',
            'template',
            'attachments',
        ]);

        $attachmentManifest = $this->attachmentManifest($letter);
        $snapshot = $this->snapshot($letter, $attachmentManifest);
        $snapshotJson = json_encode(
            $snapshot,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );
        $checksum = hash('sha256', $snapshotJson);

        $verification = $this->verificationService->issueDocument(
            schoolId: (int) $letter->school_id,
            documentType: 'letter',
            sourceType: Letter::class,
            sourceId: (int) $letter->id,
            snapshotChecksum: $checksum,
            documentNumber: $letter->letter_number,
            title: $letter->subject,
            metadata: [
                'security_classification' => $letter->security_classification ?: 'Biasa',
                'direction' => 'outgoing',
                'letter_type' => $letter->letterType?->name,
                'letter_field' => $letter->letterField?->name,
                'attachments_count' => count($attachmentManifest),
                'attachments' => $attachmentManifest,
            ],
            issuedBy: $letter->published_by ?: auth()->id()
        );

        if ($verification->hasArchivedPdf()) {
            return $verification;
        }

        try {
            $documentSetting = SchoolDocumentSetting::query()
                ->with('responsibleCoach')
                ->first();

            $scoutGroup = ScoutGroup::query()
                ->where('is_active', true)
                ->first();

            $templateBody = (string) ($letter->template?->body_template ?? '');
            $flexibleLayout = $letter->template
                ? $this->renderer->containsAny($templateBody, [
                    'letter_number', 'attachment_label', 'attachment_count', 'subject',
                    'recipient', 'recipient_location', 'signatory_name',
                    'signatory_position', 'signatory_identity',
                ])
                : false;

            $pdfBody = $this->sanitizer->forPdf(
                (string) $letter->body,
                ! $flexibleLayout
            );
            $templateData = data_get($letter->metadata, 'template_data', []);
            $recipientLocation = trim((string) data_get($templateData, 'recipient_location', 'Tempat'));
            $recipientLocation = $recipientLocation !== '' ? $recipientLocation : 'Tempat';

            $binary = Pdf::loadView('letters.pdf.outgoing', [
                'letter' => $letter,
                'school' => $letter->school,
                'documentSetting' => $documentSetting,
                'scoutGroup' => $scoutGroup,
                'verification' => $verification,
                'verificationUrl' => $this->verificationService->publicUrl($verification),
                'qrDataUri' => $this->verificationService->qrDataUri($verification),
                'pdfBody' => $pdfBody,
                'recipientLocation' => $recipientLocation,
                'attachmentCount' => count($attachmentManifest),
                'attachmentManifest' => $attachmentManifest,
                'flexibleLayout' => $flexibleLayout,
            ])->setPaper('a4', 'portrait')->output();

            $filename = 'surat-'.Str::slug((string) $letter->letter_number).'.pdf';

            return $this->verificationService->archivePdf(
                verification: $verification,
                binary: $binary,
                filename: $filename
            );
        } catch (Throwable $exception) {
            $this->verificationService->discardFailedIssue($verification);
            throw $exception;
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $attachmentManifest
     * @return array<string, mixed>
     */
    private function snapshot(Letter $letter, array $attachmentManifest): array
    {
        return [
            'id' => $letter->id,
            'school_id' => $letter->school_id,
            'letter_number' => $letter->letter_number,
            'letter_date' => $letter->letter_date?->toDateString(),
            'recipient' => $letter->recipient,
            'subject' => $letter->subject,
            'body' => $letter->body,
            'security_classification' => $letter->security_classification,
            'signatory_name' => $letter->signatory_name,
            'signatory_position' => $letter->signatory_position,
            'signatory_identity' => $letter->signatory_identity,
            'attachments' => $attachmentManifest,
            'published_at' => $letter->published_at?->toISOString(),
            'published_by' => $letter->published_by,
        ];
    }

    /**
     * Setiap lampiran tidak mendapat QR terpisah. Lampiran masuk ke manifest
     * snapshot publikasi yang dilindungi oleh verification code/QR surat utama.
     *
     * @return array<int, array<string, mixed>>
     */
    private function attachmentManifest(Letter $letter): array
    {
        return $letter->attachments
            ->sortBy('id')
            ->values()
            ->map(function (LetterAttachment $attachment): array {
                $exists = Storage::disk($attachment->disk)->exists($attachment->path);
                $binary = $exists
                    ? Storage::disk($attachment->disk)->get($attachment->path)
                    : null;

                return [
                    'id' => $attachment->id,
                    'name' => $attachment->original_name,
                    'mime_type' => $attachment->mime_type,
                    'size' => $attachment->size,
                    'sha256' => $binary !== null ? hash('sha256', $binary) : null,
                    'available_at_publication' => $exists,
                ];
            })
            ->all();
    }
}
