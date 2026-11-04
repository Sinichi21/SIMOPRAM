<?php

namespace App\Http\Controllers;

use App\Models\GudepAchievementEvidence;
use App\Support\SchoolContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GudepAchievementEvidenceController extends Controller
{
    public function download(Request $request, int $evidence): StreamedResponse
    {
        abort_unless($request->user()?->can('gudep.view'), 403);

        $schoolId = app(SchoolContext::class)->id();
        abort_unless($schoolId !== null, 409);

        $attachment = GudepAchievementEvidence::query()->where('school_id', $schoolId)
            ->with('achievement')->findOrFail($evidence);

        abort_unless($attachment->achievement !== null
            && (int) $attachment->achievement->school_id === (int) $schoolId
            && (int) $attachment->achievement->scout_group_id === (int) $attachment->scout_group_id, 404);

        $disk = Storage::disk('local');
        abort_unless($attachment->disk === 'local' && $disk->exists($attachment->path), 404);

        $data = $disk->get($attachment->path);
        abort_unless(is_string($data), 404);
        abort_unless(hash_equals($attachment->sha256, hash('sha256', $data)), 409,
            'Lampiran prestasi tidak lolos pemeriksaan integritas.');

        return $disk->download($attachment->path, $attachment->original_filename, [
            'Content-Type' => $attachment->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
