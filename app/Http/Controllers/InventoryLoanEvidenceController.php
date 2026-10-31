<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\InventoryLoan;
use App\Models\InventoryLoanEvent;
use App\Models\InventoryLoanEvidence;
use App\Support\SchoolContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InventoryLoanEvidenceController extends Controller
{
    public function store(Request $request, int $event): RedirectResponse
    {
        abort_unless($request->user()?->can('inventory.handover.process')
            || $request->user()?->can('inventory.returns.process'), 403);
        $schoolId = app(SchoolContext::class)->id();
        abort_unless($schoolId, 409);
        $record = InventoryLoanEvent::query()->where('school_id', $schoolId)
            ->whereIn('event_type', ['handover', 'return', 'damage', 'loss'])->findOrFail($event);
        $permission = $record->event_type === 'handover' ? 'inventory.handover.process' : 'inventory.returns.process';
        abort_unless($request->user()->can($permission), 403);
        $data = $request->validate([
            'photo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
        $photo = $data['photo'];
        $bytes = file_get_contents($photo->getRealPath());
        if ($bytes === false) {
            throw ValidationException::withMessages(['photo' => 'Foto tidak dapat dibaca.']);
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
        abort_unless(in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true), 422);
        $extension = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime];
        $path = 'inventory/loan-evidence/'.$schoolId.'/'.$record->loan_id.'/'.Str::uuid().'.'.$extension;
        $disk = Storage::disk('local');
        abort_unless($disk->put($path, $bytes), 500, 'Gagal menyimpan bukti.');
        try {
            InventoryLoanEvidence::query()->create([
                'loan_id' => $record->loan_id,
                'loan_event_id' => $record->id,
                'disk' => 'local',
                'path' => $path,
                'mime_type' => $mime,
                'size_bytes' => strlen($bytes),
                'sha256' => hash('sha256', $bytes),
                'uploaded_by' => $request->user()->id,
            ]);
        } catch (\Throwable $exception) {
            $disk->delete($path);
            throw $exception;
        }

        return back()->with('status', 'Foto bukti transaksi berhasil disimpan.');
    }

    public function show(Request $request, int $evidence): Response
    {
        abort_unless($request->user()?->can('inventory.view'), 403);
        $schoolId = app(SchoolContext::class)->id();
        abort_unless($schoolId, 409);
        $record = InventoryLoanEvidence::query()->where('school_id', $schoolId)->findOrFail($evidence);
        abort_unless(InventoryLoan::query()->where('school_id', $schoolId)->whereKey($record->loan_id)->exists(), 404);
        $disk = Storage::disk('local');
        abort_unless($record->disk === 'local' && $disk->exists($record->path), 404);
        $bytes = $disk->get($record->path);
        abort_unless(is_string($bytes) && hash_equals($record->sha256, hash('sha256', $bytes)), 409);

        return response($bytes, 200, [
            'Content-Type' => $record->mime_type,
            'Content-Disposition' => 'inline; filename="bukti-'.$record->id.'"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ]);
    }
}
