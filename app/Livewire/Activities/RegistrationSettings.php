<?php

namespace App\Livewire\Activities;

use App\Models\Activity;
use App\Services\ActivityEntryService;
use App\Services\GlobalActivityAccess;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

class RegistrationSettings extends Component
{
    #[Locked]
    public int $activityId;

    public bool $registrationOpen = false;

    public array $categories = ['individual'];

    public int $teamMin = 1;

    public int $teamMax = 20;

    public string $terms = ActivityEntryService::DEFAULT_TERMS;

    public array $fields = [];

    protected function activity(): Activity
    {
        $activity = Activity::withoutGlobalScope('school')->whereNull('school_id')->findOrFail($this->activityId);
        app(GlobalActivityAccess::class)->authorize($activity);

        return $activity;
    }

    public function mount(int $activityId): void
    {
        $this->activityId = $activityId;
        $activity = $this->activity();
        $this->registrationOpen = $activity->registration_open;
        $this->categories = $activity->registration_categories ?: ['individual'];
        $this->teamMin = $activity->team_min;
        $this->teamMax = $activity->team_max;
        $this->terms = $activity->registration_terms ?: ActivityEntryService::DEFAULT_TERMS;
        $this->fields = collect($activity->registration_fields ?? [])->map(fn (array $field): array => [...$field, 'options_text' => implode("\n", $field['options'] ?? [])])->all();
    }

    public function addField(): void
    {
        $this->activity();
        $this->fields[] = ['id' => Str::random(12), 'label' => '', 'description' => '', 'type' => 'short_text', 'required' => false, 'options_text' => ''];
    }

    public function removeField(int $index): void
    {
        $this->activity();
        unset($this->fields[$index]);
        $this->fields = array_values($this->fields);
    }

    public function moveField(int $index, int $direction): void
    {
        $this->activity();
        $other = $index + $direction;
        if (in_array($direction, [-1, 1], true) && isset($this->fields[$index], $this->fields[$other])) {
            [$this->fields[$index], $this->fields[$other]] = [$this->fields[$other], $this->fields[$index]];
        }
    }

    public function save(): void
    {
        $activity = $this->activity();
        $this->validate([
            'registrationOpen' => ['boolean'], 'categories' => ['required', 'array', 'min:1'],
            'categories.*' => ['string', 'distinct', Rule::in(array_keys(ActivityEntryService::CATEGORIES))],
            'teamMin' => ['required', 'integer', 'min:1', 'max:100'], 'teamMax' => ['required', 'integer', 'gte:teamMin', 'max:100'],
            'terms' => ['required', 'string', 'max:10000'], 'fields' => ['array', 'max:30'],
            'fields.*.id' => ['required', 'string', 'regex:/^[a-zA-Z0-9_-]{1,50}$/', 'distinct'],
            'fields.*.label' => ['required', 'string', 'max:150', 'distinct'], 'fields.*.description' => ['nullable', 'string', 'max:2000'],
            'fields.*.type' => ['required', 'in:short_text,paragraph,radio,checkbox,select,date,time,file'],
            'fields.*.required' => ['boolean'], 'fields.*.options_text' => ['nullable', 'string', 'max:5000'],
        ]);
        if (collect($this->fields)->where('type', 'file')->count() > 1) {
            throw ValidationException::withMessages(['fields' => 'Maksimal satu field lampiran per formulir (10 file, masing-masing 5 MB).']);
        }
        $fields = [];
        foreach ($this->fields as $index => $field) {
            $label = Str::slug($field['label']);
            if (preg_match('/^(nama|nta|nip|nis|nisn|sekolah|asal-sekolah|data-peserta|data-simpram|email|nomor-hp|no-hp|telepon|jenis-kelamin|tanggal-lahir|alamat|pembina|cadangan)(-|$)/', $label)) {
                throw ValidationException::withMessages(['fields.'.$index.'.label' => 'Data umum sudah tersedia pada bagian peserta / Data SIMPRAM dan tidak boleh ditambahkan lagi.']);
            }
            $options = in_array($field['type'], ['radio', 'checkbox', 'select'], true)
                ? array_values(array_filter(array_map('trim', preg_split('/\R/u', $field['options_text'] ?? '')))) : [];
            if (in_array($field['type'], ['radio', 'checkbox', 'select'], true)) {
                validator(['options' => $options], ['options' => ['required', 'array', 'min:1', 'max:50'], 'options.*' => ['string', 'max:150', 'distinct']])->validate();
            }
            $fields[] = ['id' => $field['id'], 'label' => trim($field['label']), 'description' => $field['description'] ?? '',
                'type' => $field['type'], 'required' => (bool) $field['required'], 'options' => $options];
        }
        $activity->forceFill(['registration_open' => $this->registrationOpen, 'registration_categories' => $this->categories,
            'team_min' => $this->teamMin, 'team_max' => $this->teamMax, 'registration_terms' => $this->terms, 'registration_fields' => $fields])->save();
        session()->flash('status', 'Form registrasi berhasil disimpan.');
    }

    public function render(): View
    {
        return view('livewire.activities.registration-settings', ['activity' => $this->activity()]);
    }
}
