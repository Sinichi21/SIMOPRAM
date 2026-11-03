<?php

namespace App\Livewire\Administration;

use App\Models\AcademicYear;
use App\Models\Activity;
use App\Models\Coach;
use App\Models\GudepAchievement;
use App\Models\GudepAchievementEvidence;
use App\Models\GudepCompetencyRecord;
use App\Models\GudepFormation;
use App\Models\GudepWorkProgram;
use App\Models\Journal;
use App\Models\ScoutGroup;
use App\Models\ScoutUnit;
use App\Models\Student;
use App\Services\Documents\OfficialDocumentPublisher;
use App\Services\ReportVerificationService;
use App\Support\SchoolContext;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

class GudepDevelopment extends Component
{
    use WithFileUploads;

    public ?int $evidenceAchievementId = null;

    public $evidenceAttachment = null;

    public ?int $scoutGroupId = null;

    public ?int $academicYearId = null;

    public string $programTitle = '';

    public string $programObjective = '';

    public string $programDate = '';

    public ?int $programFormationId = null;

    public ?int $programActivityId = null;

    public string $programStatus = 'planned';

    public string $programNotes = '';

    public ?int $studentId = null;

    public ?int $competencyCoachId = null;

    public string $competencyCategory = 'SKU';

    public string $requirementCode = '';

    public string $requirementName = '';

    public string $competencyStatus = 'in_progress';

    public string $assessedAt = '';

    public string $competencyNotes = '';

    public ?int $achievementStudentId = null;

    public ?int $achievementCoachId = null;

    public ?int $achievementActivityId = null;

    public string $achievementKind = 'achievement';

    public string $achievementTitle = '';

    public string $awardedAt = '';

    public string $issuer = '';

    public string $referenceNumber = '';

    public string $achievementNotes = '';

    public function mount(): void
    {
        $this->authorizeView();
        $this->scoutGroupId = ScoutGroup::query()
            ->where('school_id', $this->schoolId())->orderBy('id')->value('id');
        $this->academicYearId = AcademicYear::query()
            ->where('school_id', $this->schoolId())
            ->where('is_active', true)->value('id');
        $this->awardedAt = now()->toDateString();
    }

    public function updatedScoutGroupId(): void
    {
        $this->reset(['programFormationId', 'programActivityId', 'studentId',
            'competencyCoachId', 'achievementStudentId', 'achievementActivityId',
            'evidenceAchievementId', 'evidenceAttachment']);
    }

    public function saveProgram(): void
    {
        $this->authorizeManage();
        $gudep = $this->selectedGudep();
        $year = $this->selectedYear();

        $data = $this->validate([
            'programTitle' => ['required', 'string', 'max:200'],
            'programObjective' => ['nullable', 'string', 'max:4000'],
            'programDate' => ['nullable', 'date'],
            'programFormationId' => ['nullable', 'integer', Rule::exists('gudep_formations', 'id')
                ->where('school_id', $this->schoolId())
                ->where('scout_group_id', $gudep->id)
                ->where('academic_year_id', $year->id)],
            'programActivityId' => ['nullable', 'integer', Rule::exists('activities', 'id')
                ->where('school_id', $this->schoolId())->whereNull('deleted_at')],
            'programStatus' => ['required', Rule::in(['planned', 'ongoing', 'completed', 'cancelled'])],
            'programNotes' => ['nullable', 'string', 'max:4000'],
        ]);

        if (! empty($data['programActivityId'])) {
            $activity = Activity::query()->where('school_id', $this->schoolId())
                ->findOrFail((int) $data['programActivityId']);
            if ((int) $activity->academic_year_id !== (int) $year->id) {
                throw ValidationException::withMessages([
                    'programActivityId' => 'Kegiatan harus berasal dari tahun ajaran program.',
                ]);
            }
        }

        GudepWorkProgram::query()->create([
            'school_id' => $this->schoolId(),
            'scout_group_id' => $gudep->id,
            'academic_year_id' => $year->id,
            'gudep_formation_id' => $data['programFormationId'] ?: null,
            'activity_id' => $data['programActivityId'] ?: null,
            'title' => trim($data['programTitle']),
            'objective' => $data['programObjective'] ?: null,
            'planned_for' => $data['programDate'] ?: null,
            'status' => $data['programStatus'],
            'notes' => $data['programNotes'] ?: null,
        ]);

        $this->reset(['programTitle', 'programObjective', 'programDate',
            'programFormationId', 'programActivityId', 'programNotes']);
        $this->programStatus = 'planned';
        session()->flash('status', 'Program kerja berhasil dicatat.');
    }

