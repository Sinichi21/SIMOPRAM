<?php

namespace App\Livewire\Letters;

use App\Enums\ScoutAdministrationType;
use App\Models\LetterField;
use App\Models\LetterNumberSequence;
use App\Models\LetterType;
use App\Models\ScoutAdministrationProfile;
use App\Services\DocumentSignatoryService;
use App\Services\LetterAdministrationBootstrapService;
use App\Services\LetterNumberService;
use App\Support\SchoolContext;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Settings extends Component
{
    public string $administrationType = 'male';

    public string $number_format = '';

    public string $agenda_format = '';

    public ?int $default_signatory_user_id = null;

    public string $letterhead_title = '';

    public string $letterhead_subtitle = '';

    public string $letterhead_address = '';

    public int $sequenceYear;

    public int $lastOutgoingNumber = 0;

    public int $lastIncomingNumber = 0;

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

    public function updatedAdministrationType(): void
    {
        $this->loadSettings();
        $this->resetValidation();
    }

    public function updatedSequenceYear(): void
    {
        $this->loadSequences();
    }

    public function loadSettings(): void
    {
        $profile = $this->profile();

        $this->number_format = (string) $profile->number_format;
        $this->agenda_format = (string) $profile->agenda_format;
        $this->default_signatory_user_id = $profile->default_signatory_user_id;
        $this->letterhead_title = (string) ($profile->letterhead_title ?? '');
        $this->letterhead_subtitle = (string) ($profile->letterhead_subtitle ?? '');
        $this->letterhead_address = (string) ($profile->letterhead_address ?? '');

        $this->loadSequences();
    }

    private function loadSequences(): void
    {
        $base = LetterNumberSequence::withoutGlobalScope('school')
            ->where('school_id', $this->schoolId())
            ->where('administration_type', $this->administrationType)
            ->where('year', $this->sequenceYear);

        $this->lastOutgoingNumber = (int) ((clone $base)->where('direction', 'outgoing')->value('last_number') ?? 0);
        $this->lastIncomingNumber = (int) ((clone $base)->where('direction', 'incoming')->value('last_number') ?? 0);
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('letters.settings'), 403);

        $data = $this->validate([
            'administrationType' => ['required', Rule::in(array_keys(ScoutAdministrationType::options()))],
            'number_format' => ['required', 'string', 'max:255'],
            'agenda_format' => ['required', 'string', 'max:120'],
            'default_signatory_user_id' => ['nullable', 'integer'],
            'letterhead_title' => ['nullable', 'string', 'max:255'],
            'letterhead_subtitle' => ['nullable', 'string', 'max:255'],
            'letterhead_address' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($this->default_signatory_user_id) {
            app(DocumentSignatoryService::class)->resolve((int) $this->default_signatory_user_id, $this->schoolId());
        }

        $this->profile()->update([
            'number_format' => $data['number_format'],
            'agenda_format' => $data['agenda_format'],
            'default_signatory_user_id' => $data['default_signatory_user_id'],
            'letterhead_title' => $this->nullable($data['letterhead_title']),
            'letterhead_subtitle' => $this->nullable($data['letterhead_subtitle']),
            'letterhead_address' => $this->nullable($data['letterhead_address']),
        ]);

        session()->flash('success', 'Pengaturan '.ScoutAdministrationType::from($this->administrationType)->label().' berhasil disimpan.');
    }

    public function saveSequence(LetterNumberService $service): void
    {
        abort_unless(auth()->user()->can('letters.settings'), 403);

        $this->validate([
            'administrationType' => ['required', Rule::in(array_keys(ScoutAdministrationType::options()))],
            'sequenceYear' => ['required', 'integer', 'min:2000', 'max:2100'],
            'lastOutgoingNumber' => ['required', 'integer', 'min:0'],
            'lastIncomingNumber' => ['required', 'integer', 'min:0'],
        ]);

        $service->setLastNumber('outgoing', $this->schoolId(), $this->sequenceYear, $this->lastOutgoingNumber, $this->administrationType);
        $service->setLastNumber('incoming', $this->schoolId(), $this->sequenceYear, $this->lastIncomingNumber, $this->administrationType);

        session()->flash('success', 'Nomor urut terakhir '.ScoutAdministrationType::from($this->administrationType)->label().' berhasil disetel.');
    }

    private function profile(): ScoutAdministrationProfile
    {
        return ScoutAdministrationProfile::query()
            ->where('type', $this->administrationType)
            ->firstOrFail();
    }

    private function nullable(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    public function render()
    {
        return view('livewire.letters.settings', [
            'types' => LetterType::query()->orderBy('sort_order')->get(),
            'fields' => LetterField::query()->orderBy('sort_order')->get(),
            'administrationTypes' => ScoutAdministrationType::options(),
            'signatoryUsers' => app(DocumentSignatoryService::class)->usersForSchool($this->schoolId()),
        ]);
    }
}
