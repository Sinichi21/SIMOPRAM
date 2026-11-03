<?php

namespace App\Http\Controllers;

use App\Models\GudepOfficerDocument;
use App\Support\SchoolContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GudepOfficerDocumentController extends Controller
{
    public function download(Request $request, int $document): StreamedResponse
    {
        abort_unless($request->user()?->can('gudep.view'), 403);
        $schoolId = app(SchoolContext::class)->id();
        abort_unless($schoolId !== null, 409);

        $attachment = GudepOfficerDocument::query()
            ->where('school_id', $schoolId)
            ->with('officer')
            ->findOrFail($document);

        abort_unless(
            $attachment->officer !== null
                && (int) $attachment->officer->scout_group_id === (int) $attachment->scout_group_id
                && (int) $attachment->officer->school_id === (int) $schoolId,
            404
        );

        $disk = Storage::disk('local');
        abort_unless($attachment->disk === 'local' && $disk->exists($attachment->path), 404);
        $binary = $disk->get($attachment->path);
        abort_unless(is_string($binary), 404);
        abort_unless(
            hash_equals($attachment->sha256, hash('sha256', $binary)),
            409,
            'Arsip SK tidak lolos pemeriksaan integritas.'
        );

        return $disk->download(
            $attachment->path,
            $attachment->original_filename,
            [
                'Content-Type' => $attachment->mime_type,
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store',
            ]
        );
    }
}
