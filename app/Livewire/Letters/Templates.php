<?php

namespace App\Livewire\Letters;

use App\Models\Letter;
use App\Models\LetterTemplate;
use App\Models\LetterType;
use App\Services\LetterAdministrationBootstrapService;
use App\Services\LetterTemplateHtmlSanitizer;
use App\Services\LetterTemplateRenderer;
use App\Support\SchoolContext;
use Illuminate\Support\Str;
use Livewire\Component;

class Templates extends Component
{
    public ?int $editingId = null;

    public bool $creating = false;

    public string $name = '';

    public string $title = '';

    public string $body_template = '';

    public ?int $letter_type_id = null;

    public string $default_field_code = '';

    public bool $requires_recipient = false;

    public bool $is_active = true;

    public function mount(LetterAdministrationBootstrapService $bootstrap): void
    {
        abort_unless(auth()->user()->can('letters.templates'), 403);
        $id = app(SchoolContext::class)->id();
        abort_unless($id, 409, 'Pilih sekolah aktif terlebih dahulu.');
        $bootstrap->ensureForSchool($id);
    }

    public function createNew(): void
    {
        $this->resetEditor();
        $this->creating = true;
        $this->requires_recipient = false;
        $this->is_active = true;
        $this->body_template = $this->starterHtml('general');
    }

    public function applyStarter(string $starter): void
    {
        abort_unless(in_array($starter, ['general', 'decision', 'assignment', 'blank'], true), 422);
        $this->body_template = $this->starterHtml($starter);
        $this->requires_recipient = $starter === 'general';
    }

    public function edit(int $id, LetterTemplateHtmlSanitizer $sanitizer): void
    {
        $template = LetterTemplate::query()->findOrFail($id);
        $this->creating = false;
        $this->editingId = $template->id;
        $this->name = $template->name;
        $this->title = (string) $template->title;
        $this->body_template = $sanitizer->sanitize($template->body_template);
        $this->letter_type_id = $template->letter_type_id;
        $this->default_field_code = (string) $template->default_field_code;
        $this->requires_recipient = (bool) $template->requires_recipient;
        $this->is_active = (bool) $template->is_active;
    }

    public function duplicate(int $id, LetterTemplateHtmlSanitizer $sanitizer): void
    {
        $template = LetterTemplate::query()->findOrFail($id);
        $this->editingId = null;
        $this->creating = true;
        $this->name = $template->name.' - Salinan';
        $this->title = (string) $template->title;
        $this->body_template = $sanitizer->sanitize($template->body_template);
        $this->letter_type_id = $template->letter_type_id;
        $this->default_field_code = (string) $template->default_field_code;
        $this->requires_recipient = (bool) $template->requires_recipient;
        $this->is_active = true;
    }

    public function insertPlaceholder(string $placeholder, LetterTemplateRenderer $renderer): void
    {
        abort_unless(array_key_exists($placeholder, $renderer->catalog()), 422);

        $token = '{{ '.$placeholder.' }}';
        $separator = trim($this->body_template) === '' ? '' : '<p><br></p>';
        $this->body_template .= $separator.'<p>'.$token.'</p>';
    }

