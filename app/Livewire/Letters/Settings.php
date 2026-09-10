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
    public string $number_format = '';

    public string $agenda_format = '';

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
        $this->number_format = (string) $setting->number_format;
        $this->agenda_format = (string) $setting->agenda_format;

        $this->lastOutgoingNumber = (int) (
            LetterNumberSequence::withoutGlobalScope('school')
                ->where('school_id', $this->schoolId())
                ->where('direction', 'outgoing')
                ->where('year', $this->sequenceYear)
                ->value('last_number') ?? 0
        );
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('letters.settings'), 403);

        $data = $this->validate([
            'number_format' => ['required', 'string', 'max:255'],
            'agenda_format' => ['required', 'string', 'max:120'],
        ]);

        SchoolLetterSetting::query()->firstOrFail()->update($data);
        session()->flash('success', 'Pengaturan nomor persuratan berhasil disimpan.');
    }

    public function saveSequence(LetterNumberService $service): void
    {
        abort_unless(auth()->user()->can('letters.settings'), 403);

        $this->validate([
            'sequenceYear' => ['required', 'integer', 'min:2000', 'max:2100'],
            'lastOutgoingNumber' => ['required', 'integer', 'min:0'],
        ]);

        $service->setLastNumber(
            'outgoing',
            $this->schoolId(),
            $this->sequenceYear,
            $this->lastOutgoingNumber
        );

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
