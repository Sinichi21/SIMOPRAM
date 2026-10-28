<?php

namespace App\Http\Controllers;

use App\Models\InventoryLoan;
use App\Support\SchoolContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class InventoryLoanDocumentPreviewController extends Controller
{
    public function __invoke(Request $request, int $loan, string $type): Response
    {
        abort_unless($request->user()?->can('inventory.view'), 403);
        abort_unless(in_array($type, ['handover', 'return'], true), 404);

        $schoolId = app(SchoolContext::class)->id();
        abort_unless($schoolId, 409, 'Pilih sekolah aktif terlebih dahulu.');

        $record = InventoryLoan::query()
            ->where('school_id', $schoolId)
            ->with(['items.item', 'items.owner', 'items.location', 'events'])
            ->findOrFail($loan);

        abort_unless($type === 'handover'
            ? $record->events->contains('event_type', 'handover')
            : $record->events->contains('event_type', 'return'), 404);

        $events = $record->events->where('event_type', $type)->sortBy('occurred_at');
        $pdf = Pdf::loadView('inventory.documents.preview', [
            'loan' => $record,
            'type' => $type,
            'events' => $events,
        ])->setPaper('a4');

        return $pdf->stream('DRAFT-BA-'.$type.'-'.$record->loan_number.'.pdf');
    }
}
