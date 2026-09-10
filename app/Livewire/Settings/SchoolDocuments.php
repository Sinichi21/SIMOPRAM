<?php

namespace App\Livewire\Settings;

use App\Models\SchoolDocumentSetting;
use App\Models\SchoolLetterSetting;
use App\Services\DocumentSignatoryService;
use App\Support\SchoolContext;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class SchoolDocuments extends Component
{
    public ?int $principalSignatoryUserId = null;

    public ?int $coordinatorSignatoryUserId = null;

    public ?int $responsibleSignatoryUserId = null;

    public ?int $defaultLetterSignatoryUserId = null;

    public ?int $profileUserId = null;

    public string $profilePosition = '';

    public string $profileIdentifierType = 'NTA';

    public string $profileIdentifierNumber = '';

    // Legacy fallback values are retained but no longer edited in this UI.
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

        $this->principalSignatoryUserId = $setting->principal_signatory_user_id;
        $this->coordinatorSignatoryUserId = $setting->coordinator_signatory_user_id;
        $this->responsibleSignatoryUserId = $setting->responsible_signatory_user_id;
        $this->defaultLetterSignatoryUserId = $setting->default_letter_signatory_user_id;

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

    public function updatedProfileUserId(DocumentSignatoryService $service): void
    {
        if (! $this->profileUserId) {
            $this->profilePosition = '';
            $this->profileIdentifierType = 'NTA';
            $this->profileIdentifierNumber = '';

            return;
        }

        $resolved = $service->resolve($this->profileUserId, $this->schoolId());

        $this->profilePosition = $resolved['position'];
        $this->profileIdentifierType = $resolved['identifier_type'] ?: 'NTA';
        $this->profileIdentifierNumber = $resolved['identifier_number'];
    }

    public function saveProfile(DocumentSignatoryService $service): void
    {
        abort_unless(auth()->user()->can('school_documents.manage'), 403);

        $this->validate([
            'profileUserId' => ['required', 'integer'],
            'profilePosition' => ['nullable', 'string', 'max:150'],
            'profileIdentifierType' => ['required', Rule::in(['NIP', 'NTA'])],
            'profileIdentifierNumber' => ['nullable', 'string', 'max:100'],
        ]);

        $service->saveProfile(
            schoolId: $this->schoolId(),
            userId: (int) $this->profileUserId,
            position: $this->profilePosition,
            identifierType: $this->profileIdentifierType,
            identifierNumber: $this->profileIdentifierNumber
        );

        session()->flash('status', 'Profil jabatan/identitas penandatangan berhasil disimpan.');
    }

    public function save(DocumentSignatoryService $service): void
    {
        abort_unless(auth()->user()->can('school_documents.manage'), 403);

        $this->validate([
            'principalSignatoryUserId' => ['nullable', 'integer'],
            'coordinatorSignatoryUserId' => ['nullable', 'integer'],
            'responsibleSignatoryUserId' => ['nullable', 'integer'],
            'defaultLetterSignatoryUserId' => ['nullable', 'integer'],
            'gudepMaleNumber' => ['nullable', 'string', 'max:50'],
            'gudepFemaleNumber' => ['nullable', 'string', 'max:50'],
            'signingCity' => ['nullable', 'string', 'max:100'],
            'parentAgency' => ['nullable', 'string', 'max:200'],
            'extracurricularWeekday' => ['nullable', 'integer', Rule::in(range(1, 7))],
            'extracurricularStartTime' => ['nullable', 'date_format:H:i'],
            'extracurricularEndTime' => ['nullable', 'date_format:H:i'],
            'extracurricularLocation' => ['nullable', 'string', 'max:255'],
            'documentNote' => ['nullable', 'string', 'max:2000'],
        ]);

        foreach ([
            $this->principalSignatoryUserId,
            $this->coordinatorSignatoryUserId,
            $this->responsibleSignatoryUserId,
            $this->defaultLetterSignatoryUserId,
        ] as $userId) {
            if ($userId) {
                $service->resolve((int) $userId, $this->schoolId());
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
            'principal_signatory_user_id' => $this->principalSignatoryUserId,
            'coordinator_signatory_user_id' => $this->coordinatorSignatoryUserId,
            'responsible_signatory_user_id' => $this->responsibleSignatoryUserId,
            'default_letter_signatory_user_id' => $this->defaultLetterSignatoryUserId,

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

        // Backward-compatible cache for old {gudep} consumers.
        $letterSetting = SchoolLetterSetting::query()->first();
        if ($letterSetting) {
            $letterSetting->update(['gudep_code' => $this->combinedGudepCode()]);
        }

        session()->flash('status', 'Pengaturan dokumen sekolah berhasil disimpan.');
    }

    protected function schoolId(): int
    {
        $schoolId = app(SchoolContext::class)->id();
        abort_unless($schoolId, 409, 'Pilih sekolah aktif terlebih dahulu.');

        return $schoolId;
    }

    protected function combinedGudepCode(): string
    {
        $male = trim($this->gudepMaleNumber);
        $female = trim($this->gudepFemaleNumber);

        if ($male !== '' && $female !== '') {
            $femaleSuffix = preg_replace('/^.*\./', '', $female);

            return $femaleSuffix !== ''
                ? $male.'-'.$femaleSuffix
                : $male.'-'.$female;
        }

        return $male !== '' ? $male : $female;
    }

    protected function nullIfEmpty(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    protected function formatTime(mixed $value): string
    {
        return $value ? substr((string) $value, 0, 5) : '';
    }

    public function render(DocumentSignatoryService $service)
    {
        $users = $service->usersForSchool($this->schoolId());
        $resolved = [];

        foreach ($users as $user) {
            $resolved[$user->id] = $service->resolve((int) $user->id, $this->schoolId());
        }

        return view('livewire.settings.school-documents', [
            'signatoryUsers' => $users,
            'resolvedSignatories' => $resolved,
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
