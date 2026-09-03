<?php

namespace App\Services;

use App\Models\ReportVerification;
use App\Models\SemesterClosure;
use App\Support\SchoolContext;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

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
                'verification' =>
                    'Snapshot semester belum memiliki checksum.',
            ]);
        }

        return ReportVerification::query()
            ->create([
                'school_id' =>
                    $schoolId,

                'semester_closure_id' =>
                    $closure->id,

                'code' =>
                    $this->generateUniqueCode(),

                'document_type' =>
                    $documentType,

                'snapshot_checksum' =>
                    $closure
                        ->snapshot_checksum,

                'file_disk' =>
                    'local',

                'issued_by' =>
                    $issuedBy,

                'issued_at' =>
                    now(),

                'verification_count' =>
                    0,
            ]);
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

    public function archivePdf(
        ReportVerification $verification,
        string $binary,
        string $filename
    ): ReportVerification {
        $this->assertTenant(
            $verification
        );

        if (
            $binary === ''
        ) {
            throw new RuntimeException(
                'Binary PDF kosong dan tidak dapat diarsipkan.'
            );
        }

        $disk =
            $verification->file_disk
            ?: 'local';

        $path =
            'report-verifications/'
            . $verification->school_id
            . '/'
            . $verification->code
            . '.pdf';

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
            'file_disk' =>
                $disk,

            'file_path' =>
                $path,

            'file_name' =>
                $filename,

            'file_sha256' =>
                hash(
                    'sha256',
                    $binary
                ),

            'file_size' =>
                strlen(
                    $binary
                ),

            'archived_at' =>
                now(),
        ])->save();

        app(
            AssessmentAuditService::class
        )
            ->record(
                action:
                    'report.pdf.issued',

                subject:
                    $verification,

                description:
                    'PDF rekap nilai snapshot diterbitkan dan diarsipkan.',

                newValues: [
                    'document_type' =>
                        $verification
                            ->document_type,

                    'verification_code' =>
                        $verification
                            ->code,

                    'snapshot_checksum' =>
                        $verification
                            ->snapshot_checksum,

                    'file_sha256' =>
                        $verification
                            ->file_sha256,

                    'file_size' =>
                        $verification
                            ->file_size,
                ],

                metadata: [
                    'semester_closure_id' =>
                        $verification
                            ->semester_closure_id,

                    'file_name' =>
                        $verification
                            ->file_name,
                ],

                module:
                    'report_verification'
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
                'document' =>
                    'Arsip PDF untuk dokumen ini belum tersedia.',
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
                'document' =>
                    'Integritas arsip PDF tidak valid. '
                    . 'Hash file tidak sesuai dengan catatan penerbitan.',
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
                action:
                    'report.pdf.redownloaded',

                subject:
                    $verification,

                description:
                    'Arsip PDF resmi diunduh ulang menggunakan identitas dokumen yang sama.',

                metadata: [
                    'verification_code' =>
                        $verification->code,

                    'file_sha256' =>
                        $verification
                            ->file_sha256,

                    'file_size' =>
                        $verification
                            ->file_size,
                ],

                module:
                    'report_verification'
            );
    }


    public function publicUrl(
        ReportVerification $verification
    ): string {
        return route(
            'reports.verify',
            [
                'code' =>
                    $verification->code,
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
                ? new PngWriter()
                : new SvgWriter();

        $builder =
            new Builder(
                writer:
                    $writer,

                writerOptions:
                    [],

                validateResult:
                    false,

                data:
                    $this->publicUrl(
                        $verification
                    ),

                encoding:
                    new Encoding(
                        'UTF-8'
                    ),

                errorCorrectionLevel:
                    ErrorCorrectionLevel::Medium,

                size:
                    220,

                margin:
                    8,

                roundBlockSizeMode:
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
            'last_verified_at' =>
                now(),
        ])->save();

        return $verification->fresh([
            'school',
            'closure.academicYear',
            'closure.semester',
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
                'revocationReason' =>
                    'Alasan pencabutan minimal 5 karakter.',
            ]);
        }

        if (
            $verification->isRevoked()
        ) {
            return $verification;
        }

        $verification->forceFill([
            'revoked_at' =>
                now(),

            'revoked_by' =>
                $revokedBy,

            'revocation_reason' =>
                $reason,
        ])->save();

        app(
            AssessmentAuditService::class
        )
            ->record(
                action:
                    'report.verification.revoked',

                subject:
                    $verification,

                description:
                    'Dokumen terbit dicabut dari daftar dokumen resmi.',

                oldValues: [
                    'revoked_at' =>
                        null,

                    'revoked_by' =>
                        null,

                    'revocation_reason' =>
                        null,
                ],

                newValues: [
                    'revoked_at' =>
                        $verification
                            ->revoked_at
                            ?->toISOString(),

                    'revoked_by' =>
                        $verification
                            ->revoked_by,

                    'revocation_reason' =>
                        $verification
                            ->revocation_reason,
                ],

                metadata: [
                    'verification_code' =>
                        $verification
                            ->code,

                    'semester_closure_id' =>
                        $verification
                            ->semester_closure_id,

                    'snapshot_checksum' =>
                        $verification
                            ->snapshot_checksum,
                ],

                module:
                    'report_verification'
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
