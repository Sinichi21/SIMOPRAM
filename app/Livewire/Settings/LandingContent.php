<?php

namespace App\Livewire\Settings;

use App\Models\LandingPageSetting;
use App\Models\School;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class LandingContent extends Component
{
    use WithFileUploads;

    #[Locked]
    public ?int $schoolId = null;

    /** @var array<string, string|bool> */
    public array $content = [];

    public mixed $heroUpload = null;

    public mixed $logoUpload = null;

    public bool $removeHero = false;

    public bool $removeLogo = false;

    public function mount(?School $school = null): void
    {
        $this->schoolId = $school?->id;
        $school = $this->authorizeEditor();
        $this->content = $school
            ? collect($this->fields())->map(fn (array $field, string $key): mixed => $school->$key ?? ($field['default'] ?? ''))->all()
            : LandingPageSetting::contentForPage();
    }

    public function save(): void
    {
        $school = $this->authorizeEditor();
        $fields = $this->fields();
        $rules = ['content' => ['required', 'array:'.implode(',', array_keys($fields))],
            'heroUpload' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'logoUpload' => $school ? ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'] : ['prohibited'],
            'removeHero' => ['boolean'], 'removeLogo' => ['boolean']];
        foreach ($fields as $key => $field) {
            $rules['content.'.$key] = $field['rules'] ?? ['required', ($field['type'] ?? '') === 'email' ? 'email' : 'string', 'max:3000'];
        }
        $validated = $this->validate($rules, [], collect($fields)->mapWithKeys(fn (array $field, string $key): array => ['content.'.$key => $field['label']])->all());
        $record = $school ?? LandingPageSetting::firstOrNew(['key' => 'global']);
        if ($school) {
            $record->fill($validated['content']);
        } else {
            $record->content = $validated['content'];
        }
        $directory = $school ? 'landing/schools/'.$school->id : 'landing/global';
        if ($this->removeHero) {
            $record->hero_image = null;
        }
        if ($this->heroUpload) {
            $record->hero_image = $this->heroUpload->store($directory, 'public');
        }
        if ($school && $this->removeLogo) {
            $record->logo = null;
        }
        if ($school && $this->logoUpload) {
            $record->logo = $this->logoUpload->store($directory, 'public');
        }
        $record->save();
        $this->reset('heroUpload', 'logoUpload', 'removeHero', 'removeLogo');
        session()->flash('success', 'Konten berhasil disimpan dan ditampilkan pada halaman publik.');
    }

    private function authorizeEditor(): ?School
    {
        $school = $this->schoolId ? School::findOrFail($this->schoolId) : null;
        Gate::authorize($school ? 'school-landing.manage' : 'landing.manage', $school ? [$school] : []);

        return $school;
    }

    /** @return array<string, array{label: string, type?: string, default?: mixed, rules?: array<int, string>}> */
    private function fields(): array
    {
        if (! $this->schoolId) {
            return config('landing.fields');
        }

        return [
            'tagline' => ['label' => 'Judul utama / tagline', 'rules' => ['nullable', 'string', 'max:180']],
            'profile' => ['label' => 'Profil gugus depan', 'type' => 'textarea', 'rules' => ['nullable', 'string', 'max:3000']],
            'primary_color' => ['label' => 'Warna utama', 'default' => '#166534', 'rules' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/']],
            'email' => ['label' => 'Email sekolah', 'type' => 'email', 'rules' => ['nullable', 'email', 'max:150']],
            'phone' => ['label' => 'Telepon sekolah', 'rules' => ['nullable', 'string', 'max:30']],
            'address' => ['label' => 'Alamat sekolah', 'type' => 'textarea', 'rules' => ['nullable', 'string', 'max:2000']],
            'registration_open' => ['label' => 'Tampilkan pendaftaran anggota', 'type' => 'checkbox', 'default' => true, 'rules' => ['required', 'boolean']],
        ];
    }

    public function render(): View
    {
        $school = $this->authorizeEditor();
        $record = $school ?? LandingPageSetting::where('key', 'global')->first();

        return view('livewire.settings.landing-content', [
            'school' => $school, 'fields' => $this->fields(),
            'heroImage' => $record?->hero_image, 'logoImage' => $school?->logo,
        ]);
    }
}
