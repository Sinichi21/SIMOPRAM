<?php

namespace App\Livewire\Letters;

use App\Models\Letter;
use App\Models\LetterAttachment;
use App\Models\LetterField;
use App\Models\LetterTemplate;
use App\Models\LetterType;
use App\Models\School;
use App\Models\SchoolDocumentSetting;
use App\Models\SchoolLetterSetting;
use App\Models\ScoutGroup;
use App\Services\DocumentSignatoryService;
use App\Services\LetterAdministrationBootstrapService;
use App\Services\LetterNumberService;
use App\Services\LetterPublicationService;
use App\Services\LetterTemplateRenderer;
use App\Support\SchoolContext;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Index extends Component
{
    use WithFileUploads, WithPagination;

    public string $direction = 'incoming';

    public ?int $editingId = null;

    public string $letter_number = '';

    public string $letter_date = '';

    public string $received_date = '';

    public string $sender = '';

    public string $recipient = '';

    public string $subject = '';

    public string $classification = '';

    public string $security_classification = 'Biasa';

    public string $archive_code = '';

    public string $archive_category = '';

    public ?int $retention_years = null;

    public string $body = '';

    public string $signatory_source = 'master';

    public ?int $signatory_user_id = null;

    public string $signatory_name = '';

    public string $signatory_position = '';

    public string $signatory_identity = '';

    public string $status = 'draft';

    public ?int $letter_type_id = null;

    public ?int $letter_field_id = null;

    public ?int $template_id = null;

    public array $templateData = [];

    public array $templatePlaceholders = [];

    public bool $showPreview = false;

    public string $search = '';

    public string $statusFilter = '';

    public array $attachments = [];

    public function mount(string $direction, LetterAdministrationBootstrapService $bootstrap): void
    {
        abort_unless(in_array($direction, ['incoming', 'outgoing'], true), 404);
        $this->direction = $direction;
        $schoolId = $this->schoolId();
        $bootstrap->ensureForSchool($schoolId);
        $this->letter_date = now()->toDateString();
        $this->received_date = $direction === 'incoming' ? now()->toDateString() : '';
        $this->status = $direction === 'incoming' ? 'recorded' : 'draft';
        $this->applyLetterSettingDefaults();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedTemplateId(LetterTemplateRenderer $renderer): void
    {
        if (! $this->template_id || $this->direction !== 'outgoing') {
            $this->templateData = [];
            $this->templatePlaceholders = [];
            $this->showPreview = false;

            return;
        }

        $template = LetterTemplate::query()->find($this->template_id);
        if (! $template) {
            return;
        }

        $this->subject = $this->subject ?: (string) ($template->title ?? $template->name);
        if ($template->letter_type_id) {
            $this->letter_type_id = $template->letter_type_id;
        }

        if ($template->default_field_code) {
            $field = LetterField::query()->where('code', $template->default_field_code)->first();
            if ($field) {
                $this->letter_field_id = $field->id;
            }
        }

        $allPlaceholders = $renderer->placeholders($template->body_template);
        $this->templatePlaceholders = array_values(array_diff($allPlaceholders, ['letter_number', 'letter_date', 'published_date', 'subject', 'recipient', 'attachment_label', 'attachment_count', 'city', 'gudep', 'signatory_name', 'signatory_position', 'signatory_identity']));
        $defaults = $this->templateContext();
        $current = $this->templateData;
        $this->templateData = [];

        foreach ($this->templatePlaceholders as $placeholder) {
            $this->templateData[$placeholder] = (string) ($current[$placeholder] ?? $defaults[$placeholder] ?? '');
        }

        $this->showPreview = true;
    }

    public function togglePreview(): void
    {
        $this->showPreview = ! $this->showPreview;
    }

    protected function schoolId(): int
    {
        $schoolId = app(SchoolContext::class)->id();
        abort_unless($schoolId, 409, 'Pilih sekolah aktif terlebih dahulu.');

        return $schoolId;
    }

    protected function rules(): array
    {
        $statuses = $this->direction === 'incoming'
            ? ['recorded', 'disposed', 'archived']
            : ['draft', 'published', 'cancelled'];

        return [
            'letter_number' => ['nullable', 'string', 'max:150'],
            'letter_date' => ['required', 'date'],
            'received_date' => [$this->direction === 'incoming' ? 'required' : 'nullable', 'date'],
            'sender' => [$this->direction === 'incoming' ? 'required' : 'nullable', 'string', 'max:200'],
            'recipient' => ['nullable', 'string', 'max:200'],
            'subject' => ['required', 'string', 'max:255'],
            'classification' => ['nullable', 'string', 'max:100'],
            'security_classification' => ['required', Rule::in(['Biasa', 'Terbatas', 'Rahasia'])],
            'archive_code' => ['nullable', 'string', 'max:40'],
            'archive_category' => ['nullable', 'string', 'max:120'],
            'retention_years' => ['nullable', 'integer', Rule::in([2, 5, 10, 20])],
            'body' => ['nullable', 'string'],
            'signatory_source' => ['nullable', 'in:master,manual'],
            'signatory_user_id' => ['nullable', 'integer'],
            'signatory_name' => ['nullable', 'string', 'max:150'],
            'signatory_position' => ['nullable', 'string', 'max:150'],
            'signatory_identity' => ['nullable', 'string', 'max:160'],
            'status' => ['required', Rule::in($statuses)],
            'letter_type_id' => [$this->direction === 'outgoing' && $this->status === 'published' ? 'required' : 'nullable', 'integer'],
            'letter_field_id' => [$this->direction === 'outgoing' && $this->status === 'published' ? 'required' : 'nullable', 'integer'],
            'template_id' => ['nullable', 'integer'],
            'templateData' => ['array'],
            'templateData.*' => ['nullable', 'string', 'max:10000'],
            'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:5120'],
        ];
    }

    public function save(
        LetterNumberService $numberService,
        LetterTemplateRenderer $renderer,
        LetterPublicationService $publicationService
    ): void {
        abort_unless(auth()->user()->can($this->editingId ? 'letters.update' : 'letters.create'), 403);
        if ($this->direction === 'outgoing' && $this->status === 'published') {
            abort_unless(auth()->user()->can('letters.publish'), 403);
        }

        $validated = $this->validate();
        unset($validated['attachments']);

        $signatorySource = $validated['signatory_source'] ?? 'master';
        unset($validated['signatory_source']);

        $signatoryUserId = $signatorySource === 'master'
            ? ($validated['signatory_user_id'] ?? null)
            : null;

        unset($validated['signatory_user_id']);

        if ($this->direction === 'outgoing' && $signatorySource === 'master' && ! $signatoryUserId) {
            throw ValidationException::withMessages([
                'signatory_user_id' => 'Pilih user penandatangan atau ubah sumber penandatangan menjadi Manual.',
            ]);
        }

        if ($this->direction === 'outgoing' && $signatorySource === 'manual') {
            $manualName = trim((string) ($validated['signatory_name'] ?? ''));
            $manualPosition = trim((string) ($validated['signatory_position'] ?? ''));

            if ($manualName === '') {
                throw ValidationException::withMessages([
                    'signatory_name' => 'Nama penandatangan manual wajib diisi.',
                ]);
            }

            if ($manualPosition === '') {
                throw ValidationException::withMessages([
                    'signatory_position' => 'Jabatan penandatangan manual wajib diisi.',
                ]);
            }
        }
        $templateData = $validated['templateData'] ?? [];
        unset($validated['templateData']);

        $schoolId = $this->schoolId();
        $userId = auth()->id();

        $template = null;
        if ($this->direction === 'outgoing' && $this->template_id) {
            $template = LetterTemplate::query()->findOrFail($this->template_id);

            if ($template->requires_recipient && blank($validated['recipient'] ?? null)) {
                throw ValidationException::withMessages([
                    'recipient' => 'Tujuan surat wajib diisi untuk template ini.',
                ]);
            }
        }

        // Validasi variabel template dilakukan sebelum nomor resmi diambil agar
        // kegagalan form tidak menghabiskan nomor urut surat.
        if ($template && $this->status === 'published') {
            $preflightContext = array_merge($this->templateContext(), $templateData);
            $missing = $renderer->missingPlaceholders($template, $preflightContext);
            if ($missing !== []) {
                $messages = [];
                foreach ($missing as $placeholder) {
                    $messages['templateData.'.$placeholder] = 'Kolom '.$renderer->label($placeholder).' wajib diisi sebelum surat diterbitkan.';
                }
                throw ValidationException::withMessages($messages);
            }
        }

        /*
         * Nomor dialokasikan sesudah preflight valid, tetapi sebelum template
         * dirender karena {{ letter_number }} boleh ditempatkan di mana pun.
         */
        if ($this->direction === 'outgoing' && $this->status === 'published' && blank($validated['letter_number'] ?? null)) {
            $date = Carbon::parse($this->letter_date);
            $type = LetterType::query()->findOrFail($this->letter_type_id);
            $field = LetterField::query()->findOrFail($this->letter_field_id);
            $validated['letter_number'] = $numberService->nextOutgoing($schoolId, $date, $type, $field);
            $this->letter_number = $validated['letter_number'];
        }

        if ($template) {
            $context = array_merge($this->templateContext(), $templateData, [
                'letter_number' => (string) ($validated['letter_number'] ?? $this->letter_number),
            ]);

            $validated['body'] = $renderer->render($template, $context, false);
            $validated['metadata'] = [
                'template_data' => $templateData,
                'template_slug' => $template->slug,
                'template_name' => $template->name,
                'requires_recipient' => (bool) $template->requires_recipient,
                'signatory_source' => $signatorySource,
                'signatory_user_id' => $signatoryUserId,
            ];
        }

        if ($this->direction === 'outgoing' && ! $template) {
            $validated['metadata'] = array_merge(
                (array) ($validated['metadata'] ?? []),
                [
                    'signatory_source' => $signatorySource,
                    'signatory_user_id' => $signatoryUserId,
                ]
            );
        }

        $payload = array_merge($validated, ['direction' => $this->direction, 'school_id' => $schoolId, 'updated_by' => $userId]);

        if ($this->direction === 'outgoing') {
            $payload['received_date'] = null;
            $payload['sender'] = null;
            if ($this->status === 'published') {
                $payload['published_at'] ??= now();
                $payload['published_by'] ??= $userId;
            } else {
                $payload['published_at'] = null;
                $payload['published_by'] = null;
            }
        } else {
            $payload['recipient'] = null;
            $payload['signatory_name'] = null;
            $payload['signatory_position'] = null;
            $payload['signatory_identity'] = null;
            $payload['letter_type_id'] = null;
            $payload['letter_field_id'] = null;
            $payload['template_id'] = null;
            $payload['metadata'] = null;
            $payload['published_at'] = null;
            $payload['published_by'] = null;
        }

        if ($this->editingId) {
            $letter = Letter::query()->where('direction', $this->direction)->findOrFail($this->editingId);
            abort_if($letter->status === 'published' && $letter->publication()->exists(), 409, 'Surat yang sudah diterbitkan tidak dapat diubah. Cabut dokumen terbit dan buat surat pengganti bila diperlukan.');
            if ($letter->status === 'published' && blank($letter->letter_number) === false) {
                $payload['letter_number'] = $letter->letter_number;
                $payload['published_at'] = $letter->published_at;
                $payload['published_by'] = $letter->published_by;
            }
            $letter->update($payload);
            $message = 'Surat berhasil diperbarui.';
        } else {
            if ($this->direction === 'incoming') {
                $date = Carbon::parse($this->received_date ?: $this->letter_date);
                $payload['agenda_number'] = $numberService->nextAgenda($schoolId, $date);
            }
            $payload['created_by'] = $userId;
            $letter = Letter::query()->create($payload);
            $message = 'Surat berhasil ditambahkan.';
        }

        $this->storeAttachments($letter);

        if ($this->direction === 'outgoing' && $letter->status === 'published') {
            $publicationService->publish($letter->fresh());
        }

        $this->resetForm();
        session()->flash('success', $message);
    }

    public function edit(int $id, LetterTemplateRenderer $renderer): void
    {
        abort_unless(auth()->user()->can('letters.update'), 403);
        $letter = Letter::query()->where('direction', $this->direction)->findOrFail($id);
        abort_if($letter->status === 'published' && $letter->publication()->exists(), 409, 'Surat yang sudah diterbitkan bersifat final dan tidak dapat diedit.');
        $this->editingId = $letter->id;

        foreach ([
            'letter_number', 'sender', 'recipient', 'subject', 'classification', 'security_classification', 'archive_code', 'archive_category',
            'body', 'signatory_name', 'signatory_position', 'signatory_identity', 'status',
        ] as $field) {
            $this->{$field} = (string) ($letter->{$field} ?? '');
        }

        $this->letter_date = $letter->letter_date->toDateString();
        $this->received_date = $letter->received_date?->toDateString() ?? '';
        $this->retention_years = $letter->retention_years;
        $this->letter_type_id = $letter->letter_type_id;
        $this->letter_field_id = $letter->letter_field_id;
        $this->template_id = $letter->template_id;
        $this->attachments = [];
        $this->signatory_user_id = data_get($letter->metadata, 'signatory_user_id')
            ? (int) data_get($letter->metadata, 'signatory_user_id')
            : null;

        $this->signatory_source = (string) (
            data_get($letter->metadata, 'signatory_source')
            ?: ($this->signatory_user_id ? 'master' : 'manual')
        );

        $this->templateData = (array) data_get($letter->metadata, 'template_data', []);
        $this->templatePlaceholders = [];
        $this->showPreview = false;

        if ($letter->template_id) {
            $template = LetterTemplate::query()->find($letter->template_id);
            if ($template) {
                $allPlaceholders = $renderer->placeholders($template->body_template);
                $this->templatePlaceholders = array_values(array_diff($allPlaceholders, ['letter_number', 'letter_date', 'published_date', 'subject', 'recipient', 'attachment_label', 'attachment_count', 'city', 'gudep', 'signatory_name', 'signatory_position', 'signatory_identity']));
                $defaults = $this->templateContext();
                foreach ($this->templatePlaceholders as $placeholder) {
                    $this->templateData[$placeholder] = (string) ($this->templateData[$placeholder] ?? $defaults[$placeholder] ?? '');
                }
                $this->showPreview = true;
            }
        }
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()->can('letters.delete'), 403);
        $letter = Letter::query()->where('direction', $this->direction)->with('attachments')->findOrFail($id);
        abort_if($letter->status === 'published' && $letter->publication()->exists(), 409, 'Surat yang sudah diterbitkan tidak dapat dihapus. Gunakan pencabutan Dokumen Terbit.');
        foreach ($letter->attachments as $attachment) {
            Storage::disk($attachment->disk)->delete($attachment->path);
        }
        $letter->delete();
        session()->flash('success', 'Surat berhasil dihapus.');
    }

    public function deleteAttachment(int $id): void
    {
        abort_unless(auth()->user()->can('letters.attachments'), 403);
        $attachment = LetterAttachment::query()->whereHas('letter', fn ($q) => $q->where('direction', $this->direction))->findOrFail($id);
        Storage::disk($attachment->disk)->delete($attachment->path);
        $attachment->delete();
        session()->flash('success', 'Lampiran berhasil dihapus.');
    }

    private function storeAttachments(Letter $letter): void
    {
        if (! auth()->user()->can('letters.attachments')) {
            return;
        }

        foreach ($this->attachments as $file) {
            $path = $file->store("letters/{$letter->school_id}/{$letter->id}", 'public');
            $letter->attachments()->create([
                'disk' => 'public',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'uploaded_by' => auth()->id(),
            ]);
        }
    }

    public function updatedSignatorySource(string $value): void
    {
        if ($value === 'manual') {
            $this->signatory_user_id = null;
            $this->signatory_name = '';
            $this->signatory_position = '';
            $this->signatory_identity = '';
        } else {
            $this->applyLetterSettingDefaults();
        }
    }

    public function updatedSignatoryUserId(DocumentSignatoryService $service): void
    {
        if (! $this->signatory_user_id || $this->direction !== 'outgoing') {
            return;
        }

        $this->signatory_source = 'master';

        $resolved = $service->resolve(
            $this->signatory_user_id,
            $this->schoolId()
        );

        $this->signatory_name = $resolved['name'];
        $this->signatory_position = $resolved['position'];
        $this->signatory_identity = $resolved['identity'];
    }

    private function applyLetterSettingDefaults(): void
    {
        if ($this->direction !== 'outgoing') {
            return;
        }

        $documentSetting = SchoolDocumentSetting::query()->first();

        $resolved = app(DocumentSignatoryService::class)->configured($documentSetting, 'default_letter', $this->schoolId());
        if ($resolved) {
            $this->signatory_source = $resolved['user_id'] ? 'master' : 'manual';
            $this->signatory_user_id = $resolved['user_id'];
            $this->signatory_name = $resolved['name'];
            $this->signatory_position = $resolved['position'];
            $this->signatory_identity = $resolved['identity'];

            return;
        }

        // Backward compatibility dengan pengaturan Persuratan lama.
        $legacy = SchoolLetterSetting::query()->first();

        $this->signatory_name = (string) ($legacy?->default_signatory_name ?? '');
        $this->signatory_position = (string) ($legacy?->default_signatory_position ?? '');
        $this->signatory_identity = (string) ($legacy?->default_signatory_identity ?? '');

        if ($this->signatory_name !== '') {
            $this->signatory_source = 'manual';
        }
    }

    /** @return array<string, string> */
    private function templateContext(): array
    {
        $documentSetting = SchoolDocumentSetting::query()->first();
        $legacy = SchoolLetterSetting::query()->first();

        $attachmentCount = count($this->attachments);
        if ($this->editingId) {
            $attachmentCount += LetterAttachment::query()
                ->where('letter_id', $this->editingId)
                ->count();
        }

        $recipientLocation = trim((string) ($this->templateData['recipient_location'] ?? ''));

        $maleGudep = trim((string) $documentSetting?->gudep_male_number);
        $femaleGudep = trim((string) $documentSetting?->gudep_female_number);

        if ($maleGudep !== '' && $femaleGudep !== '') {
            $femaleSuffix = preg_replace('/^.*\./', '', $femaleGudep);
            $gudep = $femaleSuffix !== ''
                ? $maleGudep.'-'.$femaleSuffix
                : $maleGudep.'-'.$femaleGudep;
        } else {
            $gudep = $maleGudep !== '' ? $maleGudep : $femaleGudep;
        }

        return [
            'letter_number' => $this->letter_number ?: ($this->status === 'published' ? '' : '(nomor otomatis saat diterbitkan)'),
            'recipient' => $this->recipient,
            'recipient_location' => $recipientLocation !== '' ? $recipientLocation : 'Tempat',
            'subject' => $this->subject,
            'letter_date' => $this->formatDate($this->letter_date),
            'published_date' => $this->formatDate($this->letter_date),
            'attachment_count' => (string) $attachmentCount,
            'attachment_label' => $attachmentCount > 0 ? $attachmentCount.' berkas' : '-',
            'city' => (string) ($documentSetting?->signing_city ?: ($legacy?->city ?? '')),
            'gudep' => $gudep !== '' ? $gudep : (string) ($legacy?->gudep_code ?? ''),
            'signatory_name' => $this->signatory_name,
            'signatory_position' => $this->signatory_position,
            'signatory_identity' => $this->signatory_identity,
        ];
    }

    private function formatDate(?string $date): string
    {
        if (blank($date)) {
            return '';
        }

        return Carbon::parse($date)->locale('id')->translatedFormat('d F Y');
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->letter_number = '';
        $this->letter_date = now()->toDateString();
        $this->received_date = $this->direction === 'incoming' ? now()->toDateString() : '';
        $this->sender = '';
        $this->recipient = '';
        $this->subject = '';
        $this->classification = '';
        $this->security_classification = 'Biasa';
        $this->archive_code = '';
        $this->archive_category = '';
        $this->retention_years = null;
        $this->body = '';
        $this->signatory_source = 'master';
        $this->signatory_user_id = null;
        $this->signatory_name = '';
        $this->signatory_position = '';
        $this->signatory_identity = '';
        $this->letter_type_id = null;
        $this->letter_field_id = null;
        $this->template_id = null;
        $this->templateData = [];
        $this->templatePlaceholders = [];
        $this->showPreview = false;
        $this->status = $this->direction === 'incoming' ? 'recorded' : 'draft';
        $this->attachments = [];
        $this->applyLetterSettingDefaults();
        $this->resetValidation();
    }

    public function render(LetterTemplateRenderer $renderer)
    {
        $letters = Letter::query()
            ->with(['attachments', 'creator', 'letterType', 'letterField', 'publication'])
            ->where('direction', $this->direction)
            ->when($this->search, function ($query): void {
                $search = '%'.trim($this->search).'%';
                $query->where(fn ($q) => $q->where('subject', 'like', $search)
                    ->orWhere('letter_number', 'like', $search)
                    ->orWhere('agenda_number', 'like', $search)
                    ->orWhere('sender', 'like', $search)
                    ->orWhere('recipient', 'like', $search));
            })
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->orderByDesc('letter_date')->orderByDesc('id')->paginate(10);

        $types = LetterType::query()->where('is_active', true)->orderBy('sort_order')->get();
        $fields = LetterField::query()->where('is_active', true)->orderBy('sort_order')->get();
        $templates = LetterTemplate::query()->where('is_active', true)->orderBy('sort_order')->get();
        $signatoryUsers = $this->direction === 'outgoing'
            ? app(DocumentSignatoryService::class)->usersForSchool($this->schoolId())
            : collect();

        $selectedTemplate = null;
        $previewBody = '';
        $previewFlexibleLayout = false;
        $previewSchool = null;
        $previewDocumentSetting = null;
        $previewScoutGroup = null;

        if ($this->direction === 'outgoing' && $this->template_id) {
            $selectedTemplate = $templates->firstWhere('id', $this->template_id)
                ?? LetterTemplate::query()->find($this->template_id);

            if ($selectedTemplate) {
                $previewBody = $renderer->render(
                    $selectedTemplate,
                    array_merge($this->templateContext(), $this->templateData),
                    true
                );

                $previewFlexibleLayout = $renderer->containsAny(
                    (string) $selectedTemplate->body_template,
                    [
                        'letter_number',
                        'attachment_label',
                        'attachment_count',
                        'subject',
                        'recipient',
                        'recipient_location',
                        'signatory_name',
                        'signatory_position',
                        'signatory_identity',
                    ]
                );
            }

            $schoolId = $this->schoolId();
            $previewSchool = School::query()->find($schoolId);
            $previewDocumentSetting = SchoolDocumentSetting::query()->first();
            $previewScoutGroup = ScoutGroup::query()
                ->where('is_active', true)
                ->first();
        }

        return view('livewire.letters.index', compact(
            'letters',
            'types',
            'fields',
            'templates',
            'selectedTemplate',
            'previewBody',
            'previewFlexibleLayout',
            'previewSchool',
            'previewDocumentSetting',
            'previewScoutGroup',
            'signatoryUsers',
        ) + ['placeholderCatalog' => $renderer->catalog()]);
    }
}
