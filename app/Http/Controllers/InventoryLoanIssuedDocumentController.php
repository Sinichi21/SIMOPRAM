<?php

namespace App\Http\Controllers;

use App\Models\InventoryLoanDocument;
use App\Models\InventoryLoanDocumentPrint;
use App\Services\Inventory\InventoryLoanDocumentService;
use App\Support\SchoolContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class InventoryLoanIssuedDocumentController extends Controller
{
    public function issue(Request $request, int $loan, string $type, InventoryLoanDocumentService $service): RedirectResponse
    {
        abort_unless($request->user()?->can('inventory.documents.reprint'), 403);
        abort_unless(in_array($type, ['handover', 'return'], true), 404);

        $document = $service->issue($loan, $type);

        return redirect()->route('inventory.loans.documents.show', ['document' => $document->id]);
    }

    public function logReprint(Request $request, int $document): RedirectResponse
    {
        abort_unless($request->user()?->can('inventory.documents.reprint'), 403);
        $schoolId = app(SchoolContext::class)->id();
        abort_unless($schoolId, 409);
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $record = InventoryLoanDocument::query()->where('school_id', $schoolId)
            ->where('status', 'issued')->findOrFail($document);

        // Audit the reprint only if the immutable PDF still exists and passes integrity verification.
        $this->verifiedPdf($record);

        InventoryLoanDocumentPrint::query()->create([
            'school_id' => $schoolId,
            'document_id' => $record->id,
            'printed_by' => $request->user()->id,
            'printed_at' => now(),
            'purpose' => 'reprint',
            'reason' => trim($data['reason']),
        ]);

        return redirect()->route('inventory.loans.documents.show', ['document' => $record->id]);
    }

    public function show(Request $request, int $document): Response
    {
        abort_unless($request->user()?->can('inventory.view'), 403);
        $schoolId = app(SchoolContext::class)->id();
        abort_unless($schoolId, 409);

        $record = InventoryLoanDocument::query()->where('school_id', $schoolId)
            ->where('status', 'issued')->findOrFail($document);
        $bytes = $this->verifiedPdf($record);

        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="BA-'.basename($record->storage_path).'"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
    private function verifiedPdf(InventoryLoanDocument $record): string
    {
        $disk = Storage::disk('local');
        if (! $record->storage_path || ! $disk->exists($record->storage_path)) {
            abort(404, 'Berkas berita acara tidak tersedia.');
        }

        $bytes = $disk->get($record->storage_path);
        if (! is_string($bytes) || ! $record->sha256 || ! hash_equals((string) $record->sha256, hash('sha256', $bytes))) {
            abort(409, 'Integritas berkas berita acara tidak sesuai.');
        }

        return $bytes;
    }

}
