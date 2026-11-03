<?php

namespace App\Livewire\Administration;

use App\Models\AcademicYear;
use App\Models\Coach;
use App\Models\GudepOfficer;
use App\Models\ScoutGroup;
use App\Models\ScoutUnit;
use App\Models\ScoutUnitMember;
use App\Services\Documents\OfficialDocumentPublisher;
use App\Services\ReportVerificationService;
use App\Support\SchoolContext;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class GudepOrganization extends Component
{
    public ?int $scoutGroupId = null;

    public ?int $editingOfficerId = null;

    public ?int $coachId = null;

    public string $scope = 'gudep';

    public string $position = '';

    public string $personName = '';

    public string $startedAt = '';

    public string $endedAt = '';

    public string $decreeNumber = '';

    public string $decreeDate = '';

    public string $notes = '';

    public ?int $unitId = null;

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
            ->where('scout_group_id', $this->selectedGudep()->id)->findOrFail($id);

        $this->editingOfficerId = $officer->id;
        $this->coachId = $officer->coach_id;
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
            'editingOfficerId', 'coachId', 'scope', 'position', 'personName',
            'startedAt', 'endedAt', 'decreeNumber', 'decreeDate', 'notes',
        ]);
        $this->scope = 'gudep';
        $this->startedAt = now()->toDateString();
        $this->resetValidation();
    }

    public function assignUnit(): void
    {
        $this->authorizeManage();
        $gudep = $this->selectedGudep();
        $validated = $this->validate([
            'unitId' => ['required', 'integer', Rule::exists('scout_units', 'id')
                ->where('school_id', $this->schoolId())->whereNull('deleted_at')],
        ]);

        ScoutUnit::query()->where('school_id', $this->schoolId())
            ->findOrFail($validated['unitId'])
            ->update(['scout_group_id' => $gudep->id]);

        $this->unitId = null;
        session()->flash('status', 'Satuan berhasil ditautkan ke Gugusdepan.');
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
            ->with(['scoutLevel', 'academicYear', 'memberships.student'])
            ->orderBy('scout_level_id')->orderBy('name')->get();

        $officers = GudepOfficer::query()->where('school_id', $this->schoolId())
            ->where('scout_group_id', $gudep->id)
            ->orderBy('scope')->orderBy('position')->get();

        $memberRows = [];
        foreach ($units as $unit) {
            foreach ($unit->memberships->whereNull('left_at') as $membership) {
                if ($membership->student && (int) $membership->student->school_id === $this->schoolId()) {
                    $memberRows[] = [
                        'unit' => $unit->name,
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
            'units' => $units->map(fn ($unit) => [
                'id' => $unit->id, 'name' => $unit->name,
                'level' => $unit->scoutLevel?->name,
                'academic_year' => $unit->academicYear?->name,
            ])->all(),
            'officers' => $officers->map(fn ($officer) => $officer->only([
                'id', 'scope', 'position', 'person_name', 'started_at',
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
            ->with(['scoutLevel', 'academicYear'])->orderBy('name')->get();

        return view('livewire.administration.gudep-organization', [
            'gudep' => $gudep,
            'gudepOptions' => ScoutGroup::query()->where('school_id', $schoolId)
                ->orderBy('name')->get(),
            'units' => $units,
            'unassignedUnits' => ScoutUnit::query()->where('school_id', $schoolId)
                ->whereNull('scout_group_id')->orderBy('name')->get(),
            'officers' => $gudep
                ? GudepOfficer::query()->where('school_id', $schoolId)
                    ->where('scout_group_id', $gudep->id)
                    ->orderByDesc('started_at')->get()
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
