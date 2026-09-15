<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Announcement;
use App\Services\PublishedContentDocuments;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;

class PublicContentMediaController extends Controller
{
    public function __invoke(string $kind, int $id, int $index): Response
    {
        abort_unless(in_array($kind, ['activities', 'announcements'], true), 404);
        $record = ($kind === 'activities' ? Activity::withoutGlobalScope('school') : Announcement::withoutGlobalScope('school'))->findOrFail($id);
        abort_unless($record->is_public && in_array($record->status, $kind === 'activities' ? ['published', 'ongoing', 'completed'] : ['published'], true)
            && (! $record->published_at || $record->published_at->lte(now())), 404);
        if ($record->school_id) {
            abort_unless($record->school?->is_active, 404);
        } elseif ($kind === 'activities') {
            abort_unless($record->approval_status === 'approved', 404);
        }
        if ($kind === 'announcements') {
            abort_if($record->expires_at?->lte(now()), 404);
        }
        $file = ($record->attachments ?? [])[$index] ?? null;
        if (isset($file['document_id'])) {
            $binary = app(PublishedContentDocuments::class)->binary($file, $record->school_id);

            return response($binary, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => HeaderUtils::makeDisposition('attachment', $file['name'], Str::ascii(str_replace('%', '_', $file['name']))),
                'Cache-Control' => 'private, no-store',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }
        abort_unless($file && Storage::disk('local')->exists($file['path']), 404);

        return response()->download(Storage::disk('local')->path($file['path']), $file['name'], ['X-Content-Type-Options' => 'nosniff']);
    }
}
