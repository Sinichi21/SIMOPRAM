<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityAssessment;
use App\Support\SchoolContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PublicAssessmentService
{
    public function setPublished(ActivityAssessment $assessment, bool $published): void
    {
        if ($assessment->school_id === null) {
            app(GlobalActivityAccess::class)->authorize($assessment->activity()->withoutGlobalScope('school')->firstOrFail());
        } else {
            abort_unless(auth()->user()?->can('activity_assessments.publish'), 403);
        }
        abort_unless(app(SchoolContext::class)->id() === $assessment->school_id, 404);

        DB::transaction(function () use ($assessment, $published): void {
            $assessment = ActivityAssessment::query()->where('school_id', $assessment->school_id)->lockForUpdate()->findOrFail($assessment->id);
            if ($published && (! $assessment->isPublished() || $this->rankings($assessment)->whereNotNull('score')->isEmpty())) {
                throw ValidationException::withMessages(['publication' => 'Publikasikan form dan lengkapi setidaknya satu hasil penilaian terlebih dahulu. Draft juri tidak dihitung.']);
            }
            $assessment->update(['results_published_at' => $published ? now() : null]);
            app(AssessmentAuditService::class)->record(
                action: $published ? 'activity_assessment.results_published' : 'activity_assessment.results_hidden',
                subject: $assessment, description: $published ? 'Hasil penilaian ditampilkan ke publik.' : 'Hasil penilaian disembunyikan dari publik.', module: 'activity_assessment',
            );
        });
    }

    /** @return Collection<int, ActivityAssessment> */
    public function publishedFor(Activity $activity): Collection
    {
        return ActivityAssessment::withoutGlobalScope('school')->where('school_id', $activity->school_id)
            ->where('activity_id', $activity->id)->where('status', 'published')
            ->whereNotNull('results_published_at')->where('results_published_at', '<=', now())->orderBy('id')->get();
    }

    /** @return Collection<int, array{name: string, score: ?float, rank: ?int}> */
    public function rankings(ActivityAssessment $assessment): Collection
    {
        $scope = fn ($query) => $query->withoutGlobalScope('school')->where('school_id', $assessment->school_id);
        $assessment->load(['targets' => $scope, 'targets.student' => $scope, 'targets.scoutUnit' => $scope, 'criteria' => $scope, 'judges' => $scope]);
        if ($assessment->is_special) {
            return app(ActivityJudgeService::class)->rankings($assessment)->map(fn (array $row): array => [
                'name' => $row['target']->participant_name ?? $row['target']->student?->name ?? $row['target']->scoutUnit?->name ?? 'Peserta',
                'score' => $row['score'], 'rank' => $row['rank'],
            ]);
        }
        $previousScore = null;
        $rank = 0;

        return $assessment->targets->sortByDesc(fn ($target) => $target->assessed_at ? $target->normalized_score : -1)->values()
            ->map(function ($target, int $index) use (&$previousScore, &$rank): array {
                $score = $target->assessed_at ? round($target->normalized_score, 2) : null;
                if ($score !== null && $score !== $previousScore) {
                    $rank = $index + 1;
                }
                $previousScore = $score;

                return ['name' => $target->participant_name ?? $target->student?->name ?? $target->scoutUnit?->name ?? 'Peserta', 'score' => $score, 'rank' => $score === null ? null : $rank];
            });
    }
}
