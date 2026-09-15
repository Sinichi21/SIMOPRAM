<?php

namespace App\Services;

use App\Models\ActivityAssessment;
use App\Models\ActivityJudge;
use App\Models\School;
use App\Support\SchoolContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ActivityJudgeService
{
    /** @return array{judge: ActivityJudge, token: string} */
    public function invite(ActivityAssessment $assessment, string $name): array
    {
        $this->authorizeManagement($assessment);
        abort_unless(app(SchoolContext::class)->id() === $assessment->school_id, 404);

        return DB::transaction(function () use ($assessment, $name): array {
            $assessment = ActivityAssessment::query()->lockForUpdate()->findOrFail($assessment->id);
            $assessment->load('activity');
            Validator::make(['name' => trim($name)], ['name' => 'required|string|max:150'])->validate();
            if (! $assessment->is_special || ! $assessment->isPublished() || ! $assessment->targets()->exists()) {
                throw ValidationException::withMessages(['judge' => 'Publikasikan form kegiatan khusus dan siapkan pesertanya terlebih dahulu.']);
            }
            $activity = $assessment->activity;
            if (! $activity?->start_at || ! $activity->end_at || $activity->end_at->lte($activity->start_at) || now()->gte($activity->end_at)) {
                throw ValidationException::withMessages(['judge' => 'Kegiatan harus memiliki waktu mulai dan selesai yang valid serta belum berakhir.']);
            }
            if ($assessment->judges()->whereNotNull('finalized_at')->exists()) {
                throw ValidationException::withMessages(['judge' => 'Daftar juri dikunci setelah ada juri yang melakukan finalisasi.']);
            }
            $token = Str::random(64);
            $judge = $assessment->judges()->create([
                'name' => trim($name), 'token_hash' => hash('sha256', $token),
                'starts_at' => $activity->start_at, 'expires_at' => $activity->end_at,
                'created_by' => auth()->id(),
            ]);
            app(AssessmentAuditService::class)->record(action: 'activity_judge.invited', subject: $judge,
                description: 'Link sementara juri diterbitkan.', module: 'activity_assessment');

            return compact('judge', 'token');
        });
    }

    public function revoke(ActivityAssessment $assessment, int $judgeId): void
    {
        $this->authorizeManagement($assessment);
        abort_unless(app(SchoolContext::class)->id() === $assessment->school_id, 404);
        DB::transaction(function () use ($assessment, $judgeId): void {
            $assessment = ActivityAssessment::query()->lockForUpdate()->findOrFail($assessment->id);
            $judge = $assessment->judges()->lockForUpdate()->findOrFail($judgeId);
            if ($judge->finalized_at) {
                throw ValidationException::withMessages(['judge' => 'Penilaian yang sudah final tidak dapat dibatalkan.']);
            }
            $judge->update(['revoked_at' => now()]);
            app(AssessmentAuditService::class)->record(action: 'activity_judge.revoked', subject: $judge,
                description: 'Akses juri dicabut.', module: 'activity_assessment');
        });
    }

    public function resolve(string $token): ActivityJudge
    {
        abort_unless(strlen($token) === 64, 404);
        $judge = ActivityJudge::withoutGlobalScope('school')->where('token_hash', hash('sha256', $token))->firstOrFail();
        if ($judge->school_id === null) {
            app(SchoolContext::class)->clear();
        } else {
            $school = School::query()->where('is_active', true)->findOrFail($judge->school_id);
            app(SchoolContext::class)->set($school);
        }
        $this->assertAccessible($judge);

        return $judge;
    }

    public function authorizeManagement(ActivityAssessment $assessment): void
    {
        if ($assessment->school_id === null) {
            app(GlobalActivityAccess::class)->authorize($assessment->activity()->withoutGlobalScope('school')->firstOrFail());
        } else {
            abort_unless(auth()->user()?->can('activity_assessments.publish'), 403);
        }
    }

    public function assertAccessible(ActivityJudge $judge): void
    {
        $judge->loadMissing('assessment.activity');
        $assessment = $judge->assessment;
        $activity = $assessment?->activity;
        abort_unless($assessment?->is_special && $assessment->isPublished() && $activity, 410, 'Link penilaian tidak tersedia.');
        abort_if($judge->revoked_at || $judge->finalized_at || now()->lt($judge->starts_at) || now()->gte($judge->expires_at)
            || ! $activity->start_at || ! $activity->end_at || now()->lt($activity->start_at) || now()->gte($activity->end_at)
            || $activity->status === 'cancelled', 410, 'Link belum aktif, sudah berakhir, atau penilaian sudah final.');
    }

    /** @param array<int, array<int, mixed>> $scores */
    public function save(ActivityJudge $judge, array $scores, bool $finalize): void
    {
        DB::transaction(function () use ($judge, $scores, $finalize): void {
            $assessment = ActivityAssessment::query()->lockForUpdate()->findOrFail($judge->activity_assessment_id);
            $judge = $assessment->judges()->lockForUpdate()->findOrFail($judge->id);
            $this->assertAccessible($judge);
            $assessment->load('targets.student', 'targets.scoutUnit', 'criteria');
            $rules = ['scores' => ['required', 'array:'.implode(',', $assessment->targets->modelKeys())]];
            $attributes = [];
            foreach ($assessment->targets as $target) {
                $participant = $target->participant_name ?? $target->student?->name ?? $target->scoutUnit?->name ?? 'Peserta';
                $attributes['scores.'.$target->id] = 'Nilai '.$participant;
                $rules['scores.'.$target->id] = [$finalize ? 'required' : 'sometimes', 'array:'.implode(',', $assessment->criteria->modelKeys())];
                foreach ($assessment->criteria as $criterion) {
                    $rules['scores.'.$target->id.'.'.$criterion->id] = [$finalize ? 'required' : 'nullable', 'numeric', 'min:0', 'max:'.$criterion->max_score];
                    $attributes['scores.'.$target->id.'.'.$criterion->id] = 'Nilai '.$criterion->name.' untuk '.$participant;
                }
            }
            $validated = Validator::make(['scores' => $scores], $rules, [], $attributes)->validate();
            $judge->update(['scores' => $validated['scores'], 'finalized_at' => $finalize ? now() : null]);
            app(AssessmentAuditService::class)->record(
                action: $finalize ? 'activity_judge.finalized' : 'activity_judge.saved', subject: $judge,
                description: $finalize ? 'Juri memfinalisasi nilai kegiatan khusus.' : 'Juri menyimpan draft nilai.',
                module: 'activity_assessment',
            );
        });
    }

    /** @return Collection<int, array{id: int, number: int, name: string, finalized: bool}> */
    public function resultJudges(ActivityAssessment $assessment): Collection
    {
        $assessment->loadMissing('judges');

        return $assessment->judges->whereNull('revoked_at')->sortBy('id')->values()
            ->map(fn ($judge, int $index): array => ['id' => $judge->id, 'number' => $index + 1, 'name' => $judge->name, 'finalized' => $judge->finalized_at !== null]);
    }

    /** @return Collection<int, array{target: mixed, score: float|null, rank: int|null, judge_scores: array<int, float|null>}> */
    public function rankings(ActivityAssessment $assessment): Collection
    {
        $assessment->loadMissing('targets.student', 'targets.scoutUnit', 'criteria', 'judges');
        $judges = $assessment->judges->whereNotNull('finalized_at')->whereNull('revoked_at');
        $rows = $assessment->targets->map(function ($target) use ($assessment, $judges): array {
            $scores = $judges->mapWithKeys(function ($judge) use ($target, $assessment): array {
                return [$judge->id => $assessment->criteria->sum(fn ($criterion) => (float) ($judge->scores[$target->id][$criterion->id] ?? 0) / $criterion->max_score * $criterion->weight)];
            });

            return ['target' => $target, 'score' => $scores->isEmpty() ? null : round($scores->avg(), 2), 'rank' => null,
                'judge_scores' => $scores->map(fn (float $score): float => round($score, 2))->all()];
        })->sortByDesc('score')->values();
        $previous = null;
        $rank = 0;

        return $rows->map(function (array $row, int $index) use (&$previous, &$rank): array {
            if ($row['score'] !== null) {
                if ($row['score'] !== $previous) {
                    $rank = $index + 1;
                }
                $row['rank'] = $rank;
                $previous = $row['score'];
            }

            return $row;
        });
    }
}
