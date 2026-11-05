<?php

namespace App\Livewire\Administration;

use App\Models\AcademicYear;
use App\Models\GudepAccreditationEvidenceLink;
use App\Models\GudepAccreditationIndicator;
use App\Models\GudepAchievement;
use App\Models\GudepCashPeriod;
use App\Models\GudepFormation;
use App\Models\GudepWorkProgram;
use App\Models\ScoutGroup;
use App\Models\ScoutLevel;
use App\Models\ScoutUnit;
use App\Support\SchoolContext;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class GudepAccreditationPortfolio extends Component
{
    public ?int $scoutGroupId = null;

    public ?int $academicYearId = null;

    public string $indicatorCode = '';

    public string $indicatorTitle = '';

    public ?int $indicatorLevelId = null;

    public ?int $selectedIndicatorId = null;

    public string $evidenceKind = 'program';

    public ?int $evidenceSourceId = null;

    public function updatedScoutGroupId(): void
    {
        $this->reset(['selectedIndicatorId', 'evidenceSourceId']);
    }

    public function updatedAcademicYearId(): void
    {
        $this->reset(['selectedIndicatorId', 'evidenceSourceId']);
    }

    public function updatedEvidenceKind(): void
    {
        $this->evidenceSourceId = null;
    }

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('gudep.view'), 403);

        $schoolId = $this->schoolId();
        $this->scoutGroupId = ScoutGroup::query()->where('school_id', $schoolId)
            ->orderBy('id')->value('id');
        $this->academicYearId = AcademicYear::query()->where('school_id', $schoolId)
            ->where('is_active', true)->value('id');
    }

    private function selectedScope(): array
    {
        $schoolId = $this->schoolId();
        $gudep = ScoutGroup::query()->where('school_id', $schoolId)
            ->findOrFail($this->scoutGroupId);
        $year = AcademicYear::query()->where('school_id', $schoolId)
            ->findOrFail($this->academicYearId);

        return [
            'school_id' => $schoolId,
            'scout_group_id' => $gudep->id,
            'academic_year_id' => $year->id,
        ];
    }

    private function authorizeManage(): void
    {
        abort_unless(auth()->user()?->can('gudep.manage'), 403);
    }

    public function saveIndicator(): void
    {
        $this->authorizeManage();
        $scope = $this->selectedScope();
        $data = $this->validate([
            'indicatorCode' => ['required', 'string', 'max:80', 'regex:/^[a-zA-Z0-9._-]+$/'],
            'indicatorTitle' => ['required', 'string', 'max:255'],
            'indicatorLevelId' => ['nullable', 'integer', Rule::exists('scout_levels', 'id')],
        ]);

        $code = strtoupper(trim($data['indicatorCode']));
        $levelId = $data['indicatorLevelId'] ?: null;
        $alreadyExists = GudepAccreditationIndicator::query()->where($scope)
            ->where('scout_level_id', $levelId)->where('code', $code)->exists();
        abort_if($alreadyExists, 409, 'Kode indikator sudah dipakai dalam lingkup ini.');

        GudepAccreditationIndicator::query()->create($scope + [
            'scout_level_id' => $levelId,
            'code' => $code,
            'title' => trim($data['indicatorTitle']),
            'status' => 'draft',
        ]);
        $this->reset(['indicatorCode', 'indicatorTitle', 'indicatorLevelId']);
        session()->flash('status', 'Indikator internal berhasil ditambahkan.');
    }

    private function scopedIndicator(int $id): GudepAccreditationIndicator
    {
        return GudepAccreditationIndicator::query()->where($this->selectedScope())
            ->findOrFail($id);
    }

    public function selectIndicator(int $id): void
    {
        abort_unless(auth()->user()?->can('gudep.view'), 403);
        $this->selectedIndicatorId = $this->scopedIndicator($id)->id;
        $this->evidenceSourceId = null;
    }

    private function evidenceQuery(string $kind): \Illuminate\Database\Eloquent\Builder
    {
        $scope = $this->selectedScope();

        return match ($kind) {
            'program' => GudepWorkProgram::query()->where($scope),
            'achievement' => GudepAchievement::query()->where($scope),
            'cash_period' => GudepCashPeriod::query()->where($scope),
            default => abort(422, 'Jenis bukti tidak dikenal.'),
        };
    }

    public function linkEvidence(): void
    {
        $this->authorizeManage();
        $this->validate([
            'selectedIndicatorId' => ['required', 'integer'],
            'evidenceKind' => ['required', Rule::in(['program', 'achievement', 'cash_period'])],
            'evidenceSourceId' => ['required', 'integer'],
        ]);

        DB::transaction(function (): void {
            $indicator = $this->scopedIndicator((int) $this->selectedIndicatorId);
            abort_if($indicator->status === 'reviewed', 409, 'Indikator yang sudah ditinjau terkunci.');
            $source = $this->evidenceQuery($this->evidenceKind)
                ->findOrFail($this->evidenceSourceId);
            GudepAccreditationEvidenceLink::query()->firstOrCreate([
                'school_id' => $this->schoolId(),
                'indicator_id' => $indicator->id,
                'source_kind' => $this->evidenceKind,
                'source_id' => $source->id,
            ], [
                'linked_by' => auth()->id(),
            ]);
        });

        $this->evidenceSourceId = null;
        session()->flash('status', 'Bukti sumber berhasil ditautkan tanpa menyalin data.');
    }

    public function removeEvidence(int $id): void
    {
        $this->authorizeManage();
        $indicator = $this->scopedIndicator((int) $this->selectedIndicatorId);
        abort_if($indicator->status === 'reviewed', 409, 'Indikator yang sudah ditinjau terkunci.');
        GudepAccreditationEvidenceLink::query()->where('school_id', $this->schoolId())
            ->where('indicator_id', $indicator->id)->findOrFail($id)->delete();
    }

    public function markReviewed(int $id): void
    {
        $this->authorizeManage();
        DB::transaction(function () use ($id): void {
            $indicator = GudepAccreditationIndicator::query()
                ->where($this->selectedScope())->lockForUpdate()->findOrFail($id);
            abort_if($indicator->status === 'reviewed', 409);
            $links = $indicator->evidenceLinks()->get();
            abort_unless($links->isNotEmpty(), 409, 'Tautkan minimal satu bukti.');
            foreach ($links as $link) {
                abort_unless($this->evidenceQuery($link->source_kind)
                    ->whereKey($link->source_id)->exists(), 409,
                    'Ada bukti yang tidak tersedia lagi pada sumber aslinya.');
            }
            $indicator->update([
                'status' => 'reviewed',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);
        });
        session()->flash('status', 'Indikator ditandai sudah ditinjau secara internal.');
    }

    private function schoolId(): int
    {
        $id = app(SchoolContext::class)->id();
        abort_unless($id !== null, 403, 'Pilih sekolah aktif terlebih dahulu.');

        return $id;
    }

    public function render(): View
    {
        abort_unless(auth()->user()?->can('gudep.view'), 403);

        $schoolId = $this->schoolId();
        $gudepOptions = ScoutGroup::query()->where('school_id', $schoolId)
            ->orderBy('name')->get();
        $years = AcademicYear::query()->where('school_id', $schoolId)
            ->orderByDesc('start_date')->get();

        $gudep = $this->scoutGroupId === null ? null
            : $gudepOptions->firstWhere('id', $this->scoutGroupId);
        $year = $this->academicYearId === null ? null
            : $years->firstWhere('id', $this->academicYearId);

        $levels = [];
        $indicators = collect();
        $evidenceOptions = collect();
        $selectedIndicator = null;
        $shared = ['programs' => 0, 'achievements' => 0, 'cash_period' => null];

        if ($gudep !== null && $year !== null) {
            $scope = [
                'school_id' => $schoolId,
                'scout_group_id' => $gudep->id,
                'academic_year_id' => $year->id,
            ];

            $levelModels = ScoutLevel::query()->get();
            foreach (['Siaga', 'Penggalang', 'Penegak', 'Pandega'] as $name) {
                $level = $levelModels->firstWhere('name', $name)
                    ?? $levelModels->firstWhere('code', strtoupper($name));

                $formationIds = $level === null ? [] : GudepFormation::query()
                    ->where($scope)->where('scout_level_id', $level->id)->pluck('id')->all();

                $levels[] = [
                    'name' => $name,
                    'id' => $level?->id,
                    'formations' => count($formationIds),
                    'units' => $level === null ? 0 : ScoutUnit::query()
                        ->where($scope)->where('scout_level_id', $level->id)->count(),
                    'programs' => empty($formationIds) ? 0 : GudepWorkProgram::query()
                        ->where($scope)->whereIn('gudep_formation_id', $formationIds)->count(),
                ];
            }

            $indicators = GudepAccreditationIndicator::query()->where($scope)
                ->with('evidenceLinks')->orderBy('code')->get();

            $selectedIndicator = $indicators->firstWhere('id', $this->selectedIndicatorId);
            if ($selectedIndicator !== null && in_array($this->evidenceKind, ['program', 'achievement', 'cash_period'], true)) {
                $evidenceOptions = $this->evidenceQuery($this->evidenceKind)->orderBy('id')->get();
            }

            $shared = [
                'programs' => GudepWorkProgram::query()->where($scope)
                    ->whereNull('gudep_formation_id')->count(),
                'achievements' => GudepAchievement::query()->where($scope)->count(),
                'cash_period' => GudepCashPeriod::query()->where($scope)->value('status'),
            ];
        }

        return view('livewire.administration.gudep-accreditation-portfolio', compact(
            'gudepOptions', 'years', 'gudep', 'year', 'levels', 'shared',
            'indicators', 'selectedIndicator', 'evidenceOptions'
        ));
    }
}
