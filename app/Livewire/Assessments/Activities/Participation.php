<?php

namespace App\Livewire\Assessments\Activities;

use App\Models\Activity;
use App\Models\ActivityParticipation;
use App\Models\AssessmentConfig;
use App\Models\Student;
use App\Services\ActivityParticipationService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Participation extends Component
{
    #[Locked]
    public int $activityId;

    public ?int $configId = null;

    public ?int $factorId = null;

    public float $targetPoints = 100;

    public array $entries = [];

    public string $search = '';

    public string $pointsFilter = '';

    public function resetFilters(): void
    {
        $this->reset('search', 'pointsFilter');
    }

    public function mount(int $activityId): void
    {
        abort_unless(auth()->user()?->can('activity_assessments.view'), 403);
        $this->activityId = $activityId;
        $activity = Activity::query()->findOrFail($activityId);
        $this->configId = AssessmentConfig::query()->where('academic_year_id', $activity->academic_year_id)
            ->where('semester_id', $activity->semester_id)->where('is_active', true)->value('id');
        $this->updatedConfigId();
    }

    public function updatedConfigId(): void
    {
        $this->entries = [];
        $this->factorId = null;
        if (! $this->configId) {
            return;
        }
        $config = AssessmentConfig::query()->findOrFail($this->configId);
        $this->factorId = $config->participation_factor_id;
        $this->targetPoints = $config->participation_target_points ?? 100;
        $saved = ActivityParticipation::query()->where('activity_id', $this->activityId)
            ->where('assessment_config_id', $config->id)->get()->keyBy('student_id');
        foreach (app(ActivityParticipationService::class)->students(Activity::query()->findOrFail($this->activityId))->get() as $student) {
            $this->entries[$student->id] = ['points' => $saved->get($student->id)?->points ?? 0, 'notes' => $saved->get($student->id)?->notes ?? ''];
        }
    }

    public function save(ActivityParticipationService $service): void
    {
        $this->validate(['configId' => 'required|integer', 'factorId' => 'required|integer', 'targetPoints' => 'required|numeric|min:0.01|max:1000000']);
        $service->save(Activity::query()->findOrFail($this->activityId), AssessmentConfig::query()->findOrFail($this->configId),
            $this->factorId, $this->targetPoints, $this->entries);
        session()->flash('status', 'Poin disimpan dan nilai keaktifan direkap. Nilai manual yang sudah diisi tetap dipertahankan.');
    }

    public function render(): View
    {
        $activity = Activity::query()->findOrFail($this->activityId);
        $configs = AssessmentConfig::query()->where('academic_year_id', $activity->academic_year_id)
            ->where('semester_id', $activity->semester_id)->where('is_active', true)->with('items.factor')->get();
        $config = $configs->firstWhere('id', $this->configId);
        $students = app(ActivityParticipationService::class)->students($activity)->orderBy('name')->get();
        $studentCount = $students->count();
        $search = mb_strtolower(trim($this->search));
        $students = $students->filter(function (Student $student) use ($search): bool {
            $matchesSearch = $search === '' || str_contains(mb_strtolower($student->name), $search)
                || str_contains(mb_strtolower((string) $student->nis), $search)
                || str_contains(mb_strtolower((string) $student->nisn), $search);
            $points = (float) ($this->entries[$student->id]['points'] ?? 0);
            $matchesPoints = match ($this->pointsFilter) {
                'zero' => $points === 0.0,
                'positive' => $points > 0,
                default => true,
            };

            return $matchesSearch && $matchesPoints;
        });
        $totals = $config ? ActivityParticipation::query()->where('assessment_config_id', $config->id)
            ->whereHas('activity')->selectRaw('student_id, SUM(points) AS total_points')->groupBy('student_id')->pluck('total_points', 'student_id') : collect();

        return view('livewire.assessments.activities.participation', compact('activity', 'configs', 'config', 'students', 'studentCount', 'totals'));
    }
}