    public function setProgramStatus(int $id, string $status): void
    {
        $this->authorizeManage();
        abort_unless(in_array($status, ['planned', 'ongoing', 'completed', 'cancelled'], true), 422);

        $program = GudepWorkProgram::query()
            ->where('school_id', $this->schoolId())
            ->where('scout_group_id', $this->selectedGudep()->id)
            ->where('academic_year_id', $this->selectedYear()->id)
            ->findOrFail($id);

        $program->update(['status' => $status]);
        session()->flash('status', 'Status program kerja diperbarui.');
    }

    public function saveCompetency(): void
    {
        $this->authorizeManage();
        $gudep = $this->selectedGudep();
        $year = $this->selectedYear();
        $data = $this->validate([
            'studentId' => ['required', 'integer', Rule::exists('students', 'id')
                ->where('school_id', $this->schoolId())->whereNull('deleted_at')],
            'competencyCoachId' => ['nullable', 'integer', Rule::exists('coaches', 'id')
                ->where('school_id', $this->schoolId())->whereNull('deleted_at')],
            'competencyCategory' => ['required', Rule::in(['SKU', 'SKK'])],
            'requirementCode' => ['required', 'string', 'max:80'],
            'requirementName' => ['required', 'string', 'max:255'],
            'competencyStatus' => ['required', Rule::in(['in_progress', 'passed', 'not_passed'])],
            'assessedAt' => ['nullable', 'date'],
            'competencyNotes' => ['nullable', 'string', 'max:4000'],
        ]);

        $student = Student::query()->where('school_id', $this->schoolId())
            ->findOrFail((int) $data['studentId']);
        $this->assertMember($student->id, $gudep->id);

        $membership = $this->memberUnit($student->id, $gudep->id);
        abort_unless($membership !== null, 422);

        GudepCompetencyRecord::query()->updateOrCreate(
            [
                'school_id' => $this->schoolId(),
                'scout_group_id' => $gudep->id,
                'academic_year_id' => $year->id,
                'student_id' => $student->id,
                'category' => $data['competencyCategory'],
                'requirement_code' => trim($data['requirementCode']),
            ],
            [
                'scout_level_id' => $membership->scout_level_id,
                'coach_id' => $data['competencyCoachId'] ?: null,
                'requirement_name' => trim($data['requirementName']),
                'status' => $data['competencyStatus'],
                'assessed_at' => $data['competencyStatus'] === 'passed'
                    ? ($data['assessedAt'] ?: now()->toDateString())
                    : ($data['assessedAt'] ?: null),
                'notes' => $data['competencyNotes'] ?: null,
            ]
        );

        $this->reset(['studentId', 'competencyCoachId', 'requirementCode',
            'requirementName', 'assessedAt', 'competencyNotes']);
        $this->competencyCategory = 'SKU';
        $this->competencyStatus = 'in_progress';
        session()->flash('status', 'Progres kecakapan anggota tersimpan.');
    }

