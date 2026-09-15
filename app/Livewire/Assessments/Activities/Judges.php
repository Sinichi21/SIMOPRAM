<?php

namespace App\Livewire\Assessments\Activities;

use App\Models\ActivityAssessment;
use App\Models\ActivityAssessmentReport;
use App\Services\ActivityJudgeService;
use App\Services\GlobalActivityAccess;
use App\Services\MessagingService;
use App\Support\SchoolContext;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Judges extends Component
{
    #[Locked]
    public int $assessmentId;

    public string $judgeName = '';

    #[Locked]
    public string $invitationUrl = '';

    public string $messageChannel = 'whatsapp';

    public string $messageDestination = '';

    public function sendInvitation(MessagingService $messaging): void
    {
        $assessment = $this->assessment();
        app(ActivityJudgeService::class)->authorizeManagement($assessment);
        $this->validate(['messageChannel' => ['required', 'in:whatsapp,telegram,email'],
            'messageDestination' => ['required', 'string', 'max:255']]);
        $token = basename(parse_url($this->invitationUrl, PHP_URL_PATH) ?: '');
        $judge = $assessment->judges()->where('token_hash', hash('sha256', $token))->firstOrFail();
        abort_if($judge->revoked_at || $judge->finalized_at || now()->gte($judge->expires_at), 410);
        $key = 'judge-message:'.auth()->id().':'.$judge->id;
        if (RateLimiter::tooManyAttempts($key, 3)) {
            throw ValidationException::withMessages(['messageDestination' => 'Tunggu satu menit sebelum mengirim lagi.']);
        }
        RateLimiter::hit($key, 60);
        try {
            $messaging->send($this->messageChannel, $this->messageDestination,
                'Undangan penilaian untuk '.$judge->name.': '.$this->invitationUrl, 'Undangan Juri SIMPRAM');
        } catch (\Throwable) {
            throw ValidationException::withMessages(['messageDestination' => 'Undangan belum terkirim. Periksa tujuan dan konfigurasi pengiriman.']);
        }
        session()->flash('judge_message', 'Undangan diterima layanan pengiriman.');
    }

    public function mount(int $assessmentId): void
    {
        $this->assessmentId = $assessmentId;
        $this->assessment();
    }

    protected function assessment(): ActivityAssessment
    {
        $schoolId = app(SchoolContext::class)->id();
        $assessment = ActivityAssessment::query()->where('school_id', $schoolId)->where('is_special', true)->findOrFail($this->assessmentId);
        if ($schoolId === null) {
            app(GlobalActivityAccess::class)->authorize($assessment->activity()->withoutGlobalScope('school')->firstOrFail());
        } else {
            abort_unless(auth()->user()?->can('activity_assessments.view'), 403);
        }

        return $assessment;
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
        $canManageJudges = $assessment->school_id === null || auth()->user()?->can('activity_assessments.publish');
        $canExport = $canManageJudges && ($assessment->school_id === null || auth()->user()?->can('reports.export'));
        $resultJudges = app(ActivityJudgeService::class)->resultJudges($assessment);
        $reports = $canExport ? ActivityAssessmentReport::where('activity_assessment_id', $assessment->id)
            ->where('school_id', $assessment->school_id)->latest('id')->limit(5)->get() : collect();

        return view('livewire.assessments.activities.judges', compact('assessment', 'rankings', 'isFinal', 'canManageJudges', 'canExport', 'resultJudges', 'reports'));
    }
}
