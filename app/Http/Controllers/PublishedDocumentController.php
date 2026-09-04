<?php

namespace App\Http\Controllers;

use App\Models\ReportVerification;
use App\Services\ReportVerificationService;
use App\Support\SchoolContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublishedDocumentController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Detail Dokumen
    |--------------------------------------------------------------------------
    */

    public function show(
        Request $request,
        string $code,
        SchoolContext $schoolContext
    ): View {
        abort_unless(
            $request
                ->user()
                ?->can(
                    'report_verifications.view'
                ),
            403
        );

        abort_unless(
            $schoolContext
                ->hasSchool(),
            409,
            'Pilih sekolah aktif terlebih dahulu.'
        );

        $verification =
            $this->findTenantDocument(
                $code,
                $schoolContext
            );

        return view(
            'reports.published-document-show',
            [
                'verification' => $verification,

                'publicUrl' => route(
                    'reports.verify',
                    [
                        'code' => $verification
                            ->code,
                    ]
                ),

                'status' => $verification
                    ->publicStatus(),
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Download Ulang Binary yang Sama
    |--------------------------------------------------------------------------
    */

    public function download(
        Request $request,
        string $code,
        SchoolContext $schoolContext,
        ReportVerificationService $service
    ): Response {
        abort_unless(
            $request
                ->user()
                ?->can(
                    'report_verifications.view'
                )
            &&
            $request
                ->user()
                ?->can(
                    'reports.export'
                ),
            403
        );

        abort_unless(
            $schoolContext
                ->hasSchool(),
            409,
            'Pilih sekolah aktif terlebih dahulu.'
        );

        $verification =
            $this->findTenantDocument(
                $code,
                $schoolContext
            );

        /*
        |--------------------------------------------------------------------------
        | Dokumen revoked tetap dapat diverifikasi, tetapi tidak dapat lagi
        | diunduh sebagai dokumen resmi dari panel admin.
        |--------------------------------------------------------------------------
        */

        abort_if(
            $verification
                ->isRevoked(),
            409,
            'Dokumen telah dicabut dan tidak dapat diunduh ulang.'
        );

        $binary =
            $service
                ->archivedPdfBinary(
                    $verification
                );

        $service
            ->recordRedownload(
                $verification
            );

        $filename =
            $verification
                ->file_name
            ?: (
                'rekap-nilai-'
                .$verification
                    ->code
                .'.pdf'
            );

        return response(
            $binary,
            200,
            [
                'Content-Type' => 'application/pdf',

                'Content-Disposition' => 'attachment; filename="'
                    .addslashes(
                        $filename
                    )
                    .'"',

                'Content-Length' => (string) strlen(
                    $binary
                ),

                'X-Content-Type-Options' => 'nosniff',

                'Cache-Control' => 'private, no-store, max-age=0',
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Find Tenant Document
    |--------------------------------------------------------------------------
    */

    protected function findTenantDocument(
        string $code,
        SchoolContext $schoolContext
    ): ReportVerification {
        abort_unless(
            preg_match(
                '/^[a-f0-9]{48}$/',
                strtolower(
                    $code
                )
            ) === 1,
            404
        );

        return ReportVerification::query()
            ->with([
                'school',
                'closure.academicYear',
                'closure.semester',
                'issuer',
                'revoker',
            ])
            ->where(
                'school_id',
                $schoolContext
                    ->id()
            )
            ->where(
                'code',
                strtolower(
                    $code
                )
            )
            ->firstOrFail();
    }
}