    public function saveAchievement(): void
    {
        $this->authorizeManage();
        $gudep = $this->selectedGudep();
        $year = $this->selectedYear();
        $data = $this->validate([
            'achievementStudentId' => ['nullable', 'integer', Rule::exists('students', 'id')
                ->where('school_id', $this->schoolId())->whereNull('deleted_at')],
            'achievementCoachId' => ['nullable', 'integer', Rule::exists('coaches', 'id')
                ->where('school_id', $this->schoolId())->whereNull('deleted_at')],
            'achievementActivityId' => ['nullable', 'integer', Rule::exists('activities', 'id')
                ->where('school_id', $this->schoolId())->whereNull('deleted_at')],
            'achievementKind' => ['required', Rule::in(['achievement', 'tkk_award', 'development'])],
            'achievementTitle' => ['required', 'string', 'max:200'],
            'awardedAt' => ['required', 'date'],
            'issuer' => ['nullable', 'string', 'max:180'],
            'referenceNumber' => ['nullable', 'string', 'max:100'],
            'achievementNotes' => ['nullable', 'string', 'max:4000'],
        ]);

        if (! empty($data['achievementStudentId'])) {
            $this->assertMember((int) $data['achievementStudentId'], $gudep->id);
        }

        if ($data['achievementKind'] === 'tkk_award' && empty($data['achievementStudentId'])) {
            throw ValidationException::withMessages([
                'achievementStudentId' => 'Pilih anggota penerima tanda kecakapan khusus.',
            ]);
        }

        if ($data['achievementKind'] === 'development' && empty($data['achievementCoachId'])) {
            throw ValidationException::withMessages([
                'achievementCoachId' => 'Pilih pembina untuk riwayat pengembangan pembina.',
            ]);
        }

        if (! empty($data['achievementActivityId'])) {
            $activity = Activity::query()->where('school_id', $this->schoolId())
                ->findOrFail((int) $data['achievementActivityId']);
            if ((int) $activity->academic_year_id !== (int) $year->id) {
                throw ValidationException::withMessages([
                    'achievementActivityId' => 'Agenda harus berasal dari tahun ajaran yang dipilih.',
                ]);
            }
        }

        GudepAchievement::query()->create([
            'school_id' => $this->schoolId(),
            'scout_group_id' => $gudep->id,
            'academic_year_id' => $year->id,
            'student_id' => $data['achievementKind'] === 'development' ? null : ($data['achievementStudentId'] ?: null),
            'coach_id' => $data['achievementKind'] === 'development' ? $data['achievementCoachId'] : null,
            'activity_id' => $data['achievementActivityId'] ?: null,
            'kind' => $data['achievementKind'],
            'title' => trim($data['achievementTitle']),
            'awarded_at' => $data['awardedAt'],
            'issuer' => $data['issuer'] ?: null,
            'reference_number' => $data['referenceNumber'] ?: null,
            'notes' => $data['achievementNotes'] ?: null,
        ]);

        $this->reset(['achievementStudentId', 'achievementCoachId', 'achievementActivityId', 'achievementTitle',
            'issuer', 'referenceNumber', 'achievementNotes']);
        $this->achievementKind = 'achievement';
        $this->awardedAt = now()->toDateString();
        session()->flash('status', 'Prestasi atau pengembangan pembina tersimpan.');
    }

    public function uploadEvidence(): void
    {
        $this->authorizeManage();
        $gudep = $this->selectedGudep();
        $year = $this->selectedYear();

        $data = $this->validate([
            'evidenceAchievementId' => ['required', 'integer',
                Rule::exists('gudep_achievements', 'id')
                    ->where('school_id', $this->schoolId())
                    ->where('scout_group_id', $gudep->id)
                    ->where('academic_year_id', $year->id)],
            'evidenceAttachment' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:8192'],
        ]);

        $achievement = GudepAchievement::query()
            ->where('school_id', $this->schoolId())
            ->where('scout_group_id', $gudep->id)
            ->where('academic_year_id', $year->id)
            ->findOrFail((int) $data['evidenceAchievementId']);

        $file = $this->evidenceAttachment;
        $temporaryPath = $file->getRealPath();
        if (! is_string($temporaryPath) || ! is_file($temporaryPath)) {
            throw ValidationException::withMessages([
                'evidenceAttachment' => 'File sementara tidak ditemukan.',
            ]);
        }

        $hash = hash_file('sha256', $temporaryPath);
        if (! is_string($hash)) {
            throw ValidationException::withMessages([
                'evidenceAttachment' => 'File bukti tidak dapat diverifikasi.',
            ]);
        }

        $dir = 'gudep-achievement-evidences/'.$this->schoolId().'/'.$gudep->id.'/'.$achievement->id;
        $filename = (string) Str::uuid().'.'.strtolower((string) $file->extension());
        $storedPath = $file->storeAs($dir, $filename, 'local');

        if (! is_string($storedPath)) {
            throw ValidationException::withMessages([
                'evidenceAttachment' => 'File bukti gagal disimpan.',
            ]);
        }

        try {
            GudepAchievementEvidence::query()->create([
                'school_id' => $this->schoolId(),
                'scout_group_id' => $gudep->id,
                'gudep_achievement_id' => $achievement->id,
                'uploaded_by' => auth()->id(),
                'original_filename' => $file->getClientOriginalName(),
                'disk' => 'local',
                'path' => $storedPath,
                'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                'size_bytes' => (int) $file->getSize(),
                'sha256' => $hash,
            ]);
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($storedPath);

            throw $exception;
        }

        $this->reset(['evidenceAchievementId', 'evidenceAttachment']);
        session()->flash('status', 'Lampiran bukti pembinaan tersimpan pada arsip privat.');
    }

