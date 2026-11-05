<?php

namespace App\Livewire\Administration;

use App\Models\AcademicYear;
use App\Models\GudepAchievement;
use App\Models\GudepCashPeriod;
use App\Models\GudepFormation;
use App\Models\GudepWorkProgram;
use App\Models\ScoutGroup;
use App\Models\ScoutLevel;
use App\Models\ScoutUnit;
use App\Support\SchoolContext;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class GudepAccreditationPortfolio extends Component
{
    public ?int $scoutGroupId = null;

    public ?int $academicYearId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('gudep.view'), 403);

        $schoolId = $this->schoolId();
        $this->scoutGroupId = ScoutGroup::query()->where('school_id', $schoolId)
            ->orderBy('id')->value('id');
        $this->academicYearId = AcademicYear::query()->where('school_id', $schoolId)
            ->where('is_active', true)->value('id');
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
        $shared = ['programs' => 0, 'achievements' => 0, 'cash_period' => null];

        if ($gudep !== null && $year !== null) {
            $scope = [
                'school_id' => $schoolId,
                'scout_group_id' => $gudep->id,
                'academic_year_id' => $year->id,
            ];

            $levelModels = ScoutLevel::query()->get();
            foreach (['Siaga', 'Penggalang', 'Penegak', 'Pandega'] as $name) {
                $level = $levelModels->first(fn (ScoutLevel $item): bool =>
                    strcasecmp((string) $item->name, $name) === 0
                    || strcasecmp((string) $item->code, $name) === 0
                );

                $formationIds = $level === null ? [] : GudepFormation::query()
                    ->where($scope)->where('scout_level_id', $level->id)->pluck('id')->all();

                $levels[] = [
                    'name' => $name,
                    'formations' => count($formationIds),
                    'units' => $level === null ? 0 : ScoutUnit::query()
                        ->where($scope)->where('scout_level_id', $level->id)->count(),
                    'programs' => empty($formationIds) ? 0 : GudepWorkProgram::query()
                        ->where($scope)->whereIn('gudep_formation_id', $formationIds)->count(),
                ];
            }

            $shared = [
                'programs' => GudepWorkProgram::query()->where($scope)
                    ->whereNull('gudep_formation_id')->count(),
                'achievements' => GudepAchievement::query()->where($scope)->count(),
                'cash_period' => GudepCashPeriod::query()->where($scope)->value('status'),
            ];
        }

        return view('livewire.administration.gudep-accreditation-portfolio', compact(
            'gudepOptions', 'years', 'gudep', 'year', 'levels', 'shared'
        ));
    }
}
