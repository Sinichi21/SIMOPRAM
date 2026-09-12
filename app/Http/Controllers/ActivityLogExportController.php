<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use App\Services\ActivityLogQuery;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class ActivityLogExportController extends Controller
{
    public function __invoke(Request $request, ActivityLogQuery $query, ActivityLogger $logger): Response
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);
        $filters = $request->validate($query->rules());
        $logs = $query->build($filters)->limit(config('activity-log.pdf_limit') + 1)->get();
        if ($logs->count() > config('activity-log.pdf_limit')) {
            throw ValidationException::withMessages(['export' => 'Hasil melebihi '.config('activity-log.pdf_limit').' log. Persempit rentang tanggal atau filter sebelum mengekspor.']);
        }

        $response = Pdf::loadView('exports.activity-logs', ['logs' => $logs, 'filters' => $filters, 'exportedBy' => $request->user()->name])
            ->setPaper('a4', 'landscape')->setOption('isRemoteEnabled', false)
            ->download('log-aktivitas-'.$filters['from'].'-'.$filters['to'].'.pdf');
        $logger->record('reports', 'exported', new: ['start_date' => $filters['from'], 'end_date' => $filters['to'], 'count' => $logs->count(), 'format' => 'pdf'], description: 'Mengekspor log aktivitas ke PDF');
        $request->attributes->set('activity_download_logged', true);

        return $response;
    }
}
