<?php

namespace App\Services;

use App\Models\ReportVerification;
use App\Models\SemesterClosure;
use App\Support\SchoolContext;
use DateTimeInterface;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use JsonSerializable;
use RuntimeException;
use SplObjectStorage;
use Traversable;

class ReportVerificationService
{
    /*
    |--------------------------------------------------------------------------
    | Buat Identitas Dokumen
    |--------------------------------------------------------------------------
    |
    | Record belum dianggap selesai sampai archivePdf() berhasil menyimpan
    | binary PDF dan hash file.
    |--------------------------------------------------------------------------
    */

    public function issue(
        SemesterClosure $closure,
        string $documentType = 'grades',
        ?int $issuedBy = null
    ): ReportVerification {
        $schoolId =
            app(
                SchoolContext::class
            )->id();

        abort_unless(
            $schoolId,
            409,
            'Pilih sekolah aktif terlebih dahulu.'
        );

        abort_unless(
            (int) $closure->school_id
                === (int) $schoolId,
            404
        );

        if (
            ! $closure->snapshot_checksum
        ) {
            throw ValidationException::withMessages([
                'verification' => 'Snapshot semester belum memiliki checksum.',
            ]);
        }

        return ReportVerification::query()
            ->create([
                'school_id' => $schoolId,

                'semester_closure_id' => $closure->id,

                'source_type' => SemesterClosure::class,

                'source_id' => $closure->id,

                'code' => $this->generateUniqueCode(),

                'document_type' => $documentType,

                'snapshot_checksum' => $closure
                    ->snapshot_checksum,

                'file_disk' => 'local',

                'issued_by' => $issuedBy,

                'issued_at' => now(),

                'verification_count' => 0,
            ]);
    }

    /**
     * Terbitkan identitas dokumen generic yang tidak bergantung pada semester.
     * Existing report flow tetap memakai issue().
     *
     * @param  array<string, mixed>  $metadata
     */
    public function issueDocument(
        int $schoolId,
        string $documentType,
        string $sourceType,
        int $sourceId,
        string $snapshotChecksum,
        ?string $documentNumber = null,
        ?string $title = null,
        array $metadata = [],
        ?int $issuedBy = null
    ): ReportVerification {
        $activeSchoolId = app(SchoolContext::class)->id();

        abort_unless($activeSchoolId, 409, 'Pilih sekolah aktif terlebih dahulu.');
        abort_unless((int) $activeSchoolId === $schoolId, 404);

        $existing = ReportVerification::query()
            ->where('school_id', $schoolId)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('document_type', $documentType)
            ->first();

        if ($existing) {
            return $existing;
        }

        return ReportVerification::query()->create([
            'school_id' => $schoolId,
            'semester_closure_id' => null,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'code' => $this->generateUniqueCode(),
            'document_type' => $documentType,
            'document_number' => $documentNumber,
            'title' => $title,
            'snapshot_checksum' => $snapshotChecksum,
            'metadata' => $metadata,
            'file_disk' => 'local',
            'issued_by' => $issuedBy,
            'issued_at' => now(),
            'verification_count' => 0,
        ]);
    }

    /**
     * Terbitkan satu export laporan sebagai dokumen resmi baru.
     *
     * Berbeda dari issueDocument(), method ini sengaja selalu membuat record
     * baru karena setiap binary export merupakan artefak resmi yang berbeda.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function issueReportDocument(
        int $schoolId,
        string $documentType,
        string $snapshotChecksum,
        ?string $title = null,
        array $metadata = [],
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?int $issuedBy = null
    ): ReportVerification {
        $activeSchoolId = app(SchoolContext::class)->id();

        abort_unless(
            $activeSchoolId,
            409,
            'Pilih sekolah aktif terlebih dahulu.'
        );

        abort_unless(
            (int) $activeSchoolId === (int) $schoolId,
            404
        );

        return ReportVerification::query()->create([
            'school_id' => $schoolId,
            'semester_closure_id' => null,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'code' => $this->generateUniqueCode(),
            'document_type' => $documentType,
            'document_number' => null,
            'title' => $title,
            'snapshot_checksum' => $snapshotChecksum,
            'metadata' => $metadata,
            'file_disk' => 'local',
            'issued_by' => $issuedBy,
            'issued_at' => now(),
            'verification_count' => 0,
        ]);
    }

    /**
     * Checksum data sumber sebelum QR ditambahkan ke PDF.
     * file_sha256 tetap menjadi hash authoritative binary PDF final.
     */
    // public function contentChecksum(mixed $payload): string
    // {
    //     $json = json_encode(
    //         $payload,
    //         JSON_UNESCAPED_UNICODE
    //         | JSON_UNESCAPED_SLASHES
    //         | JSON_INVALID_UTF8_SUBSTITUTE
    //         | JSON_PARTIAL_OUTPUT_ON_ERROR
    //     );

