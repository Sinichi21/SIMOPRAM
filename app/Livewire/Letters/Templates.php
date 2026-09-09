<?php

namespace App\Livewire\Letters;

use App\Models\LetterTemplate;
use App\Models\LetterType;
use App\Services\LetterAdministrationBootstrapService;
use App\Services\LetterTemplateRenderer;
use App\Support\SchoolContext;
use Livewire\Component;

class Templates extends Component
{
    public ?int $editingId = null;

    public string $name = '';

    public string $title = '';

    public string $body_template = '';

    public ?int $letter_type_id = null;

    public string $default_field_code = '';

    public bool $is_active = true;

    public function mount(LetterAdministrationBootstrapService $bootstrap): void
    {
        abort_unless(auth()->user()->can('letters.templates'), 403);
        $id = app(SchoolContext::class)->id();
        abort_unless($id, 409, 'Pilih sekolah aktif terlebih dahulu.');
        $bootstrap->ensureForSchool($id);
    }

    public function edit(int $id): void
    {
        $template = LetterTemplate::query()->findOrFail($id);
        $this->editingId = $template->id;
        $this->name = $template->name;
        $this->title = (string) $template->title;
        $this->body_template = $template->body_template;
        $this->letter_type_id = $template->letter_type_id;
        $this->default_field_code = (string) $template->default_field_code;
        $this->is_active = $template->is_active;
    }

    public function insertPlaceholder(string $placeholder, LetterTemplateRenderer $renderer): void
    {
        abort_unless(array_key_exists($placeholder, $renderer->catalog()), 422);

        $token = '{{ '.$placeholder.' }}';
        $separator = trim($this->body_template) === '' ? '' : "\n";
        $this->body_template .= $separator.$token;
    }

    public function save(): void
    {
        abort_unless($this->editingId, 422);

        $data = $this->validate([
            'name' => ['required', 'string', 'max:160'],
            'title' => ['nullable', 'string', 'max:180'],
            'body_template' => ['required', 'string'],
            'letter_type_id' => ['nullable', 'integer'],
            'default_field_code' => ['nullable', 'string', 'max:10'],
            'is_active' => ['boolean'],
        ]);

        LetterTemplate::query()->findOrFail($this->editingId)->update($data);
        $this->editingId = null;
        session()->flash('success', 'Template surat berhasil diperbarui.');
    }

    public function cancel(): void
    {
        $this->editingId = null;
        $this->reset(['name', 'title', 'body_template', 'letter_type_id', 'default_field_code']);
    }

    public function render(LetterTemplateRenderer $renderer)
    {
        $templates = LetterTemplate::query()->with('letterType')->orderBy('sort_order')->get();
        $placeholders = $this->body_template ? $renderer->placeholders($this->body_template) : [];

        return view('livewire.letters.templates', [
            'templates' => $templates,
            'types' => LetterType::query()->orderBy('sort_order')->get(),
            'placeholders' => $placeholders,
            'placeholderCatalog' => $renderer->catalog(),
        ]);
    }
}
