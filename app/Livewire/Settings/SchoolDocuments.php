<?php

namespace App\Livewire\Settings;

use App\Models\Coach;
use App\Models\SchoolDocumentSetting;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class SchoolDocuments extends Component
{
    public string $principalName = '';

    public string $principalNip = '';

    public ?int $responsibleCoachId = null;

    public string $gudepMaleNumber = '';

    public string $gudepFemaleNumber = '';

    public string $signingCity = '';

    public string $documentNote = '';

    public function mount(): void
    {
        $setting =
            SchoolDocumentSetting::query()
                ->first();

        if (! $setting) {
            return;
        }

        $this->principalName =
            $setting->principal_name ?? '';

        $this->principalNip =
            $setting->principal_nip ?? '';

        $this->responsibleCoachId =
            $setting->responsible_coach_id;

        $this->gudepMaleNumber =
            $setting->gudep_male_number ?? '';

        $this->gudepFemaleNumber =
            $setting->gudep_female_number ?? '';

        $this->signingCity =
            $setting->signing_city ?? '';

        $this->documentNote =
            $setting->document_note ?? '';
    }

    public function save(): void
    {
        abort_unless(
            auth()->user()->can(
                'school_documents.manage'
            ),
            403
        );

        $this->validate([
            'principalName' => [
                'nullable',
                'string',
                'max:150',
            ],

            'principalNip' => [
                'nullable',
                'string',
                'max:50',
            ],

            'responsibleCoachId' => [
                'nullable',
                'integer',
            ],

            'gudepMaleNumber' => [
                'nullable',
                'string',
                'max:50',
            ],

            'gudepFemaleNumber' => [
                'nullable',
                'string',
                'max:50',
            ],

            'signingCity' => [
                'nullable',
                'string',
                'max:100',
            ],

            'documentNote' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Pastikan pembina berasal dari tenant aktif
        |--------------------------------------------------------------------------
        */

        if ($this->responsibleCoachId) {
            $coachExists =
                Coach::query()
                    ->whereKey(
                        $this->responsibleCoachId
                    )
                    ->exists();

            if (! $coachExists) {
                throw ValidationException::withMessages([
                    'responsibleCoachId' => 'Pembina yang dipilih tidak valid untuk sekolah aktif.',
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Satu setting untuk satu sekolah
        |--------------------------------------------------------------------------
        */

        $setting =
            SchoolDocumentSetting::query()
                ->firstOrNew();

        $setting->fill([
            'principal_name' => $this->nullIfEmpty(
                $this->principalName
            ),

            'principal_nip' => $this->nullIfEmpty(
                $this->principalNip
            ),

            'responsible_coach_id' => $this->responsibleCoachId,

            'gudep_male_number' => $this->nullIfEmpty(
                $this->gudepMaleNumber
            ),

            'gudep_female_number' => $this->nullIfEmpty(
                $this->gudepFemaleNumber
            ),

            'signing_city' => $this->nullIfEmpty(
                $this->signingCity
            ),

            'document_note' => $this->nullIfEmpty(
                $this->documentNote
            ),
        ]);

        $setting->save();

        session()->flash(
            'status',
            'Pengaturan dokumen sekolah berhasil disimpan.'
        );
    }

    protected function nullIfEmpty(
        ?string $value
    ): ?string {
        $value =
            trim(
                (string) $value
            );

        return $value === ''
            ? null
            : $value;
    }

    public function render()
    {
        $coaches =
            Coach::query()
                ->where(
                    'is_active',
                    true
                )
                ->orderBy(
                    'name'
                )
                ->get([
                    'id',
                    'name',
                    'nip',
                    'position',
                ]);

        return view(
            'livewire.settings.school-documents',
            [
                'coaches' => $coaches,
            ]
        );
    }
}