    //     if (! is_string($json)) {
    //         $json = serialize($payload);
    //     }

    //     return hash('sha256', $json);
    // }

    public function contentChecksum(mixed $payload): string
    {
        $context = hash_init('sha256');

        $visiting = new SplObjectStorage;

        $this->updateChecksum(
            $context,
            $payload,
            $visiting
        );

        return hash_final($context);
    }

    /**
     * Hash payload secara incremental agar tidak membuat JSON besar di memory.
     */
    private function updateChecksum(
        \HashContext $context,
        mixed $value,
        SplObjectStorage $visiting
    ): void {
        if ($value === null) {
            hash_update($context, 'null;');

            return;
        }

        if (is_bool($value)) {
            hash_update(
                $context,
                $value ? 'bool:1;' : 'bool:0;'
            );

            return;
        }

        if (is_int($value)) {
            hash_update(
                $context,
                'int:'.$value.';'
            );

            return;
        }

        if (is_float($value)) {
            hash_update(
                $context,
                'float:'.json_encode(
                    $value,
                    JSON_PRESERVE_ZERO_FRACTION
                ).';'
            );

            return;
        }

        if (is_string($value)) {
            /*
            * Sertakan panjang untuk menghindari ambiguitas:
            *
            * ["ab", "c"]
            * tidak boleh sama dengan
            * ["a", "bc"]
            */
            hash_update(
                $context,
                'string:'.strlen($value).':'
            );

            /*
            * String besar langsung dimasukkan ke hash context.
            * Tidak dibuat salinan JSON baru.
            */
            hash_update(
                $context,
                $value
            );

            hash_update(
                $context,
                ';'
            );

            return;
        }

        if ($value instanceof DateTimeInterface) {
            hash_update(
                $context,
                'datetime:'.$value->format(DATE_ATOM).';'
            );

            return;
        }

        if (is_array($value)) {
            hash_update(
                $context,
                'array:'.count($value).'{'
            );

            foreach ($value as $key => $item) {
                $this->updateChecksum(
                    $context,
                    $key,
                    $visiting
                );

                $this->updateChecksum(
                    $context,
                    $item,
                    $visiting
                );
            }

            hash_update(
                $context,
                '}'
            );

            return;
        }

        /*
        * Perlindungan terhadap object circular reference.
        */
        if (is_object($value)) {
            if ($visiting->contains($value)) {
                hash_update(
                    $context,
                    'cycle:'.get_class($value).';'
                );

                return;
            }

            $visiting->attach($value);
        }

        try {
            if ($value instanceof Model) {
                hash_update(
                    $context,
                    'model:'.get_class($value).'{'
                );

                /*
                * Hash atribut model tanpa mengubah seluruh model
                * menjadi JSON besar.
                */
                $this->updateChecksum(
                    $context,
                    $value->getAttributes(),
                    $visiting
                );

                /*
                * Relasi yang memang sudah dimuat tetap ikut checksum.
                */
                foreach ($value->getRelations() as $name => $relation) {
                    hash_update(
                        $context,
                        'relation:'.$name.'{'
                    );

                    $this->updateChecksum(
                        $context,
                        $relation,
                        $visiting
                    );

                    hash_update(
                        $context,
                        '}'
                    );
                }

                hash_update(
                    $context,
                    '}'
                );

                return;
            }

            if ($value instanceof Collection) {
                hash_update(
                    $context,
                    'collection:'.get_class($value).':'.$value->count().'{'
                );

                foreach ($value as $key => $item) {
                    $this->updateChecksum(
                        $context,
                        $key,
                        $visiting
                    );

                    $this->updateChecksum(
                        $context,
                        $item,
                        $visiting
                    );
                }

                hash_update(
                    $context,
                    '}'
                );

                return;
            }

            if ($value instanceof Traversable) {
                hash_update(
                    $context,
                    'traversable:'.get_class($value).'{'
                );

                foreach ($value as $key => $item) {
                    $this->updateChecksum(
                        $context,
                        $key,
                        $visiting
                    );

                    $this->updateChecksum(
                        $context,
                        $item,
                        $visiting
                    );
                }

                hash_update(
                    $context,
                    '}'
                );

                return;
            }

            if ($value instanceof Arrayable) {
                hash_update(
                    $context,
                    'arrayable:'.get_class($value).'{'
                );

                $this->updateChecksum(
                    $context,
                    $value->toArray(),
                    $visiting
                );

                hash_update(
                    $context,
                    '}'
                );

                return;
            }

            if ($value instanceof JsonSerializable) {
                hash_update(
                    $context,
                    'jsonserializable:'.get_class($value).'{'
                );

                $this->updateChecksum(
                    $context,
                    $value->jsonSerialize(),
                    $visiting
                );

                hash_update(
                    $context,
                    '}'
                );

                return;
            }

            if (is_object($value)) {
                hash_update(
                    $context,
                    'object:'.get_class($value).'{'
                );

                $this->updateChecksum(
                    $context,
                    get_object_vars($value),
                    $visiting
                );

                hash_update(
                    $context,
                    '}'
                );

                return;
            }

            if (is_resource($value)) {
                hash_update(
                    $context,
                    'resource:'.get_resource_type($value).';'
                );

                return;
            }

            hash_update(
                $context,
                'unknown:'.get_debug_type($value).';'
            );
        } finally {
            if (
                is_object($value)
                && $visiting->contains($value)
            ) {
                $visiting->detach($value);
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Arsipkan Binary PDF
    |--------------------------------------------------------------------------
    |
    | Binary yang disimpan adalah binary yang sama dengan response download
    | pertama. Download ulang mengambil file ini, bukan membuat ulang PDF.
    |--------------------------------------------------------------------------
    */

    /** @param array<int, int|null>|null $signatoryUserIds */
    public function archivePdf(
        ReportVerification $verification,
        string $binary,
        string $filename,
        ?array $signatoryUserIds = null
    ): ReportVerification {
        $this->assertTenant(
            $verification
        );

        if ($verification->hasArchivedPdf()) {
            throw new RuntimeException('Arsip dokumen tidak dapat ditimpa. Terbitkan dokumen baru untuk perubahan.');
        }

        if (
            $binary === ''
        ) {
            throw new RuntimeException(
                'Binary PDF kosong dan tidak dapat diarsipkan.'
            );
        }

        app(DocumentApprovalService::class)->request($verification, $signatoryUserIds);

        $disk =
            $verification->file_disk
            ?: 'local';

        $path =
            'report-verifications/'
            .$verification->school_id
            .'/'
            .$verification->code
            .'.pdf';

        $written =
            Storage::disk(
                $disk
            )->put(
                $path,
                $binary
            );

        if (! $written) {
            throw new RuntimeException(
                'Arsip PDF gagal disimpan.'
            );
        }

        $verification->forceFill([
            'file_disk' => $disk,

            'file_path' => $path,

            'file_name' => $filename,

            'file_sha256' => hash(
                'sha256',
                $binary
            ),

            'file_size' => strlen(
                $binary
            ),

            'archived_at' => now(),
        ])->save();

        app(
            AssessmentAuditService::class
        )
            ->record(
                action: 'report.pdf.issued',

                subject: $verification,

                description: 'PDF dokumen resmi diterbitkan dan diarsipkan.',

                newValues: [
                    'document_type' => $verification
                        ->document_type,

                    'verification_code' => $verification
                        ->code,

                    'snapshot_checksum' => $verification
                        ->snapshot_checksum,

                    'file_sha256' => $verification
                        ->file_sha256,

                    'file_size' => $verification
                        ->file_size,
                ],

                metadata: [
                    'semester_closure_id' => $verification
                        ->semester_closure_id,

                    'file_name' => $verification
                        ->file_name,
                ],

                module: 'report_verification'
            );

        return $verification->fresh([
            'closure',
            'issuer',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Hapus Draft jika Generate/Archive Gagal
    |--------------------------------------------------------------------------
    */

    public function discardFailedIssue(
        ReportVerification $verification
    ): void {
        if (
            $verification->file_path
        ) {
            Storage::disk(
                $verification->file_disk
                ?: 'local'
            )->delete(
                $verification->file_path
            );
        }

        $verification->delete();
    }

    /*
    |--------------------------------------------------------------------------
    | Binary Arsip untuk Download Ulang
    |--------------------------------------------------------------------------
    */

    public function archivedPdfBinary(
        ReportVerification $verification
    ): string {
        $this->assertTenant(
            $verification
        );

        if (
            ! $verification
                ->hasArchivedPdf()
        ) {
            throw ValidationException::withMessages([
                'document' => 'Arsip PDF untuk dokumen ini belum tersedia.',
            ]);
        }

        $disk =
            $verification->file_disk
            ?: 'local';

        abort_unless(
            Storage::disk(
                $disk
            )->exists(
                $verification->file_path
            ),
            404,
            'Arsip PDF tidak ditemukan pada storage.'
        );

        $binary =
            Storage::disk(
                $disk
            )->get(
                $verification->file_path
            );

        $actualHash =
            hash(
                'sha256',
                $binary
            );

        if (
            ! hash_equals(
                (string) $verification
                    ->file_sha256,
                $actualHash
            )
        ) {
            throw ValidationException::withMessages([
                'document' => 'Integritas arsip PDF tidak valid. '
                    .'Hash file tidak sesuai dengan catatan penerbitan.',
            ]);
        }

        return $binary;
    }

    /*
    |--------------------------------------------------------------------------
    | Catat Download Ulang
    |--------------------------------------------------------------------------
    */

    public function recordRedownload(
        ReportVerification $verification
    ): void {
        $this->assertTenant(
            $verification
        );

        app(
            AssessmentAuditService::class
        )
            ->record(
                action: 'report.pdf.redownloaded',

                subject: $verification,

                description: 'Arsip PDF resmi diunduh ulang menggunakan identitas dokumen yang sama.',

                metadata: [
                    'verification_code' => $verification->code,

                    'file_sha256' => $verification
                        ->file_sha256,

                    'file_size' => $verification
                        ->file_size,
                ],

                module: 'report_verification'
            );
    }

    public function publicUrl(
        ReportVerification $verification
    ): string {
        return route(
            'reports.verify',
            [
                'code' => $verification->code,
            ]
        );
    }

    public function qrDataUri(
        ReportVerification $verification
    ): string {
        $writer =
            extension_loaded(
                'gd'
            )
                ? new PngWriter
                : new SvgWriter;

        $builder = Builder::create()
            ->writer($writer)
            ->writerOptions([])
            ->validateResult(false)
            ->data(
                $this->publicUrl(
                    $verification
                )
            )
            ->encoding(
                new Encoding('UTF-8')
            )
            ->errorCorrectionLevel(
                ErrorCorrectionLevel::Medium
            )
            ->size(220)
            ->margin(8)
            ->roundBlockSizeMode(
                RoundBlockSizeMode::Margin
            );

        return $builder
            ->build()
            ->getDataUri();
    }

    /*
    |--------------------------------------------------------------------------
    | Verifikasi Publik
    |--------------------------------------------------------------------------
    */

    public function findPublic(
        string $code
    ): ReportVerification {
        abort_unless(
            preg_match(
                '/^[a-f0-9]{48}$/',
                $code
            ) === 1,
            404
        );

        $verification =
            ReportVerification::query()
                ->with([
                    'school',
                    'closure.academicYear',
                    'closure.semester',
                    'source',
                ])
                ->where(
                    'code',
                    $code
                )
                ->firstOrFail();

        $verification->increment(
            'verification_count'
        );

        $verification->forceFill([
            'last_verified_at' => now(),
        ])->save();

        return $verification->fresh([
            'school',
            'closure.academicYear',
            'closure.semester',
            'source',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Cabut Dokumen
    |--------------------------------------------------------------------------
    */

    public function revoke(
        ReportVerification $verification,
        string $reason,
        ?int $revokedBy = null
    ): ReportVerification {
        $this->assertTenant(
            $verification
        );

        $reason =
            trim(
                $reason
            );

        if (
            mb_strlen(
                $reason
            ) < 5
        ) {
            throw ValidationException::withMessages([
                'revocationReason' => 'Alasan pencabutan minimal 5 karakter.',
            ]);
        }

        if (
            $verification->isRevoked()
        ) {
            return $verification;
        }

        $verification->forceFill([
            'revoked_at' => now(),

            'revoked_by' => $revokedBy,

            'revocation_reason' => $reason,
        ])->save();

        app(
            AssessmentAuditService::class
        )
            ->record(
                action: 'report.verification.revoked',

                subject: $verification,

                description: 'Dokumen terbit dicabut dari daftar dokumen resmi.',

                oldValues: [
                    'revoked_at' => null,

                    'revoked_by' => null,

                    'revocation_reason' => null,
                ],

                newValues: [
                    'revoked_at' => $verification
                        ->revoked_at
                        ?->toISOString(),

                    'revoked_by' => $verification
                        ->revoked_by,

                    'revocation_reason' => $verification
                        ->revocation_reason,
                ],

                metadata: [
                    'verification_code' => $verification
                        ->code,

                    'semester_closure_id' => $verification
                        ->semester_closure_id,

                    'snapshot_checksum' => $verification
                        ->snapshot_checksum,
                ],

                module: 'report_verification'
            );

        return $verification->fresh([
            'closure',
            'issuer',
            'revoker',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Tenant Guard untuk Operasi Admin
    |--------------------------------------------------------------------------
    */

    protected function assertTenant(
        ReportVerification $verification
    ): void {
        $schoolId =
            app(
                SchoolContext::class
            )->id();

        abort_unless(
            $schoolId,
            409,
            'Pilih sekolah aktif terlebih dahulu.'
        );

        abort_unless(
            (int) $verification->school_id
                === (int) $schoolId,
            404
        );
    }

    protected function generateUniqueCode(): string
    {
        do {
            $code =
                bin2hex(
                    random_bytes(
                        24
                    )
                );
        } while (
            ReportVerification::query()
                ->where(
                    'code',
                    $code
                )
                ->exists()
        );

        return $code;
    }
}