    public function save(LetterTemplateHtmlSanitizer $sanitizer): void
    {
        abort_unless($this->creating || $this->editingId, 422);

        $data = $this->validate([
            'name' => ['required', 'string', 'max:160'],
            'title' => ['nullable', 'string', 'max:180'],
            'body_template' => ['required', 'string'],
            'letter_type_id' => ['nullable', 'integer'],
            'default_field_code' => ['nullable', 'string', 'max:10'],
            'requires_recipient' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        $data['body_template'] = $sanitizer->sanitize($data['body_template']);

        if ($this->editingId) {
            LetterTemplate::query()->findOrFail($this->editingId)->update($data);
            $message = 'Template surat berhasil diperbarui.';
        } else {
            $schoolId = app(SchoolContext::class)->id();
            abort_unless($schoolId, 409, 'Pilih sekolah aktif terlebih dahulu.');

            $data['school_id'] = $schoolId;
            $data['slug'] = $this->uniqueSlug($schoolId, $this->name);
            $data['document_kind'] = 'letter';
            $data['sort_order'] = (int) LetterTemplate::query()->max('sort_order') + 10;

            LetterTemplate::query()->create($data);
            $message = 'Template surat baru berhasil ditambahkan.';
        }

        $this->resetEditor();
        session()->flash('success', $message);
    }

    public function delete(int $id): void
    {
        $template = LetterTemplate::query()->findOrFail($id);
        abort_if(
            Letter::query()->where('template_id', $template->id)->exists(),
            409,
            'Template sudah pernah digunakan pada surat dan tidak dapat dihapus. Nonaktifkan template bila tidak ingin dipakai lagi.'
        );

        $template->delete();
        if ($this->editingId === $id) {
            $this->resetEditor();
        }
        session()->flash('success', 'Template surat berhasil dihapus.');
    }

    public function cancel(): void
    {
        $this->resetEditor();
    }

    public function render(LetterTemplateRenderer $renderer)
    {
        $templates = LetterTemplate::query()->with('letterType')->orderBy('sort_order')->orderBy('name')->get();
        $placeholders = $this->body_template ? $renderer->placeholders($this->body_template) : [];

        return view('livewire.letters.templates', [
            'templates' => $templates,
            'types' => LetterType::query()->orderBy('sort_order')->get(),
            'placeholders' => $placeholders,
            'systemPlaceholderCatalog' => $renderer->systemCatalog(),
            'contentPlaceholderCatalog' => $renderer->contentCatalog(),
        ]);
    }

    private function resetEditor(): void
    {
        $this->editingId = null;
        $this->creating = false;
        $this->name = '';
        $this->title = '';
        $this->body_template = '';
        $this->letter_type_id = null;
        $this->default_field_code = '';
        $this->requires_recipient = false;
        $this->is_active = true;
        $this->resetValidation();
    }

    private function uniqueSlug(int $schoolId, string $name): string
    {
        $base = Str::slug($name) ?: 'template-surat';
        $slug = $base;
        $counter = 2;

        while (LetterTemplate::withoutGlobalScope('school')
            ->where('school_id', $schoolId)
            ->where('slug', $slug)
            ->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }

    private function starterHtml(string $starter): string
    {
        return match ($starter) {
            'decision' => '<p style="text-align: center"><strong>KEPUTUSAN</strong><br>Nomor : {{ letter_number }}<br>Tentang<br><strong>{{ subject }}</strong></p><p>Menimbang :</p><p>{{ considerations }}</p><p>Mengingat :</p><p>{{ legal_basis }}</p><p style="text-align: center"><strong>MEMUTUSKAN</strong></p><p>Menetapkan :</p><p>{{ statement }}</p><p style="text-align: right">Ditetapkan di : {{ city }}<br>Pada tanggal : {{ letter_date }}</p><p style="text-align: right">{{ signatory_position }}<br><br><br><strong><u>{{ signatory_name }}</u></strong><br>{{ signatory_identity }}</p>',
            'assignment' => '<p style="text-align: center"><strong>SURAT TUGAS</strong><br>Nomor : {{ letter_number }}</p><p>Dasar :</p><p>{{ legal_basis }}</p><p style="text-align: center"><strong>MENUGASKAN</strong></p><p>{{ assignees }}</p><p>Untuk :</p><p>{{ assignment_purpose }}</p><p style="text-align: right">{{ city }}, {{ letter_date }}<br>{{ signatory_position }}<br><br><br><strong><u>{{ signatory_name }}</u></strong><br>{{ signatory_identity }}</p>',
            'blank' => '<p><br></p>',
            default => '<table><tbody><tr><td>Nomor</td><td>:</td><td>{{ letter_number }}</td></tr><tr><td>Lampiran</td><td>:</td><td>{{ attachment_label }}</td></tr><tr><td>Perihal</td><td>:</td><td><strong>{{ subject }}</strong></td></tr></tbody></table><p>Yth. {{ recipient }}<br>di {{ recipient_location }}</p><p>Salam Pramuka,</p><p style="text-align: justify">Tuliskan isi surat di sini.</p><p style="text-align: right">{{ signatory_position }}<br><br><br><strong><u>{{ signatory_name }}</u></strong><br>{{ signatory_identity }}</p>',
        };
    }
}
