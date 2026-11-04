<?php

namespace App\Http\Controllers;

use App\Models\GudepCashEvidence;
use App\Support\SchoolContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GudepCashEvidenceController extends Controller
{
    public function download(Request $request, int $evidence): StreamedResponse
    {
        abort_unless($request->user()?->can('gudep.finance.view'), 403);
        $schoolId = app(SchoolContext::class)->id();
        abort_unless($schoolId !== null, 409);

        $file = GudepCashEvidence::query()->where('school_id', $schoolId)
            ->with('cashEntry')->findOrFail($evidence);

        abort_unless(
            $file->cashEntry !== null
                && (int) $file->cashEntry->school_id === (int) $schoolId
                && (int) $file->cashEntry->scout_group_id === (int) $file->scout_group_id,
            404
        );

        $disk = Storage::disk('local');
        abort_unless($file->disk === 'local' && $disk->exists($file->path), 404);

        $bytes = $disk->get($file->path);
        abort_unless(is_string($bytes), 404);
        abort_unless(hash_equals($file->sha256, hash('sha256', $bytes)), 409,
            'Bukti kas tidak lolos pemeriksaan integritas.');

        return $disk->download($file->path, $file->original_filename, [
            'Content-Type' => $file->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
