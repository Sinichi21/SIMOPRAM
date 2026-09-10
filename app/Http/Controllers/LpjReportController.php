<?php

namespace App\Http\Controllers;

use App\Models\Semester;
use App\Services\LpjReportService;
use App\Services\ReportVerificationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LpjReportController extends Controller
{
    public function __invoke(
        Request $request,
        LpjReportService $reportService,
        ReportVerificationService $reportVerificationService
    ): Response {
        $validated = $request->validate([
            'academic_year_id' => ['required', 'integer'],
            'semester_id' => ['required', 'integer'],
            'period_type' => ['required', 'in:monthly,semester'],
            'month' => ['nullable', 'required_if:period_type,monthly', 'integer', 'between:1,12'],
        ]);

        $data = $reportService->build(
            (int) $validated['academic_year_id'],
            (int) $validated['semester_id'],
            $validated['period_type'],
            isset($validated['month']) ? (int) $validated['month'] : null
        );

        $periodLabel = $validated['period_type'] === 'monthly'
            ? $data['periodStart']->locale('id')->translatedFormat('F Y')
            : $data['semester']->name;

        $verification = $reportVerificationService->issueReportDocument(
            schoolId: (int) $data['school']->id,
            documentType: 'lpj',
            snapshotChecksum: $reportVerificationService->contentChecksum([
                'report' => 'lpj',
                'academic_year_id' => $validated['academic_year_id'],
                'semester_id' => $validated['semester_id'],
                'period_type' => $validated['period_type'],
                'month' => $validated['month'] ?? null,
                'data' => $data,
            ]),
            title: 'LPJ Pramuka - '.$periodLabel,
            metadata: [
                'academic_year' => $data['academicYear']->name,
                'semester' => $data['semester']->name,
                'period_type' => $validated['period_type'],
                'period' => $periodLabel,
                'month' => $validated['month'] ?? null,
            ],
            sourceType: Semester::class,
            sourceId: (int) $data['semester']->id,
            issuedBy: $request->user()?->id
        );

        $data['verification'] = $verification;
        $data['verificationUrl'] = $reportVerificationService->publicUrl($verification);
        $data['verificationQrDataUri'] = $reportVerificationService->qrDataUri($verification);

        $period = $validated['period_type'] === 'monthly'
            ? $data['periodStart']->locale('id')->translatedFormat('F-Y')
            : Str::slug($data['semester']->name);

        $filename = 'lpj-pramuka-'
            .Str::slug($data['school']->name)
            .'-'
            .Str::slug($period)
            .'-'
            .$verification->issued_at->format('Ymd-His')
            .'.pdf';

        $pdf = Pdf::loadView('reports.pdf.lpj', $data)
            ->setPaper('a4', 'portrait');

        try {
            $binary = $pdf->output();

            $reportVerificationService->archivePdf(
                verification: $verification,
                binary: $binary,
                filename: $filename,
                signatoryUserIds: [
                    data_get($data, 'documentSetting.principal_signatory_user_id'),
                    data_get($data, 'documentSetting.responsible_signatory_user_id'),
                    data_get($data, 'documentSetting.coordinator_signatory_user_id'),
                ]
            );
        } catch (Throwable $exception) {
            $reportVerificationService->discardFailedIssue($verification);
            throw $exception;
        }

        return response(
            $binary,
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'
                    .addslashes($filename)
                    .'"',
                'Content-Length' => (string) strlen($binary),
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store, max-age=0',
            ]
        );
    }
}
