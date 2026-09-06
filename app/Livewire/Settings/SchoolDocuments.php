<?php

namespace App\Livewire\Settings;

use App\Models\Coach;
use App\Models\SchoolDocumentSetting;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class SchoolDocuments extends Component
{
    public string $principalName = '';

    public string $principalNip = '';

    public string $coordinatorName = '';

    public string $coordinatorNip = '';

    public ?int $responsibleCoachId = null;

    public string $gudepMaleNumber = '';

    public string $gudepFemaleNumber = '';

    public string $signingCity = '';

    public string $parentAgency = '';

    public ?int $extracurricularWeekday = null;

    public string $extracurricularStartTime = '';

    public string $extracurricularEndTime = '';

    public string $extracurricularLocation = '';

    public string $documentNote = '';

    public function mount(): void
    {
        $setting = SchoolDocumentSetting::query()->first();

        if (! $setting) {
            return;
        }

        $this->principalName = $setting->principal_name ?? '';
        $this->principalNip = $setting->principal_nip ?? '';
        $this->coordinatorName = $setting->coordinator_name ?? '';
        $this->coordinatorNip = $setting->coordinator_nip ?? '';
        $this->responsibleCoachId = $setting->responsible_coach_id;
        $this->gudepMaleNumber = $setting->gudep_male_number ?? '';
        $this->gudepFemaleNumber = $setting->gudep_female_number ?? '';
        $this->signingCity = $setting->signing_city ?? '';
        $this->parentAgency = $setting->parent_agency ?? '';
        $this->extracurricularWeekday = $setting->extracurricular_weekday;
        $this->extracurricularStartTime = $this->formatTime($setting->extracurricular_start_time);
        $this->extracurricularEndTime = $this->formatTime($setting->extracurricular_end_time);
        $this->extracurricularLocation = $setting->extracurricular_location ?? '';
        $this->documentNote = $setting->document_note ?? '';
    }

    public function save(): void
    {
        abort_unless(
            auth()->user()->can('school_documents.manage'),
            403
        );

        $this->validate([
            'principalName' => ['nullable', 'string', 'max:150'],
            'principalNip' => ['nullable', 'string', 'max:50'],
            'coordinatorName' => ['nullable', 'string', 'max:150'],
            'coordinatorNip' => ['nullable', 'string', 'max:50'],
            'responsibleCoachId' => ['nullable', 'integer'],
            'gudepMaleNumber' => ['nullable', 'string', 'max:50'],
            'gudepFemaleNumber' => ['nullable', 'string', 'max:50'],
            'signingCity' => ['nullable', 'string', 'max:100'],
            'parentAgency' => ['nullable', 'string', 'max:200'],
            'extracurricularWeekday' => [
                'nullable',
                'integer',
                Rule::in(range(1, 7)),
            ],
            'extracurricularStartTime' => ['nullable', 'date_format:H:i'],
            'extracurricularEndTime' => ['nullable', 'date_format:H:i'],
            'extracurricularLocation' => ['nullable', 'string', 'max:255'],
            'documentNote' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($this->responsibleCoachId) {
            $coachExists = Coach::query()
                ->whereKey($this->responsibleCoachId)
                ->exists();

            if (! $coachExists) {
                throw ValidationException::withMessages([
                    'responsibleCoachId' => 'Pembina yang dipilih tidak valid untuk sekolah aktif.',
                ]);
            }
        }

        if (
            $this->extracurricularStartTime !== ''
            && $this->extracurricularEndTime !== ''
            && $this->extracurricularEndTime <= $this->extracurricularStartTime
        ) {
            throw ValidationException::withMessages([
                'extracurricularEndTime' => 'Jam selesai harus lebih besar dari jam mulai.',
            ]);
        }

        $setting = SchoolDocumentSetting::query()->firstOrNew();

        $setting->fill([
            'principal_name' => $this->nullIfEmpty($this->principalName),
            'principal_nip' => $this->nullIfEmpty($this->principalNip),
            'coordinator_name' => $this->nullIfEmpty($this->coordinatorName),
            'coordinator_nip' => $this->nullIfEmpty($this->coordinatorNip),
            'responsible_coach_id' => $this->responsibleCoachId,
            'gudep_male_number' => $this->nullIfEmpty($this->gudepMaleNumber),
            'gudep_female_number' => $this->nullIfEmpty($this->gudepFemaleNumber),
            'signing_city' => $this->nullIfEmpty($this->signingCity),
            'parent_agency' => $this->nullIfEmpty($this->parentAgency),
            'extracurricular_weekday' => $this->extracurricularWeekday,
            'extracurricular_start_time' => $this->nullIfEmpty($this->extracurricularStartTime),
            'extracurricular_end_time' => $this->nullIfEmpty($this->extracurricularEndTime),
            'extracurricular_location' => $this->nullIfEmpty($this->extracurricularLocation),
            'document_note' => $this->nullIfEmpty($this->documentNote),
        ]);

        $setting->save();

        session()->flash(
            'status',
            'Pengaturan dokumen sekolah berhasil disimpan.'
        );
    }

    protected function nullIfEmpty(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    protected function formatTime(mixed $value): string
    {
        if (! $value) {
            return '';
        }

        return substr((string) $value, 0, 5);
    }

    public function render()
    {
        $coaches = Coach::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'nip',
                'position',
            ]);

        return view('livewire.settings.school-documents', [
            'coaches' => $coaches,
            'weekdays' => [
                1 => 'Senin',
                2 => 'Selasa',
                3 => 'Rabu',
                4 => 'Kamis',
                5 => 'Jumat',
                6 => 'Sabtu',
                7 => 'Minggu',
            ],
        ]);
    }
}
