<?php

namespace App\Http\Controllers;

use App\Services\LpjReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class LpjReportController extends Controller
{
    public function __invoke(Request $request, LpjReportService $reportService): Response
    {
        $validated = $request->validate([
            'academic_year_id' => ['required', 'integer'],
            'semester_id' => ['required', 'integer'],
            'period_type' => ['required', 'in:monthly,semester'],
            'month' => ['nullable', 'required_if:period_type,monthly', 'integer', 'between:1,12'],
        ]);

        $data = $reportService->build((int) $validated['academic_year_id'], (int) $validated['semester_id'], $validated['period_type'], isset($validated['month']) ? (int) $validated['month'] : null);
        $period = $validated['period_type'] === 'monthly' ? $data['periodStart']->translatedFormat('F-Y') : Str::slug($data['semester']->name);
        $filename = 'lpj-pramuka-'.Str::slug($data['school']->name).'-'.Str::slug($period).'.pdf';

        return Pdf::loadView('reports.pdf.lpj', $data)->setPaper('a4', 'portrait')->download($filename);
    }
}
