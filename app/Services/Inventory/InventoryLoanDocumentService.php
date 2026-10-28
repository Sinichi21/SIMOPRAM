<?php

namespace App\Services\Inventory;

use App\Models\InventoryLoan;
use App\Models\InventoryLoanDocument;
use App\Support\SchoolContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class InventoryLoanDocumentService
{
    public function issue(int $loanId, string $type): InventoryLoanDocument
    {
        if (! in_array($type, ['handover', 'return'], true)) {
            throw ValidationException::withMessages(['type' => 'Jenis berita acara tidak valid.']);
        }

        $schoolId = app(SchoolContext::class)->id();
        abort_unless($schoolId, 409, 'Pilih sekolah aktif terlebih dahulu.');

        return DB::transaction(function () use ($loanId, $type, $schoolId): InventoryLoanDocument {
            $loan = InventoryLoan::query()->where('school_id', $schoolId)
                ->lockForUpdate()->with(['items.item', 'items.owner', 'items.location', 'events'])
                ->findOrFail($loanId);

            $events = $loan->events->where('event_type', $type)->sortBy('occurred_at');
            if ($events->isEmpty()) {
                throw ValidationException::withMessages(['document' => 'Belum ada transaksi fisik untuk jenis berita acara ini.']);
            }

            // One issued snapshot per loan and type until a separate, reviewed correction workflow exists.
            $existing = InventoryLoanDocument::query()->where('school_id', $schoolId)
                ->where('loan_id', $loanId)->where('document_type', $type)
                ->where('status', 'issued')->first();
            if ($existing) {
                return $existing;
            }

            $documentNumber = sprintf('BA-INV-%d-%s-%06d', $schoolId, strtoupper($type === 'handover' ? 'ST' : 'KB'), $loanId);
            $pdf = Pdf::loadView('inventory.documents.preview', [
                'loan' => $loan,
                'type' => $type,
                'events' => $events,
                'issued' => true,
                'documentNumber' => $documentNumber,
            ])->setPaper('a4')->output();

            $path = sprintf('inventory/%d/loan-documents/%d-%s-v1.pdf', $schoolId, $loanId, $type);
            $disk = Storage::disk('local');
            if ($disk->exists($path)) {
                throw ValidationException::withMessages(['document' => 'Berkas dokumen sudah ada tanpa catatan penerbitan. Periksa secara manual.']);
            }
            if (! $disk->put($path, $pdf)) {
                throw ValidationException::withMessages(['document' => 'Gagal menyimpan berkas berita acara.']);
            }

            try {
                return InventoryLoanDocument::query()->create([
                    'school_id' => $schoolId,
                    'loan_id' => $loanId,
                    'document_type' => $type,
                    'version' => 1,
                    'document_number' => $documentNumber,
                    'storage_path' => $path,
                    'sha256' => hash('sha256', $pdf),
                    'status' => 'issued',
                    'issued_by' => auth()->id(),
                    'issued_at' => now(),
                ]);
            } catch (\Throwable $exception) {
                $disk->delete($path);
                throw $exception;
            }
        }, 3);
    }
}
