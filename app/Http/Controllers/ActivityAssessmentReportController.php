<?php

namespace App\Http\Controllers;

use App\Models\ActivityAssessment;
use App\Models\ActivityAssessmentReport;
use App\Services\ActivityAssessmentReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ActivityAssessmentReportController extends Controller
{
    public function store(Request $request, int $assessmentId, ActivityAssessmentReportService $service): RedirectResponse
    {
        $assessment = ActivityAssessment::withoutGlobalScope('school')->findOrFail($assessmentId);
        $service->authorize($assessment);
        $data = $request->validate(['format' => ['required', 'in:complete,judges'], 'with_signatures' => ['required', 'boolean']]);
        $report = $service->issue($assessment, $data['format'], (bool) $data['with_signatures']);

        return redirect()->route('assessment-reports.show', $report->code);
    }

    public function show(string $code, ActivityAssessmentReportService $service): BinaryFileResponse
    {
        $report = ActivityAssessmentReport::where('code', $code)->firstOrFail();
        $service->authorize($report->assessment);
        abort_unless($service->intact($report), 409, 'Arsip cetakan tidak tersedia atau integritasnya tidak valid.');

        return response()->download(Storage::disk('local')->path($report->file_path),
            'rekap-penilaian-'.$report->id.'.pdf', ['Content-Type' => 'application/pdf', 'Cache-Control' => 'private, no-store']);
    }

    public function verify(string $code, ActivityAssessmentReportService $service): View
    {
        $report = ActivityAssessmentReport::where('code', $code)->firstOrFail();
        $valid = $service->intact($report);

        return view('reports.assessment-verification', compact('report', 'valid'));
    }
}
