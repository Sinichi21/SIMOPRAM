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
    public function show(Request $request, string $code, SchoolContext $schoolContext): View
    {
        abort_unless($request->user()?->can('report_verifications.view'), 403);
        abort_unless($schoolContext->hasSchool(), 409, 'Pilih sekolah aktif terlebih dahulu.');

        $verification = $this->findTenantDocument($code, $schoolContext);

        return view('reports.published-document-show', [
            'verification' => $verification,
            'publicUrl' => route('reports.verify', ['code' => $verification->code]),
            'status' => $verification->publicStatus(),
        ]);
    }

    public function download(
        Request $request,
        string $code,
        SchoolContext $schoolContext,
        ReportVerificationService $service
    ): Response {
        abort_unless(
            $request->user()?->can('report_verifications.view')
            && $request->user()?->can('reports.export'),
            403
        );
        abort_unless($schoolContext->hasSchool(), 409, 'Pilih sekolah aktif terlebih dahulu.');

        $verification = $this->findTenantDocument($code, $schoolContext);
        abort_if($verification->isRevoked(), 409, 'Dokumen telah dicabut dan tidak dapat diunduh ulang.');

        $binary = $service->archivedPdfBinary($verification);
        $service->recordRedownload($verification);
        $filename = $verification->file_name ?: 'dokumen-'.$verification->code.'.pdf';

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.addslashes($filename).'"',
            'Content-Length' => (string) strlen($binary),
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    protected function findTenantDocument(string $code, SchoolContext $schoolContext): ReportVerification
    {
        $code = strtolower($code);
        abort_unless(preg_match('/^[a-f0-9]{48}$/', $code) === 1, 404);

        return ReportVerification::query()
            ->with([
                'school',
                'closure.academicYear',
                'closure.semester',
                'issuer',
                'revoker',
                'source',
            ])
            ->where('school_id', $schoolContext->id())
            ->where('code', $code)
            ->firstOrFail();
    }
}
