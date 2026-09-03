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
use Illuminate\Validation\ValidationException;

class ReportVerificationService
{
    /*
    |--------------------------------------------------------------------------
    | Terbitkan Kode Verifikasi
    |--------------------------------------------------------------------------
    |
    | Satu pemanggilan = satu dokumen PDF snapshot = satu kode unik.
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

        $verification =
            ReportVerification::query()
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

                    'issued_by' =>
                        $issuedBy,

                    'issued_at' =>
                        now(),

                    'verification_count' =>
                        0,
                ]);

        /*
        |--------------------------------------------------------------------------
        | Audit internal
        |--------------------------------------------------------------------------
        */

        if (
            app(
                SchoolContext::class
            )->hasSchool()
        ) {
            app(
                AssessmentAuditService::class
            )
                ->record(
                    action:
                        'report.pdf.issued',

                    subject:
                        $verification,

                    description:
                        'PDF rekap nilai snapshot diterbitkan dengan kode verifikasi publik.',

                    newValues: [
                        'document_type' =>
                            $documentType,

                        'verification_code' =>
                            $verification->code,

                        'snapshot_checksum' =>
                            $verification
                                ->snapshot_checksum,
                    ],

                    metadata: [
                        'semester_closure_id' =>
                            $closure->id,

                        'closure_version' =>
                            $closure->version,
                    ],

                    module:
                        'report_verification'
                );
        }

        return $verification;
    }


    /*
    |--------------------------------------------------------------------------
    | URL Publik
    |--------------------------------------------------------------------------
    */

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


    /*
    |--------------------------------------------------------------------------
    | QR Data URI
    |--------------------------------------------------------------------------
    |
    | PNG dipakai bila GD tersedia.
    | SVG menjadi fallback agar fitur tetap bekerja tanpa ext-gd.
    |--------------------------------------------------------------------------
    */

    public function qrDataUri(
        ReportVerification $verification
    ): string {
        $url =
            $this->publicUrl(
                $verification
            );

        $builder =
            Builder::create()
                ->data(
                    $url
                )
                ->encoding(
                    new Encoding(
                        'UTF-8'
                    )
                )
                ->errorCorrectionLevel(
                    ErrorCorrectionLevel::Medium
                )
                ->size(
                    220
                )
                ->margin(
                    8
                )
                ->roundBlockSizeMode(
                    RoundBlockSizeMode::Margin
                )
                ->validateResult(
                    false
                );

        if (
            extension_loaded(
                'gd'
            )
        ) {
            $result =
                $builder
                    ->writer(
                        new PngWriter()
                    )
                    ->build();
        } else {
            $result =
                $builder
                    ->writer(
                        new SvgWriter()
                    )
                    ->build();
        }

        return $result
            ->getDataUri();
    }


    /*
    |--------------------------------------------------------------------------
    | Ambil untuk Verifikasi Publik
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

        /*
        |--------------------------------------------------------------------------
        | Statistik akses saja. Tidak memengaruhi validitas dokumen.
        |--------------------------------------------------------------------------
        */

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
    | Cabut Verifikasi
    |--------------------------------------------------------------------------
    */

    public function revoke(
        ReportVerification $verification,
        string $reason,
        ?int $revokedBy = null
    ): ReportVerification {
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
                'revocation_reason' =>
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

        return $verification->fresh();
    }


    /*
    |--------------------------------------------------------------------------
    | Kode 48 Karakter Hexadecimal
    |--------------------------------------------------------------------------
    */

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
