<?php

namespace App\Services;

use App\Models\ReportVerification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PublishedContentDocuments
{
    /** @return Collection<int, ReportVerification> */
    public function available(?int $schoolId): Collection
    {
        $user = auth()->user();
        if (! $user?->is_active) {
            return collect();
        }
        $query = ReportVerification::with('closure', 'school')->whereNull('revoked_at')->whereNotNull('file_path')->whereNotNull('file_sha256');
        if (! $user->isSuperAdmin()) {
            $schoolIds = app(GlobalActivityAccess::class)->schoolAdminIds($user);
            $query->whereIn('school_id', $schoolIds);
        }
        if ($schoolId !== null) {
            $query->where('school_id', $schoolId);
        }

        return $query->latest('issued_at')->get()->filter(fn (ReportVerification $document): bool => $this->isPublishable($document));
    }

    public function isPublishable(ReportVerification $document): bool
    {
        return $document->school?->is_active && $document->hasArchivedPdf()
            && $document->publicStatus() === 'valid'
            && data_get($document->metadata, 'security_classification', 'Biasa') === 'Biasa';
    }

    /** @param array<int, int|string> $ids
     * @return array<int, array<string, mixed>>
     */
    public function references(array $ids, ?int $schoolId): array
    {
        if ($ids === []) {
            return [];
        }
        validator(['publishedDocumentIds' => $ids], [
            'publishedDocumentIds' => ['array', 'max:10'],
            'publishedDocumentIds.*' => ['integer', 'distinct'],
        ])->validate();
        $available = $this->available($schoolId)->keyBy('id');
        $references = [];
        foreach ($ids as $id) {
            $document = $available->get($id);
            if (! $document) {
                throw ValidationException::withMessages(['publishedDocumentIds' => 'Dokumen tidak tersedia, belum disetujui, atau berada di luar sekolah yang dapat Anda kelola.']);
            }
            $references[] = ['document_id' => $document->id, 'name' => $document->file_name ?: $document->title.'.pdf',
                'sha256' => $document->file_sha256, 'mime' => 'application/pdf', 'size' => $document->file_size];
        }

        return $references;
    }

    /** @param array<string, mixed> $reference */
    public function binary(array $reference, ?int $schoolId): string
    {
        $document = ReportVerification::with('closure', 'school')->findOrFail($reference['document_id']);
        abort_unless(($schoolId === null || $schoolId === $document->school_id) && $this->isPublishable($document)
            && hash_equals((string) ($reference['sha256'] ?? ''), (string) $document->file_sha256), 404);
        $disk = Storage::disk($document->file_disk ?: 'local');
        abort_unless($disk->exists($document->file_path), 404);
        $binary = $disk->get($document->file_path);
        abort_unless(hash_equals($document->file_sha256, hash('sha256', $binary)), 404);

        return $binary;
    }
}
