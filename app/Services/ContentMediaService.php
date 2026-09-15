<?php

namespace App\Services;

use App\Models\Activity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ContentMediaService
{
    /** @param array<int, UploadedFile> $uploads
     * @param  array<int, int|string>  $removeIndexes
     */
    public function save(Model $record, array $uploads = [], ?UploadedFile $banner = null, bool $removeBanner = false, array $removeIndexes = [], array $documentIds = []): void
    {
        validator(['attachmentUploads' => $uploads, 'bannerUpload' => $banner, 'removeIndexes' => $removeIndexes], [
            'removeIndexes' => ['array', 'max:10'], 'removeIndexes.*' => ['integer', 'min:0', 'max:9', 'distinct'],
            'attachmentUploads' => ['array', 'max:10'], 'attachmentUploads.*' => ['file', 'max:5120', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx'],
            'bannerUpload' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ])->validate();
        $current = $record->attachments ?? [];
        $attachments = collect($current)->except($removeIndexes)->values()->all();
        $references = app(PublishedContentDocuments::class)->references($documentIds, $record->school_id);
        foreach ($references as $reference) {
            if (! collect($attachments)->contains('document_id', $reference['document_id'])) {
                $attachments[] = $reference;
            }
        }
        validator(['attachmentUploads' => [...$attachments, ...$uploads]], ['attachmentUploads' => ['array', 'max:10']])->validate();
        foreach ($uploads as $file) {
            $path = $file->store('content-attachments', 'local');
            if (! $path) {
                throw ValidationException::withMessages(['attachmentUploads' => 'Lampiran gagal disimpan. Silakan coba kembali.']);
            }
            $attachments[] = ['path' => $path, 'name' => $file->getClientOriginalName(), 'mime' => $file->getMimeType(), 'size' => $file->getSize()];
        }
        $record->forceFill(['attachments' => $attachments]);
        if ($record instanceof Activity && ($banner || $removeBanner)) {
            $oldBanner = $record->banner_path;
            $path = $banner?->store('activity-banners', 'public');
            if ($banner && ! $path) {
                throw ValidationException::withMessages(['bannerUpload' => 'Banner gagal disimpan. Silakan coba kembali.']);
            }
            $record->forceFill(['banner_path' => $path]);
            if ($oldBanner) {
                DB::afterCommit(fn () => Storage::disk('public')->delete($oldBanner));
            }
        }
        $record->save();
        foreach ($removeIndexes as $index) {
            if (isset($current[$index]['path'])) {
                DB::afterCommit(fn () => Storage::disk('local')->delete($current[$index]['path']));
            }
        }
    }
}