    public function publishPortfolio(OfficialDocumentPublisher $publisher, ReportVerificationService $verifier): void
    {
        $this->authorizeView();
        abort_unless(auth()->user()?->can('reports.export'), 403);

        $gudep = $this->selectedGudep();
        $year = $this->selectedYear();
        $programs = $this->programQuery()->with(['activity', 'formation'])->orderBy('id')->get();
        $competencies = $this->competencyQuery()->with(['student', 'scoutLevel'])
            ->orderBy('id')->get();
        $achievements = $this->achievementQuery()->with(['student', 'activity', 'coach', 'evidences'])
            ->orderBy('id')->get();

        // The logbook remains a link to the existing journal source, not copied data.
        $activityIds = $programs->pluck('activity_id')->filter()->unique()->values()->all();
        $journals = Journal::query()->where('school_id', $this->schoolId())
            ->whereIn('activity_id', $activityIds)
            ->with('activity')->orderBy('id')->get();

        $snapshot = [
            'gudep_id' => $gudep->id,
            'gudep_name' => $gudep->name,
            'year_id' => $year->id,
            'year_name' => $year->name,
            'programs' => $programs->map(fn ($row) => $row->only([
                'id', 'title', 'objective', 'planned_for', 'status', 'notes', 'activity_id', 'gudep_formation_id',
            ]) + ['activity_title' => $row->activity?->title, 'formation_name' => $row->formation?->name])->all(),
            'competencies' => $competencies->map(fn ($row) => $row->only([
                'id', 'student_id', 'scout_level_id', 'category', 'requirement_code',
                'requirement_name', 'status', 'assessed_at', 'notes',
            ]) + ['student_name' => $row->student?->name, 'student_nta' => $row->student?->nta,
                'level_name' => $row->scoutLevel?->name])->all(),
            'achievements' => $achievements->map(fn ($row) => $row->only([
                'id', 'student_id', 'activity_id', 'coach_id', 'kind', 'title', 'awarded_at',
                'issuer', 'reference_number', 'notes',
            ]) + ['student_name' => $row->student?->name, 'coach_name' => $row->coach?->name,
                'evidences' => $row->evidences->map(fn ($evidence) => [
                    'id' => $evidence->id, 'name' => $evidence->original_filename,
                    'sha256' => $evidence->sha256,
                ])->all()])->all(),
            'journals' => $journals->map(fn ($row) => $row->only([
                'id', 'activity_id', 'status', 'objective', 'material',
                'activity_description', 'result', 'evaluation', 'follow_up', 'notes',
            ]) + ['activity_title' => $row->activity?->title])->all(),
        ];

        $document = $publisher->publishBlade(
            schoolId: $this->schoolId(),
            sourceType: ScoutGroup::class,
            sourceId: $gudep->id,
            documentType: 'gudep_development',
            seriesKey: 'year-'.$year->id,
            snapshotChecksum: $verifier->contentChecksum($snapshot),
            filename: 'pembinaan-gudep-'.$gudep->id.'-'.$year->id.'.pdf',
            contentView: 'reports.pdf.administration.gudep-development',
            contentData: compact('gudep', 'year', 'programs', 'competencies', 'achievements', 'journals'),
            title: 'Portofolio Pembinaan '.$gudep->name.' '.$year->name,
            metadata: ['academic_year' => $year->name, 'security_classification' => 'Terbatas'],
        );

        $this->redirectRoute('reports.published-documents.show', ['code' => $document->code], navigate: false);
    }

    private function assertMember(int $studentId, int $gudepId): void
    {
        if ($this->memberUnit($studentId, $gudepId) === null) {
            throw ValidationException::withMessages([
                'studentId' => 'Anggota belum terdaftar dalam satuan Gudep ini.',
            ]);
        }
    }

