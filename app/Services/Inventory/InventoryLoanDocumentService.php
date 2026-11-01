<?php

namespace App\Services\Inventory;

use App\Enums\ScoutAdministrationType;
use App\Models\InventoryLoan;
use App\Models\InventoryLoanDocument;
use App\Models\LetterField;
use App\Models\LetterType;
use App\Models\School;
use App\Models\SchoolDocumentSetting;
use App\Models\ScoutAdministrationProfile;
use App\Models\ScoutGroup;
use App\Services\DocumentSignatoryService;
use App\Services\LetterAdministrationBootstrapService;
use App\Services\LetterNumberService;
use App\Services\ReportVerificationService;
use App\Support\SchoolContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class InventoryLoanDocumentService
{
    public function correct(int $documentId, string $reason): InventoryLoanDocument
    {
        $reason = trim($reason);
        if (mb_strlen($reason) < 10 || mb_strlen($reason) > 2000) {
            throw ValidationException::withMessages(['reason' => 'Alasan koreksi wajib 10-2000 karakter.']);
        }
        $schoolId = $this->schoolId();
        $paths = [];
        try {
            return DB::transaction(function () use ($documentId, $reason, $schoolId, &$paths): InventoryLoanDocument {
                $original = InventoryLoanDocument::query()->where('school_id', $schoolId)->findOrFail($documentId);
                $loan = InventoryLoan::query()->where('school_id', $schoolId)->lockForUpdate()->findOrFail($original->loan_id);
                $current = $loan->documents()->where('document_type', $original->document_type)
                    ->where('status', 'issued')->lockForUpdate()->first();
                if (! $current || $current->id !== $original->id) {
                    throw ValidationException::withMessages(['document' => 'Hanya versi aktif terbaru yang dapat dikoreksi.']);
                }
                $disk = Storage::disk('local');
                if (! $current->storage_path || ! $disk->exists($current->storage_path)
                    || ! hash_equals((string) $current->sha256, hash('sha256', $disk->get($current->storage_path)))) {
                    throw ValidationException::withMessages(['document' => 'Berkas versi sebelumnya tidak valid.']);
                }
                $new = $this->publish($loan, $current->document_type, $current->version + 1, $paths, $current, $reason);
                $current->update(['status' => 'superseded']);
                if ($current->verification) {
                    app(ReportVerificationService::class)->revoke(
                        $current->verification,
                        'Digantikan oleh BA '.$new->document_number.'. '.$reason,
                        auth()->id(),
                    );
                }

                return $new;
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($paths);
            throw $exception;
        }
    }

    public function issue(int $loanId, string $type): InventoryLoanDocument
    {
        if (! in_array($type, ['handover', 'return'], true)) {
            throw ValidationException::withMessages(['type' => 'Jenis berita acara tidak valid.']);
        }
        $schoolId = $this->schoolId();
        $paths = [];
        try {
            return DB::transaction(function () use ($loanId, $type, $schoolId, &$paths): InventoryLoanDocument {
                $loan = InventoryLoan::query()->where('school_id', $schoolId)->lockForUpdate()->findOrFail($loanId);
                $existing = $loan->documents()->where('document_type', $type)->where('status', 'issued')->first();
                if ($existing) {
                    return $existing;
                }

                return $this->publish($loan, $type, 1, $paths);
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($paths);
            throw $exception;
        }
    }

    /** @return array<string, mixed> */
    public function previewData(InventoryLoan $loan, string $type): array
    {
        $schoolId = $this->schoolId();
        abort_unless((int) $loan->school_id === $schoolId, 404);
        abort_unless(in_array($type, ['handover', 'return'], true), 422);
        $loan->loadMissing(['items.item', 'items.owner', 'items.location', 'events.evidence', 'events.recorder']);
        $events = $loan->events->where('event_type', $type)->sortBy('occurred_at');
        if ($events->isEmpty()) {
            throw ValidationException::withMessages(['document' => 'Belum ada transaksi fisik untuk jenis berita acara ini.']);
        }

        return [
            'loan' => $loan,
            'officers' => $events->unique('recorded_by')->map(function ($event) use ($schoolId): array {
                return $event->recorder
                    ? app(DocumentSignatoryService::class)->identityFor($event->recorder, $schoolId)
                    : ['user_id' => null, 'name' => 'Akun petugas tidak tersedia', 'identity' => ''];
            })->values()->all(),
            'type' => $type,
            'events' => $events,
            'evidenceThumbnails' => app(InventoryLoanEvidenceThumbnailService::class)->forEvents($events),
            'school' => School::query()->findOrFail($schoolId),
            'documentSetting' => SchoolDocumentSetting::query()->where('school_id', $schoolId)->first(),
            'scoutGroup' => ScoutGroup::query()->where('school_id', $schoolId)->where('is_active', true)->first(),
            'administrationProfile' => ScoutAdministrationProfile::query()->where('school_id', $schoolId)
                ->where('type', 'mabigus')->where('is_active', true)->first(),
            'administrationType' => 'mabigus',
        ];
    }

    /** @param array<int, string> $paths */
    private function publish(
        InventoryLoan $loan,
        string $type,
        int $version,
        array &$paths,
        ?InventoryLoanDocument $previous = null,
        ?string $reason = null,
    ): InventoryLoanDocument {
        $schoolId = (int) $loan->school_id;
        app(LetterAdministrationBootstrapService::class)->ensureForSchool($schoolId);
        $data = $this->previewData($loan, $type);
        $letterType = LetterType::query()->where('school_id', $schoolId)->where('code', '02')->firstOrFail();
        $field = LetterField::query()->where('school_id', $schoolId)->where('code', 'C')->firstOrFail();
        $number = app(LetterNumberService::class)->nextOutgoing(
            $schoolId, now(), $letterType, $field, ScoutAdministrationType::Mabigus,
            '{sequence}/02/{month_roman}.{year_short}/{gudep}-C',
        );
        $document = InventoryLoanDocument::query()->create([
            'loan_id' => $loan->id, 'document_type' => $type, 'version' => $version,
            'document_number' => $number, 'status' => 'issued',
            'issued_by' => auth()->id(), 'issued_at' => now(), 'correction_reason' => $reason,
        ]);
        $service = app(ReportVerificationService::class);
        $verification = $service->issueDocument(
            schoolId: $schoolId,
            documentType: 'inventory_'.$type,
            sourceType: InventoryLoanDocument::class,
            sourceId: $document->id,
            snapshotChecksum: $service->contentChecksum([
                'document' => $document->toArray(), 'loan' => $loan->toArray(),
                'events' => $data['events']->toArray(), 'officers' => $data['officers'],
                'administration_profile' => $data['administrationProfile']?->toArray(),
                'document_setting' => $data['documentSetting']?->toArray(),
            ]),
            documentNumber: $number,
            title: $type === 'handover' ? 'Berita Acara Serah Terima Inventaris' : 'Berita Acara Pengembalian Inventaris',
            metadata: [
                'administration_type' => 'mabigus', 'letter_type' => $letterType->name, 'letter_type_code' => '02',
                'letter_field' => $field->name, 'letter_field_code' => 'C',
                'loan_id' => $loan->id, 'loan_number' => $loan->loan_number, 'version' => $version,
                'previous_document_number' => $previous?->document_number,
                'signature_status' => 'wet_signatures_not_verified',
            ],
            issuedBy: auth()->id(),
        );
        $paths[] = 'report-verifications/'.$schoolId.'/'.$verification->code.'.pdf';
        $pdf = Pdf::loadView('inventory.documents.preview', array_merge($data, [
            'issued' => true, 'documentNumber' => $number,
            'correctionReason' => $reason, 'previousDocumentNumber' => $previous?->document_number,
            'correctionIssuedBy' => auth()->user()?->name, 'correctionIssuedAt' => now()->format('d/m/Y H:i'),
            'verification' => $verification,
            'verificationUrl' => $service->publicUrl($verification),
            'verificationQrDataUri' => $service->qrDataUri($verification),
        ]))->setPaper('a4')->output();
        $verification = $service->archivePdf($verification, $pdf, 'BA-'.Str::slug($number).'.pdf', array_column($data['officers'], 'user_id'));
        $document->update(['storage_path' => $verification->file_path, 'sha256' => $verification->file_sha256]);

        return $document;
    }

    private function schoolId(): int
    {
        $schoolId = app(SchoolContext::class)->id();
        abort_unless($schoolId, 409, 'Pilih sekolah aktif terlebih dahulu.');

        return (int) $schoolId;
    }
}
