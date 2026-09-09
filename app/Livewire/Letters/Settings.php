<?php

namespace App\Livewire\Letters;

use App\Models\LetterField;
use App\Models\LetterNumberSequence;
use App\Models\LetterType;
use App\Models\SchoolLetterSetting;
use App\Services\LetterAdministrationBootstrapService;
use App\Services\LetterNumberService;
use App\Support\SchoolContext;
use Livewire\Component;

class Settings extends Component
{
    public string $gudep_code = '';

    public string $number_format = '';

    public string $agenda_format = '';

    public string $letterhead_title = '';

    public string $letterhead_subtitle = '';

    public string $letterhead_address = '';

    public string $city = '';

    public string $default_signatory_name = '';

    public string $default_signatory_position = '';

    public string $default_signatory_identity = '';

    public int $sequenceYear;

    public int $lastOutgoingNumber = 0;

    public function mount(LetterAdministrationBootstrapService $bootstrap): void
    {
        abort_unless(auth()->user()->can('letters.settings'), 403);
        $schoolId = $this->schoolId();
        $bootstrap->ensureForSchool($schoolId);
        $this->sequenceYear = (int) now()->year;
        $this->loadSettings();
    }

    private function schoolId(): int
    {
        $id = app(SchoolContext::class)->id();
        abort_unless($id, 409, 'Pilih sekolah aktif terlebih dahulu.');

        return $id;
    }

    public function loadSettings(): void
    {
        $setting = SchoolLetterSetting::query()->firstOrFail();
        foreach (['gudep_code', 'number_format', 'agenda_format', 'letterhead_title', 'letterhead_subtitle', 'letterhead_address', 'city', 'default_signatory_name', 'default_signatory_position', 'default_signatory_identity'] as $field) {
            $this->{$field} = (string) ($setting->{$field} ?? '');
        }
        $this->lastOutgoingNumber = (int) (LetterNumberSequence::withoutGlobalScope('school')
            ->where('school_id', $this->schoolId())->where('direction', 'outgoing')->where('year', $this->sequenceYear)->value('last_number') ?? 0);
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('letters.settings'), 403);
        $data = $this->validate([
            'gudep_code' => ['required', 'string', 'max:80'], 'number_format' => ['required', 'string', 'max:255'],
            'agenda_format' => ['required', 'string', 'max:120'], 'letterhead_title' => ['nullable', 'string', 'max:255'],
            'letterhead_subtitle' => ['nullable', 'string', 'max:255'], 'letterhead_address' => ['nullable', 'string'], 'city' => ['nullable', 'string', 'max:120'],
            'default_signatory_name' => ['nullable', 'string', 'max:160'], 'default_signatory_position' => ['nullable', 'string', 'max:160'],
            'default_signatory_identity' => ['nullable', 'string', 'max:160'],
        ]);
        SchoolLetterSetting::query()->firstOrFail()->update($data);
        session()->flash('success', 'Pengaturan persuratan berhasil disimpan.');
    }

    public function saveSequence(LetterNumberService $service): void
    {
        abort_unless(auth()->user()->can('letters.settings'), 403);
        $this->validate(['sequenceYear' => ['required', 'integer', 'min:2000', 'max:2100'], 'lastOutgoingNumber' => ['required', 'integer', 'min:0']]);
        $service->setLastNumber('outgoing', $this->schoolId(), $this->sequenceYear, $this->lastOutgoingNumber);
        session()->flash('success', 'Nomor urut terakhir berhasil disetel.');
    }

    public function render()
    {
        return view('livewire.letters.settings', [
            'types' => LetterType::query()->orderBy('sort_order')->get(),
            'fields' => LetterField::query()->orderBy('sort_order')->get(),
        ]);
    }
}
