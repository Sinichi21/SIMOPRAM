<?php

namespace App\Http\Controllers;

use App\Services\ReportVerificationService;
use App\Services\StudentDocumentAccessService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StudentDocumentController extends Controller
{
    public function download(
        Request $request,
        string $code,
        StudentDocumentAccessService $access,
        ReportVerificationService $reportService
    ): Response {
        $user = $request->user();

        abort_unless(
            $user?->hasRole('student'),
            403
        );

        /*
        |--------------------------------------------------------------------------
        | Penting
        |--------------------------------------------------------------------------
        |
        | Document dicari menggunakan query kepemilikan siswa.
        | Jadi mengetahui kode dokumen siswa lain tidak cukup untuk mengaksesnya.
        |
        */

        $document = $access->findForUser(
            $user,
            $code
        );

        /*
        |--------------------------------------------------------------------------
        | Dokumen dicabut
        |--------------------------------------------------------------------------
        */

        abort_if(
            $document->isRevoked(),
            409,
            'Dokumen telah dicabut dan tidak dapat diunduh.'
        );

        abort_unless(
            filled(
                $document->file_path
            ),
            404,
            'Arsip dokumen belum tersedia.'
        );

        /*
        |--------------------------------------------------------------------------
        | Ambil PDF asli dari arsip
        |--------------------------------------------------------------------------
        |
        | Service existing juga memverifikasi integritas hash file.
        |--------------------------------------------------------------------------
        */

        $binary = $reportService
            ->archivedPdfBinary(
                $document
            );

        /*
        |--------------------------------------------------------------------------
        | Audit download
        |--------------------------------------------------------------------------
        */

        $reportService->recordRedownload(
            $document
        );

        $filename = $document->file_name
            ?: (
                'dokumen-'
                .$document->code
                .'.pdf'
            );

        return response(
            $binary,
            200,
            [
                'Content-Type' => 'application/pdf',

                'Content-Disposition' => 'attachment; filename="'
                    .addslashes($filename)
                    .'"',

                'Content-Length' => (string) strlen(
                    $binary
                ),

                'X-Content-Type-Options' => 'nosniff',

                'Cache-Control' => 'private, no-store, max-age=0',
            ]
        );
    }
}
