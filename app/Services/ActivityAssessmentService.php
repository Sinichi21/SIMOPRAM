<?php

namespace App\Services;

use App\Models\ActivityAssessment;
use App\Models\ActivityAssessmentTarget;
use App\Models\AssessmentConfig;
use App\Models\Student;
use App\Models\StudentScore;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ActivityAssessmentService
{
    /*
    |--------------------------------------------------------------------------
    | Validasi Sebelum Publish
    |--------------------------------------------------------------------------
    */

    public function validateForPublish(
        ActivityAssessment $assessment
    ): void {
        $assessment->loadMissing(
            'criteria'
        );


        if (
            $assessment
                ->criteria
                ->isEmpty()
        ) {
            throw ValidationException::withMessages([
                'criteria' =>
                    'Form penilaian harus memiliki minimal satu kriteria.',
            ]);
        }


        $totalWeight =
            (float) $assessment
                ->criteria
                ->sum(
                    'weight'
                );


        if (
            abs(
                $totalWeight - 100
            ) > 0.01
        ) {
            throw ValidationException::withMessages([
                'criteria' =>
                    'Total bobot seluruh kriteria harus tepat 100%. '
                    . 'Total saat ini: '
                    . number_format(
                        $totalWeight,
                        2
                    )
                    . '%.',
            ]);
        }


        foreach (
            $assessment->criteria
            as $criterion
        ) {
            if (
                (float) $criterion
                    ->max_score
                <= 0
            ) {
                throw ValidationException::withMessages([
                    'criteria' =>
                        'Nilai maksimum setiap kriteria harus lebih dari 0.',
                ]);
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Publish
    |--------------------------------------------------------------------------
    */

    public function publish(
        ActivityAssessment $assessment
    ): void {
        $this->validateForPublish(
            $assessment
        );


        $assessment->update([
            'status' =>
                'published',

            'published_by' =>
                auth()->id(),

            'published_at' =>
                now(),
        ]);


        /*
        |--------------------------------------------------------------------------
        | Jika sudah memiliki target penilaian, sinkronkan langsung.
        |--------------------------------------------------------------------------
        */

        $this->syncToStudentScores(
            $assessment
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Kembalikan ke Draft
    |--------------------------------------------------------------------------
    */

    public function reopen(
        ActivityAssessment $assessment
    ): void {
        $assessment->update([
            'status' =>
                'draft',

            'published_by' =>
                null,

            'published_at' =>
                null,
        ]);


        /*
        |--------------------------------------------------------------------------
        | Form ini tidak lagi ikut rekap.
        |--------------------------------------------------------------------------
        */

        $this->syncToStudentScores(
            $assessment
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Simpan Nilai Target
    |--------------------------------------------------------------------------
    */

    public function saveTargetScores(
        ActivityAssessmentTarget $target,
        array $scores,
        ?string $notes = null
    ): ActivityAssessmentTarget {
        $target->loadMissing([
            'assessment.criteria',
        ]);


        $assessment =
            $target->assessment;


        $criteria =
            $assessment->criteria;


        if ($criteria->isEmpty()) {
            throw ValidationException::withMessages([
                'scores' =>
                    'Form belum memiliki kriteria penilaian.',
            ]);
        }


        $totalScore =
            0.0;

        $normalizedScore =
            0.0;


        DB::transaction(
            function () use (
                $target,
                $criteria,
                $scores,
                $notes,
                &$totalScore,
                &$normalizedScore
            ): void {

                foreach (
                    $criteria
                    as $criterion
                ) {
                    if (
                        ! array_key_exists(
                            $criterion->id,
                            $scores
                        )
                    ) {
                        throw ValidationException::withMessages([
                            'scores' =>
                                "Nilai {$criterion->name} belum diisi.",
                        ]);
                    }


                    $score =
                        (float) $scores[
                            $criterion->id
                        ];


                    if (
                        $score < 0
                        ||
                        $score
                            > (float) $criterion
                                ->max_score
                    ) {
                        throw ValidationException::withMessages([
                            'scores' =>
                                "Nilai {$criterion->name} harus antara 0 sampai "
                                . number_format(
                                    (float) $criterion
                                        ->max_score,
                                    2
                                )
                                . '.',
                        ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Contoh:
                    |
                    | score     = 80
                    | max_score = 100
                    | weight    = 25
                    |
                    | weighted = 80 / 100 × 25
                    |          = 20
                    |--------------------------------------------------------------------------
                    */

                    $weightedScore =
                        $criterion->max_score > 0
                            ? (
                                $score
                                /
                                (float) $criterion
                                    ->max_score
                            )
                            *
                            (float) $criterion
                                ->weight
                            : 0;


                    $target
                        ->scores()
                        ->updateOrCreate(
                            [
                                'activity_assessment_criterion_id' =>
                                    $criterion->id,
                            ],
                            [
                                'score' =>
                                    round(
                                        $score,
                                        2
                                    ),

                                'weighted_score' =>
                                    round(
                                        $weightedScore,
                                        4
                                    ),
                            ]
                        );


                    $totalScore +=
                        $score;

                    $normalizedScore +=
                        $weightedScore;
                }


                $target->update([
                    'total_score' =>
                        round(
                            $totalScore,
                            2
                        ),

                    'normalized_score' =>
                        round(
                            min(
                                100,
                                max(
                                    0,
                                    $normalizedScore
                                )
                            ),
                            2
                        ),

                    'notes' =>
                        $notes,

                    'assessed_by' =>
                        auth()->id(),

                    'assessed_at' =>
                        now(),
                ]);
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Jika form sudah publish, nilai langsung masuk rekap faktor.
        |--------------------------------------------------------------------------
        */

        if (
            $assessment->isPublished()
        ) {
            $this->syncToStudentScores(
                $assessment
            );
        }


        return $target->fresh([
            'scores',
            'members',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Cari AssessmentConfig Tujuan
    |--------------------------------------------------------------------------
    */

    public function resolveAssessmentConfig(
        ActivityAssessment $assessment
    ): ?AssessmentConfig {
        $assessment->loadMissing(
            'activity'
        );


        $activity =
            $assessment->activity;


        if (! $activity) {
            return null;
        }


        return AssessmentConfig::query()
            ->where(
                'academic_year_id',
                $activity
                    ->academic_year_id
            )
            ->when(
                $activity->semester_id,
                fn ($query) =>
                    $query->where(
                        'semester_id',
                        $activity
                            ->semester_id
                    )
            )
            ->where(
                'is_active',
                true
            )
            ->whereHas(
                'items',
                fn ($query) =>
                    $query->where(
                        'assessment_factor_id',
                        $assessment
                            ->assessment_factor_id
                    )
            )
            ->first();
    }


    /*
    |--------------------------------------------------------------------------
    | Sinkronkan Rekap ke StudentScore
    |--------------------------------------------------------------------------
    */

    public function syncToStudentScores(
        ActivityAssessment $assessment
    ): int {
        $config =
            $this->resolveAssessmentConfig(
                $assessment
            );


        if (! $config) {
            return 0;
        }


        $assessment->loadMissing(
            'activity'
        );


        /*
        |--------------------------------------------------------------------------
        | Semua form PUBLISHED untuk faktor yang sama pada periode yang sama.
        |--------------------------------------------------------------------------
        */

        $assessments =
            ActivityAssessment::query()
                ->where(
                    'assessment_factor_id',
                    $assessment
                        ->assessment_factor_id
                )
                ->where(
                    'status',
                    'published'
                )
                ->whereHas(
                    'activity',
                    function ($query) use (
                        $assessment
                    ): void {
                        $query->where(
                            'academic_year_id',
                            $assessment
                                ->activity
                                ->academic_year_id
                        );


                        if (
                            $assessment
                                ->activity
                                ->semester_id
                        ) {
                            $query->where(
                                'semester_id',
                                $assessment
                                    ->activity
                                    ->semester_id
                            );
                        }
                    }
                )
                ->with([
                    'targets.members',
                ])
                ->get();


        /*
        |--------------------------------------------------------------------------
        | Kumpulkan kontribusi per siswa.
        |--------------------------------------------------------------------------
        */

        $studentScores = [];


        foreach (
            $assessments
            as $form
        ) {
            foreach (
                $form->targets
                as $target
            ) {
                /*
                |--------------------------------------------------------------------------
                | Target belum dinilai.
                |--------------------------------------------------------------------------
                */

                if (
                    ! $target
                        ->assessed_at
                ) {
                    continue;
                }


                /*
                |--------------------------------------------------------------------------
                | Individu
                |--------------------------------------------------------------------------
                */

                if (
                    $form->mode
                    === 'individual'
                    &&
                    $target->student_id
                ) {
                    $studentScores[
                        $target->student_id
                    ][] =
                        (float) $target
                            ->normalized_score;

                    continue;
                }


                /*
                |--------------------------------------------------------------------------
                | Regu
                |--------------------------------------------------------------------------
                */

                if (
                    $form->mode
                    === 'team'
                ) {
                    foreach (
                        $target->members
                        as $member
                    ) {
                        $studentScores[
                            $member->student_id
                        ][] =
                            (float) $target
                                ->normalized_score;
                    }
                }
            }
        }


        $updated =
            0;


        DB::transaction(
            function () use (
                $config,
                $assessment,
                $studentScores,
                &$updated
            ): void {

                /*
                |--------------------------------------------------------------------------
                | Hapus nilai otomatis lama untuk faktor ini.
                |--------------------------------------------------------------------------
                |
                | Asumsi desain:
                | satu faktor otomatis menerima satu jenis sumber sistem.
                |--------------------------------------------------------------------------
                */

                StudentScore::query()
                    ->where(
                        'assessment_config_id',
                        $config->id
                    )
                    ->where(
                        'assessment_factor_id',
                        $assessment
                            ->assessment_factor_id
                    )
                    ->where(
                        'source',
                        'system'
                    )
                    ->delete();


                /*
                |--------------------------------------------------------------------------
                | Tulis nilai rekap baru.
                |--------------------------------------------------------------------------
                */

                foreach (
                    $studentScores
                    as $studentId =>
                        $scores
                ) {
                    if (
                        count(
                            $scores
                        ) === 0
                    ) {
                        continue;
                    }


                    $average =
                        array_sum(
                            $scores
                        )
                        /
                        count(
                            $scores
                        );


                    StudentScore::query()
                        ->updateOrCreate(
                            [
                                'assessment_config_id' =>
                                    $config->id,

                                'student_id' =>
                                    (int) $studentId,

                                'assessment_factor_id' =>
                                    $assessment
                                        ->assessment_factor_id,
                            ],
                            [
                                'score' =>
                                    round(
                                        $average,
                                        2
                                    ),

                                'source' =>
                                    'system',

                                'source_version' =>
                                    null,

                                'source_synced_at' =>
                                    now(),

                                'entered_by' =>
                                    auth()->id(),

                                'notes' =>
                                    'Rekap otomatis penilaian kegiatan.',
                            ]
                        );


                    $updated++;
                }
            }
        );


        return $updated;
    }
}