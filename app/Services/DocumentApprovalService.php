<?php

namespace App\Services;

use App\Models\ReportVerification;
use App\Models\SchoolDocumentSetting;
use App\Models\User;
use App\Support\SchoolContext;
use Illuminate\Support\Facades\DB;

class DocumentApprovalService
{
    /** @param array<int, int|null>|null $signatoryUserIds */
    public function request(ReportVerification $document, ?array $signatoryUserIds = null): void
    {
        if ($document->required_signatories !== null) {
            return;
        }

        $ids = $signatoryUserIds ?? [];
        if ($signatoryUserIds !== null) {
            $ids = $signatoryUserIds;
        } elseif ($document->document_type === 'letter') {
            $ids = [data_get($document->metadata, 'signatory_user_id')];
        } elseif (in_array($document->document_type, ['grades', 'attendance', 'lpj'], true)) {
            $setting = SchoolDocumentSetting::query()->where('school_id', $document->school_id)->first();
            $ids = [$setting?->principal_signatory_user_id, $setting?->responsible_signatory_user_id];
            if ($document->document_type === 'lpj') {
                $ids[] = $setting?->coordinator_signatory_user_id;
            }
        }

        $signatories = User::query()->whereIn('id', array_filter($ids))
            ->where('system_role', 'principal')
            ->get(['id', 'name'])
            ->map(fn (User $user): array => ['user_id' => $user->id, 'name' => $user->name])
            ->all();

        $document->forceFill([
            'required_signatories' => $signatories,
            'required_signatory_ids' => array_column($signatories, 'user_id'),
        ])->save();
    }

    public function approve(int $documentId): void
    {
        $user = auth()->user();
        abort_unless($user?->is_active && $user->can('documents.approve'), 403);
        $schoolId = app(SchoolContext::class)->id();
        abort_unless($schoolId, 409);
        abort_unless($user->schoolMemberships()->where('school_id', $schoolId)
            ->where('is_active', true)->whereNull('left_at')->exists(), 403);

        DB::transaction(function () use ($documentId, $user, $schoolId): void {
            $document = ReportVerification::query()->where('school_id', $schoolId)
                ->lockForUpdate()->find($documentId);
            abort_unless($document, 404);
            abort_unless(in_array($user->id, array_column($document->required_signatories ?? [], 'user_id'), true), 403);
            abort_unless($document->publicStatus() === 'pending' && $document->hasArchivedPdf(), 409,
                'Dokumen tidak sedang menunggu persetujuan atau belum memiliki arsip PDF.');
            abort_unless(in_array($user->id, $document->pendingSignatoryIds(), true), 409,
                'Anda sudah menyetujui dokumen ini.');
            app(ReportVerificationService::class)->archivedPdfBinary($document);
            $approvals = $document->signatory_approvals ?? [];
            $approvals[] = [
                'user_id' => $user->id, 'name' => $user->name,
                'approved_at' => now()->toISOString(), 'file_sha256' => $document->file_sha256,
            ];
            $document->forceFill(['signatory_approvals' => $approvals]);
            if ($document->pendingSignatoryIds() === []) {
                $document->approval_completed_at = now();
            }
            $document->save();
        });
    }
}
