<?php

namespace App\Livewire\Assessments\Activities;

use App\Models\ActivityAssessment;
use App\Services\ActivityJudgeService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Judges extends Component
{
    #[Locked]
    public int $assessmentId;

    public string $judgeName = '';

    public string $invitationUrl = '';

    public function mount(int $assessmentId): void
    {
        $this->assessmentId = $assessmentId;
        $this->assessment();
    }

    protected function assessment(): ActivityAssessment
    {
        abort_unless(auth()->user()?->can('activity_assessments.view'), 403);

        return ActivityAssessment::query()->where('is_special', true)->findOrFail($this->assessmentId);
    }

    public function invite(ActivityJudgeService $service): void
    {
        $this->validate(['judgeName' => 'required|string|max:150']);
        $result = $service->invite($this->assessment(), $this->judgeName);
        $this->invitationUrl = route('activity-judges.show', ['token' => $result['token']]);
        $this->judgeName = '';
    }

    public function revoke(int $judgeId, ActivityJudgeService $service): void
    {
        $service->revoke($this->assessment(), $judgeId);
        $this->invitationUrl = '';
    }

    public function render(): View
    {
        $assessment = $this->assessment()->load('judges', 'activity');
        $rankings = app(ActivityJudgeService::class)->rankings($assessment);
        $activeJudges = $assessment->judges->whereNull('revoked_at');
        $isFinal = $activeJudges->isNotEmpty() && $activeJudges->every(fn ($judge) => $judge->finalized_at !== null);

        return view('livewire.assessments.activities.judges', compact('assessment', 'rankings', 'isFinal'));
    }
}