    private function memberUnit(int $studentId, int $gudepId): ?ScoutUnit
    {
        return ScoutUnit::query()->where('school_id', $this->schoolId())
            ->where('scout_group_id', $gudepId)
            ->where('academic_year_id', $this->selectedYear()->id)
            ->whereHas('memberships', fn ($q) => $q->where('student_id', $studentId)->whereNull('left_at'))
            ->orderByDesc('id')->first();
    }

    /** @return Builder<GudepWorkProgram> */
    private function programQuery(): Builder
    {
        return GudepWorkProgram::query()->where('school_id', $this->schoolId())
            ->where('scout_group_id', $this->selectedGudep()->id)
            ->where('academic_year_id', $this->selectedYear()->id);
    }

    /** @return Builder<GudepCompetencyRecord> */
    private function competencyQuery(): Builder
    {
        return GudepCompetencyRecord::query()->where('school_id', $this->schoolId())
            ->where('scout_group_id', $this->selectedGudep()->id)
            ->where('academic_year_id', $this->selectedYear()->id);
    }

    /** @return Builder<GudepAchievement> */
    private function achievementQuery(): Builder
    {
        return GudepAchievement::query()->where('school_id', $this->schoolId())
            ->where('scout_group_id', $this->selectedGudep()->id)
            ->where('academic_year_id', $this->selectedYear()->id);
    }

    private function schoolId(): int
    {
        $id = app(SchoolContext::class)->id();
        abort_unless($id !== null, 409, 'Pilih sekolah aktif terlebih dahulu.');

        return (int) $id;
    }

    private function selectedGudep(): ScoutGroup
    {
        abort_unless($this->scoutGroupId !== null, 422);

        return ScoutGroup::query()->where('school_id', $this->schoolId())
            ->findOrFail($this->scoutGroupId);
    }

    private function selectedYear(): AcademicYear
    {
        abort_unless($this->academicYearId !== null, 422);

        return AcademicYear::query()->where('school_id', $this->schoolId())
            ->findOrFail($this->academicYearId);
    }

    private function authorizeView(): void
    {
        abort_unless(auth()->user()?->can('gudep.view'), 403);
        $this->schoolId();
    }

    private function authorizeManage(): void
    {
        $this->authorizeView();
        abort_unless(auth()->user()?->can('gudep.manage') || auth()->user()?->can('gudep.development.manage'), 403);
    }

    public function render(): View
    {
        $this->authorizeView();
        $schoolId = $this->schoolId();
        $gudep = $this->scoutGroupId !== null ? $this->selectedGudep() : null;
        $year = $this->academicYearId !== null ? $this->selectedYear() : null;

        $members = $gudep && $year
            ? Student::query()->where('school_id', $schoolId)
                ->whereHas('scoutUnitMembers', function ($q) use ($gudep): void {
                    $q->whereNull('left_at')->whereHas('scoutUnit',
                        fn ($unit) => $unit->where('scout_group_id', $gudep->id)
                            ->where('academic_year_id', $this->selectedYear()->id));
                })->orderBy('name')->get()
            : collect();

        return view('livewire.administration.gudep-development', [
            'gudep' => $gudep,
            'year' => $year,
            'gudepOptions' => ScoutGroup::query()->where('school_id', $schoolId)->orderBy('name')->get(),
            'years' => AcademicYear::query()->where('school_id', $schoolId)->orderByDesc('start_date')->get(),
            'formations' => $gudep && $year ? GudepFormation::query()
                ->where('school_id', $schoolId)->where('scout_group_id', $gudep->id)
                ->where('academic_year_id', $year->id)->orderBy('name')->get() : collect(),
            'activities' => $year ? Activity::query()->where('school_id', $schoolId)
                ->where('academic_year_id', $year->id)->orderBy('title')->get() : collect(),
            'members' => $members,
            'coaches' => Coach::query()->where('school_id', $schoolId)->where('is_active', true)
                ->orderBy('name')->get(),
            'programs' => $gudep && $year ? $this->programQuery()->with('activity')
                ->orderBy('planned_for')->get() : collect(),
            'competencies' => $gudep && $year ? $this->competencyQuery()->with(['student', 'scoutLevel'])
                ->orderByDesc('id')->limit(100)->get() : collect(),
            'achievements' => $gudep && $year ? $this->achievementQuery()->with(['student', 'coach', 'evidences'])
                ->orderByDesc('awarded_at')->limit(100)->get() : collect(),
        ]);
    }
}
