<div class="my-5 space-y-4">
    <fieldset class="space-y-2">
        <legend class="text-sm font-semibold">Ambil dari dokumen terbit</legend>
        <flux:text>Dokumen dipautkan ke arsip asli tanpa unggahan ulang. Dokumen yang dipilih dapat dibuka publik setelah agenda atau pengumuman diterbitkan. Maksimal 10 lampiran termasuk unggahan.</flux:text>
        <div class="max-h-48 space-y-2 overflow-y-auto">
            @forelse($this->publishedDocumentOptions as $document)
                <flux:checkbox wire:model="publishedDocumentIds" :value="(string) $document->id" :label="($document->document_number ?: 'Tanpa nomor').' — '.$document->title.' ('.$document->school->name.')'" />
            @empty
                <flux:text>Belum ada dokumen terbit berklasifikasi Biasa yang telah disetujui dan dapat dipilih.</flux:text>
            @endforelse
        </div>
        <flux:error name="publishedDocumentIds" />
    </fieldset>
    <flux:input type="file" wire:model="attachmentUploads" label="Lampiran agenda / pengumuman" multiple accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx" />
    <flux:text>Maksimal 10 lampiran, masing-masing 5 MB. PDF, gambar, Word, atau Excel.</flux:text>
    @foreach($existingAttachments as $index => $file)<flux:checkbox wire:model="removeAttachmentIndexes" :value="(string) $index" :label="'Hapus: '.$file['name']" />@endforeach
    @if($withBanner ?? false)<flux:input type="file" wire:model="bannerUpload" label="Banner kegiatan (maksimal 5 MB)" accept=".jpg,.jpeg,.png,.webp" /><flux:checkbox wire:model="removeBanner" label="Hapus banner sebelumnya" />@endif
    <p wire:loading wire:target="attachmentUploads,bannerUpload" class="text-sm">Mengunggah berkas…</p>
</div>
