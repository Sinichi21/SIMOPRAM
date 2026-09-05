<?php

namespace App\Livewire\Assessments\Activities;

use App\Models\ActivityAssessment;
use App\Models\ActivityAssessmentTarget;
use App\Services\ActivityAssessmentService;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Score extends Component
{
    public int $assessmentId;

    public ?int $selectedTargetId = null;

    public array $scores = [];

    public string $notes = '';

    public string $search = '';

    public function mount(
        int $assessmentId
    ): void {
        $this->authorizeScoring();

        $this->assessmentId =
            $assessmentId;

        $assessment =
            $this->assessment();

        if (
            $assessment->status
            !== 'published'
        ) {
            abort(
                409,
                'Form harus dipublikasikan sebelum melakukan penilaian.'
            );
        }

        $firstTarget =
            $assessment
                ->targets()
                ->orderBy('id')
                ->first();

        if ($firstTarget) {
            $this->selectTarget(
                $firstTarget->id
            );
        }
    }

    protected function assessment(): ActivityAssessment
    {
        return ActivityAssessment::query()
            ->with([
                'activity',
                'factor',
                'criteria',
            ])
            ->findOrFail(
                $this->assessmentId
            );
    }

    protected function target(): ?ActivityAssessmentTarget
    {
        if (
            ! $this->selectedTargetId
        ) {
            return null;
        }

        return ActivityAssessmentTarget::query()
            ->where(
                'activity_assessment_id',
                $this->assessmentId
            )
            ->with([
                'student',
                'scoutUnit',
                'members.student',
                'scores',
            ])
            ->findOrFail(
                $this->selectedTargetId
            );
    }

    public function selectTarget(
        int $targetId
    ): void {
        $this->authorizeScoring();

        $target =
            ActivityAssessmentTarget::query()
                ->where(
                    'activity_assessment_id',
                    $this->assessmentId
                )
                ->with(
                    'scores'
                )
                ->findOrFail(
                    $targetId
                );

        $this->selectedTargetId =
            $target->id;

        $this->scores = [];

        foreach (
            $target->scores as $score
        ) {
            $this->scores[
                $score
                    ->activity_assessment_criterion_id
            ] =
                (float) $score->score;
        }

        $this->notes =
            $target->notes
            ?? '';

        $this->resetValidation();
    }

    public function save(
        ActivityAssessmentService $service
    ): void {
        $this->authorizeScoring();

        $target =
            $this->target();

        if (! $target) {
            throw ValidationException::withMessages([
                'scores' => 'Pilih siswa atau regu yang akan dinilai.',
            ]);
        }

        $assessment =
            $this->assessment();

        abort_unless(
            $assessment->isPublished(),
            409,
            'Form harus dipublikasikan sebelum melakukan penilaian.'
        );

        foreach (
            $assessment->criteria as $criterion
        ) {
            $value =
                $this->scores[
                    $criterion->id
                ]
                ?? null;

            if (
                $value === null
                ||
                $value === ''
            ) {
                throw ValidationException::withMessages([
                    "scores.{$criterion->id}" => "Nilai {$criterion->name} wajib diisi.",
                ]);
            }

            if (
                ! is_numeric(
                    $value
                )
            ) {
                throw ValidationException::withMessages([
                    "scores.{$criterion->id}" => "Nilai {$criterion->name} harus berupa angka.",
                ]);
            }

            if (
                (float) $value < 0
                ||
                (float) $value
                    >
                    (float) $criterion
                        ->max_score
            ) {
                throw ValidationException::withMessages([
                    "scores.{$criterion->id}" => "Nilai {$criterion->name} harus antara 0 sampai "
                        .number_format(
                            $criterion
                                ->max_score,
                            2
                        )
                        .'.',
                ]);
            }
        }

        $service->saveTargetScores(
            $target,
            $this->scores,
            $this->notes
        );

        session()->flash(
            'status',
            'Nilai kegiatan berhasil disimpan dan rekap faktor diperbarui. '
            .'Nilai akhir akan ditandai perlu disinkronkan.'
        );

        /*
        |--------------------------------------------------------------------------
        | Refresh target
        |--------------------------------------------------------------------------
        */

        $this->selectTarget(
            $target->id
        );
    }

    public function syncMembers(ActivityAssessmentService $service): void
    {
        $this->authorizeScoring();
        $this->assessment();
        $target = $this->target();

        if (! $target) {
            throw ValidationException::withMessages(['members' => 'Pilih regu yang akan diperbarui.']);
        }

        $added = $service->syncTeamMembers($target);
        $this->resetValidation();

        session()->flash('status', "{$added} anggota baru ditambahkan ke penerima nilai. "
            .'Rekap faktor diperbarui; jalankan sinkronisasi nilai akhir.');
    }

    protected function authorizeScoring(): void
    {
        abort_unless(
            auth()->user()?->can(
                'activity_assessments.score'
            ),
            403
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Preview Nilai
    |--------------------------------------------------------------------------
    */

    protected function previewNormalizedScore(
        ActivityAssessment $assessment
    ): float {
        $result = 0.0;

        foreach (
            $assessment->criteria as $criterion
        ) {
            $score =
                $this->scores[
                    $criterion->id
                ]
                ?? null;

            if (
                $score === null
                ||
                ! is_numeric(
                    $score
                )
            ) {
                continue;
            }

            $maxScore =
                (float) $criterion
                    ->max_score;

            if (
                $maxScore <= 0
            ) {
                continue;
            }

            $value =
                min(
                    $maxScore,
                    max(
                        0,
                        (float) $score
                    )
                );

            $result +=
                (
                    $value
                    /
                    $maxScore
                )
                *
                (float) $criterion
                    ->weight;
        }

        return round(
            min(
                100,
                max(
                    0,
                    $result
                )
            ),
            2
        );
    }

    public function render()
    {
        $assessment =
            $this->assessment();

        $targets =
            ActivityAssessmentTarget::query()
                ->where(
                    'activity_assessment_id',
                    $assessment->id
                )
                ->with([
                    'student',
                    'scoutUnit',
                    'members.student',
                ])
                ->when(
                    trim(
                        $this->search
                    ) !== '',
                    function ($query) use (
                        $assessment
                    ): void {
                        $search =
                            '%'
                            .trim(
                                $this->search
                            )
                            .'%';

                        if (
                            $assessment->mode
                            === 'individual'
                        ) {
                            $query->whereHas(
                                'student',
                                function ($query) use (
                                    $search
                                ): void {
                                    $query->where(
                                        function ($query) use (
                                            $search
                                        ): void {
                                            $query
                                                ->where(
                                                    'name',
                                                    'like',
                                                    $search
                                                )
                                                ->orWhere(
                                                    'nis',
                                                    'like',
                                                    $search
                                                );
                                        }
                                    );
                                }
                            );
                        } else {
                            $query->whereHas(
                                'scoutUnit',
                                fn ($query) => $query->where(
                                    'name',
                                    'like',
                                    $search
                                )
                            );
                        }
                    }
                )
                ->get()
                ->sortBy(
                    function (
                        ActivityAssessmentTarget $target
                    ) use (
                        $assessment
                    ): string {
                        return $assessment->mode
                            === 'individual'
                                ? (
                                    $target
                                        ->student
                                        ?->name
                                    ?? ''
                                )
                                : (
                                    $target
                                        ->scoutUnit
                                        ?->name
                                    ?? ''
                                );
                    }
                )
                ->values();

        $selectedTarget =
            $this->target();

        $assessedCount =
            ActivityAssessmentTarget::query()
                ->where(
                    'activity_assessment_id',
                    $assessment->id
                )
                ->whereNotNull(
                    'assessed_at'
                )
                ->count();

        $totalTargets =
            ActivityAssessmentTarget::query()
                ->where(
                    'activity_assessment_id',
                    $assessment->id
                )
                ->count();

        return view(
            'livewire.assessments.activities.score',
            [
                'assessment' => $assessment,

                'targets' => $targets,

                'selectedTarget' => $selectedTarget,

                'assessedCount' => $assessedCount,

                'totalTargets' => $totalTargets,

                'previewScore' => $this->previewNormalizedScore(
                    $assessment
                ),
            ]
        );
    }
}
