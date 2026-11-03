<?php

namespace App\Livewire\Administration;

use App\Models\AcademicYear;
use App\Models\Coach;
use App\Models\GudepFormation;
use App\Models\GudepOfficer;
use App\Models\ScoutGroup;
use App\Models\ScoutLevel;
use App\Models\ScoutUnit;
use App\Models\ScoutUnitMember;
use App\Services\Documents\OfficialDocumentPublisher;
use App\Services\ReportVerificationService;
use App\Support\SchoolContext;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class GudepOrganization extends Component
{
    public ?int $scoutGroupId = null;

    public ?int $editingOfficerId = null;

    public ?int $coachId = null;

    public ?int $officerFormationId = null;

    public string $scope = 'gudep';

    public string $position = '';

    public string $personName = '';

    public string $startedAt = '';

    public string $endedAt = '';

    public string $decreeNumber = '';

    public string $decreeDate = '';

    public string $notes = '';

    public ?int $unitId = null;

    public ?int $unitFormationId = null;

    public ?int $formationScoutLevelId = null;

    public ?int $formationYearId = null;

    public string $formationName = '';

    public string $formationGender = 'campuran';

    public string $formationNotes = '';

    public ?int $academicYearId = null;

    public function mount(): void
    {
        $this->authorizeView();

        $this->scoutGroupId = ScoutGroup::query()
            ->where('school_id', $this->schoolId())->orderBy('id')->value('id');

        $this->academicYearId = AcademicYear::query()
            ->where('school_id', $this->schoolId())
            ->where('is_active', true)->value('id');

        $this->startedAt = now()->toDateString();
        $this->formationYearId = $this->academicYearId;
    }

    public function updatedScoutGroupId(): void
    {
        $this->editingOfficerId = null;
        $this->unitId = null;
        $this->resetOfficer();
    }

    public function saveOfficer(): void
    {
        $this->authorizeManage();
        $gudep = $this->selectedGudep();
        $validated = $this->validate([
            'scope' => ['required', Rule::in(['gudep', 'mabi', 'satuan'])],
            'position' => ['required', 'string', 'max:150'],
            'personName' => ['required', 'string', 'max:150'],
            'officerFormationId' => ['nullable', 'integer',
                Rule::exists('gudep_formations', 'id')
                    ->where('school_id', $this->schoolId())
                    ->where('scout_group_id', $gudep->id)],
            'coachId' => ['nullable', 'integer', Rule::exists('coaches', 'id')
                ->where('school_id', $this->schoolId())->whereNull('deleted_at')],
            'startedAt' => ['required', 'date'],
            'endedAt' => ['nullable', 'date', 'after_or_equal:startedAt'],
            'decreeNumber' => ['nullable', 'string', 'max:180'],
            'decreeDate' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $officer = $this->editingOfficerId
            ? GudepOfficer::query()->where('school_id', $this->schoolId())
                ->where('scout_group_id', $gudep->id)->findOrFail($this->editingOfficerId)
            : new GudepOfficer;

        $officer->fill([
            'school_id' => $this->schoolId(),
            'scout_group_id' => $gudep->id,
            'coach_id' => $validated['coachId'] ?: null,
            'gudep_formation_id' => $validated['scope'] === 'satuan'
                ? ($validated['officerFormationId'] ?: null)
                : null,
            'scope' => $validated['scope'],
            'position' => trim($validated['position']),
            'person_name' => trim($validated['personName']),
            'started_at' => $validated['startedAt'],
            'ended_at' => $validated['endedAt'] ?: null,
            'decree_number' => $validated['decreeNumber'] ?: null,
            'decree_date' => $validated['decreeDate'] ?: null,
            'notes' => $validated['notes'] ?: null,
        ])->save();

        $this->resetOfficer();
        session()->flash('status', 'Riwayat kepengurusan tersimpan.');
    }

    public function editOfficer(int $id): void
    {
        $this->authorizeManage();
        $officer = GudepOfficer::query()->where('school_id', $this->schoolId())
            ->where('scout_group_id', $this->selectedGudep()->id)->find($id);

        abort_unless($officer, 404);

        $this->editingOfficerId = $officer->id;
        $this->coachId = $officer->coach_id;
        $this->officerFormationId = $officer->gudep_formation_id;
        $this->scope = $officer->scope;
        $this->position = $officer->position;
        $this->personName = $officer->person_name;
        $this->startedAt = $officer->started_at->toDateString();
        $this->endedAt = $officer->ended_at?->toDateString() ?? '';
        $this->decreeNumber = $officer->decree_number ?? '';
        $this->decreeDate = $officer->decree_date?->toDateString() ?? '';
        $this->notes = $officer->notes ?? '';
    }

    public function resetOfficer(): void
    {
        $this->reset([
            'editingOfficerId', 'coachId', 'officerFormationId', 'scope', 'position', 'personName',
            'startedAt', 'endedAt', 'decreeNumber', 'decreeDate', 'notes',
        ]);
        $this->scope = 'gudep';
        $this->startedAt = now()->toDateString();
        $this->resetValidation();
    }

    public function saveFormation(): void
    {
        $this->authorizeManage();
        $gudep = $this->selectedGudep();

        $validated = $this->validate([
            'formationName' => ['required', 'string', 'max:150',
                Rule::unique('gudep_formations', 'name')
                    ->where('school_id', $this->schoolId())
                    ->where('scout_group_id', $gudep->id)
                    ->where('academic_year_id', $this->formationYearId)],
            'formationScoutLevelId' => ['required', 'integer', Rule::exists('scout_levels', 'id')],
            'formationYearId' => ['required', 'integer',
                Rule::exists('academic_years', 'id')->where('school_id', $this->schoolId())],
            'formationGender' => ['required', Rule::in(['putra', 'putri', 'campuran'])],
            'formationNotes' => ['nullable', 'string', 'max:2000'],
        ]);

        $level = ScoutLevel::query()->findOrFail($validated['formationScoutLevelId']);
        $type = match (strtoupper($level->code)) {
            'SIAGA' => 'perindukan',
            'PENGGALANG' => 'pasukan',
            'PENEGAK' => 'ambalan',
            'PANDEGA' => 'racana',
            default => null,
        };
        abort_unless($type, 422, 'Golongan satuan tidak didukung.');

        GudepFormation::query()->create([
            'school_id' => $this->schoolId(),
            'scout_group_id' => $gudep->id,
            'scout_level_id' => $level->id,
            'academic_year_id' => $validated['formationYearId'],
            'name' => trim($validated['formationName']),
            'formation_type' => $type,
            'gender' => $validated['formationGender'],
            'notes' => $validated['formationNotes'] ?: null,
        ]);

        $this->reset(['formationName', 'formationScoutLevelId', 'formationNotes']);
        session()->flash('status', 'Satuan golongan berhasil dibuat.');
    }

    public function assignUnit(): void
    {
        $this->authorizeManage();
        $gudep = $this->selectedGudep();
        $validated = $this->validate([
            'unitId' => ['required', 'integer', Rule::exists('scout_units', 'id')
                ->where('school_id', $this->schoolId())
                ->whereNull('scout_group_id')->whereNull('deleted_at')],
            'unitFormationId' => ['nullable', 'integer',
                Rule::exists('gudep_formations', 'id')
                    ->where('school_id', $this->schoolId())
                    ->where('scout_group_id', $gudep->id)],
        ]);

        $unit = ScoutUnit::query()->where('school_id', $this->schoolId())
            ->whereNull('scout_group_id')->findOrFail($validated['unitId']);
        $formation = $validated['unitFormationId']
            ? GudepFormation::query()->where('school_id', $this->schoolId())
                ->where('scout_group_id', $gudep->id)->findOrFail($validated['unitFormationId'])
            : null;

        if ($formation && ((int) $formation->scout_level_id !== (int) $unit->scout_level_id
            || (int) $formation->academic_year_id !== (int) $unit->academic_year_id)) {
            throw ValidationException::withMessages([
                'unitFormationId' => 'Golongan dan tahun ajaran satuan harus sesuai.',
            ]);
        }

        $unit->update([
            'scout_group_id' => $gudep->id,
            'gudep_formation_id' => $formation?->id,
        ]);

        $this->unitId = null;
        $this->unitFormationId = null;
        session()->flash('status', 'Satuan berhasil ditautkan ke Gugusdepan.');
    }

    public function detachUnit(int $id): void
    {
        $this->authorizeManage();
        ScoutUnit::query()->where('school_id', $this->schoolId())
            ->where('scout_group_id', $this->selectedGudep()->id)
            ->findOrFail($id)->update([
                'scout_group_id' => null,
                'gudep_formation_id' => null,
            ]);

        session()->flash('status', 'Tautan satuan ke Gudep dilepas; riwayat keanggotaan tidak dihapus.');
    }

    public function publishProfile(OfficialDocumentPublisher $publisher, ReportVerificationService $verifier): void
    {
        $this->publish('gudep_profile', $publisher, $verifier);
    }

    public function publishMembers(OfficialDocumentPublisher $publisher, ReportVerificationService $verifier): void
    {
        $this->publish('gudep_members', $publisher, $verifier);
    }

    private function publish(
        string $documentType,
        OfficialDocumentPublisher $publisher,
        ReportVerificationService $verifier,
    ): void {
        $this->authorizeView();
        abort_unless(auth()->user()?->can('reports.export'), 403);
        $gudep = $this->selectedGudep();
        $year = $this->academicYearId
            ? AcademicYear::query()->where('school_id', $this->schoolId())
                ->findOrFail($this->academicYearId)
            : null;

        $units = ScoutUnit::query()
            ->where('school_id', $this->schoolId())
            ->where('scout_group_id', $gudep->id)
            ->when($year, fn ($q) => $q->where('academic_year_id', $year->id))
            ->with(['scoutLevel', 'academicYear', 'gudepFormation', 'memberships.student'])
            ->orderBy('scout_level_id')->orderBy('name')->get();

        $formations = GudepFormation::query()->where('school_id', $this->schoolId())
            ->where('scout_group_id', $gudep->id)
            ->when($year, fn ($q) => $q->where('academic_year_id', $year->id))
            ->with(['scoutLevel', 'academicYear'])
            ->orderBy('scout_level_id')->orderBy('name')->get();

        $officers = GudepOfficer::query()->where('school_id', $this->schoolId())
            ->where('scout_group_id', $gudep->id)
            ->with('formation')->orderBy('scope')->orderBy('position')->get();

        $memberRows = [];
        foreach ($units as $unit) {
            foreach ($unit->memberships->whereNull('left_at') as $membership) {
                if ($membership->student && (int) $membership->student->school_id === $this->schoolId()) {
                    $memberRows[] = [
                        'unit' => $unit->name,
                        'formation' => $unit->gudepFormation?->name ?? '-',
                        'level' => $unit->scoutLevel?->name ?? '-',
                        'name' => $membership->student->name,
                        'member_number' => $membership->student->nis ?? '-',
                        'position' => $membership->position,
                        'joined_at' => $membership->joined_at?->toDateString(),
                    ];
                }
            }
        }

        $content = [
            'gudep' => $gudep,
            'year' => $year,
            'units' => $units,
            'formations' => $formations,
            'officers' => $officers,
            'memberRows' => $memberRows,
            'generatedAt' => now()->toDateTimeString(),
        ];

        $snapshot = [
            'gudep' => $gudep->only([
                'id', 'school_id', 'name', 'male_number', 'female_number',
                'kwarran', 'kwarcab', 'kwarda', 'kamabigus_name',
                'head_coach_name', 'secretariat_address', 'inauguration_date',
                'description',
            ]),
            'period' => $year?->name,
            'formations' => $formations->map(fn ($formation) => $formation->only([
                'id', 'name', 'formation_type', 'gender', 'scout_level_id',
                'academic_year_id', 'is_active', 'notes',
            ]))->all(),
            'units' => $units->map(fn ($unit) => [
                'id' => $unit->id, 'name' => $unit->name,
                'level' => $unit->scoutLevel?->name,
                'academic_year' => $unit->academicYear?->name,
                'formation_id' => $unit->gudep_formation_id,
            ])->all(),
            'officers' => $officers->map(fn ($officer) => $officer->only([
                'id', 'scope', 'gudep_formation_id', 'position', 'person_name', 'started_at',
                'ended_at', 'decree_number', 'decree_date', 'notes',
            ]))->all(),
            'members' => $documentType === 'gudep_members' ? $memberRows : [],
        ];

        $checksum = $verifier->contentChecksum($snapshot);
        $verification = $publisher->publishBlade(
            schoolId: $this->schoolId(),
            sourceType: ScoutGroup::class,
            sourceId: $gudep->id,
            documentType: $documentType,
            snapshotChecksum: $checksum,
            filename: $documentType.'-'.$gudep->id.'.pdf',
            contentView: $documentType === 'gudep_members'
                ? 'reports.pdf.administration.gudep-members'
                : 'reports.pdf.administration.gudep-profile',
            contentData: $content,
            seriesKey: $year ? 'year-'.$year->id : 'all-years',
            title: $documentType === 'gudep_members'
                ? 'Buku Induk Anggota Gudep '.$gudep->name
                : 'Profil dan Organisasi Gudep '.$gudep->name,
            metadata: [
                'period' => $year?->name,
                'security_classification' => $documentType === 'gudep_members' ? 'Terbatas' : 'Biasa',
                'school_id' => $this->schoolId(),
            ],
        );

        session()->flash('status', 'Dokumen resmi tersedia di Dokumen Terbit. Kode: '.$verification->code);
        $this->redirectRoute('reports.published-documents.show', [
            'code' => $verification->code,
        ], navigate: false);
    }

    private function schoolId(): int
    {
        $id = app(SchoolContext::class)->id();
        abort_unless($id, 409, 'Pilih sekolah aktif terlebih dahulu.');

        return (int) $id;
    }

    private function selectedGudep(): ScoutGroup
    {
        abort_unless($this->scoutGroupId, 422, 'Pilih Gudep terlebih dahulu.');

        return ScoutGroup::query()->where('school_id', $this->schoolId())
            ->findOrFail($this->scoutGroupId);
    }

    private function authorizeView(): void
    {
        abort_unless(auth()->user()?->can('gudep.view'), 403);
        $this->schoolId();
    }

    private function authorizeManage(): void
    {
        $this->authorizeView();
        abort_unless(auth()->user()?->can('gudep.manage'), 403);
    }

    public function render(): View
    {
        $this->authorizeView();

        $gudep = $this->scoutGroupId
            ? $this->selectedGudep()
            : null;
        $schoolId = $this->schoolId();
        $units = ScoutUnit::query()->where('school_id', $schoolId)
            ->when($gudep, fn ($q) => $q->where('scout_group_id', $gudep->id))
            ->with(['scoutLevel', 'academicYear', 'gudepFormation'])->orderBy('name')->get();

        return view('livewire.administration.gudep-organization', [
            'gudep' => $gudep,
            'gudepOptions' => ScoutGroup::query()->where('school_id', $schoolId)
                ->orderBy('name')->get(),
            'units' => $units,
            'formations' => $gudep
                ? GudepFormation::query()->where('school_id', $schoolId)
                    ->where('scout_group_id', $gudep->id)
                    ->with(['scoutLevel', 'academicYear'])->orderBy('name')->get()
                : collect(),
            'scoutLevels' => ScoutLevel::query()->orderBy('sort_order')->get(),
            'unassignedUnits' => ScoutUnit::query()->where('school_id', $schoolId)
                ->whereNull('scout_group_id')->orderBy('name')->get(),
            'officers' => $gudep
                ? GudepOfficer::query()->where('school_id', $schoolId)
                    ->where('scout_group_id', $gudep->id)
                    ->with('formation')->orderByDesc('started_at')->get()
                : collect(),
            'coaches' => Coach::query()->where('school_id', $schoolId)
                ->where('is_active', true)->orderBy('name')->get(),
            'years' => AcademicYear::query()->where('school_id', $schoolId)
                ->orderByDesc('start_date')->get(),
            'activeMembers' => $gudep
                ? ScoutUnitMember::query()->where('school_id', $schoolId)
                    ->whereNull('left_at')
                    ->whereHas('scoutUnit', fn ($q) => $q->where('scout_group_id', $gudep->id))
                    ->count()
                : 0,
        ]);
    }
}
