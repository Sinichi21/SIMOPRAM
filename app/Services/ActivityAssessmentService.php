<?php

namespace App\Services;

use App\Models\ActivityAssessment;
use App\Models\ActivityAssessmentTarget;
use App\Models\ActivityAssessmentTargetMember;
use App\Models\AssessmentConfig;
use App\Models\ScoutUnit;
use App\Models\Student;
use App\Models\StudentScore;
use App\Support\SchoolContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ActivityAssessmentService
{
    /*
    |--------------------------------------------------------------------------
    | Validasi Publish
    |--------------------------------------------------------------------------
    */

    public function validateForPublish(
        ActivityAssessment $assessment
    ): void {
        $assessment->loadMissing([
            'criteria',
            'activity',
            'factor',
        ]);

        if (! $assessment->activity) {
            throw ValidationException::withMessages([
                'assessment' => 'Kegiatan pada form penilaian tidak ditemukan.',
            ]);
        }

        if (! $assessment->factor) {
            throw ValidationException::withMessages([
                'assessment_factor_id' => 'Faktor penilaian tidak ditemukan.',
            ]);
        }

        if (
            ! in_array(
                $assessment->mode,
                [
                    'individual',
                    'team',
                ],
                true
            )
        ) {
            throw ValidationException::withMessages([
                'mode' => 'Mode penilaian tidak valid.',
            ]);
        }

        if (
            $assessment->criteria->isEmpty()
        ) {
            throw ValidationException::withMessages([
                'criteria' => 'Form penilaian harus memiliki minimal satu kriteria.',
            ]);
        }

        $totalWeight =
            (float) $assessment
                ->criteria
                ->sum('weight');

        if (
            abs(
                $totalWeight - 100
            ) > 0.01
        ) {
            throw ValidationException::withMessages([
                'criteria' => 'Total bobot seluruh kriteria harus tepat 100%. '
                    .'Total saat ini '
                    .number_format(
                        $totalWeight,
                        2
                    )
                    .'%.',
            ]);
        }

        foreach (
            $assessment->criteria as $criterion
        ) {
            if (
                (float) $criterion->max_score
                <= 0
            ) {
                throw ValidationException::withMessages([
                    'criteria' => "Nilai maksimum kriteria {$criterion->name} "
                        .'harus lebih besar dari 0.',
                ]);
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Generate Target
    |--------------------------------------------------------------------------
    */

    public function prepareTargets(
        ActivityAssessment $assessment
    ): int {
        $assessment->loadMissing(
            'activity'
        );

        if (! $assessment->activity) {
            throw ValidationException::withMessages([
                'activity' => 'Kegiatan tidak ditemukan.',
            ]);
        }

        return $assessment->mode === 'team'
            ? $this->prepareTeamTargets(
                $assessment
            )
            : $this->prepareIndividualTargets(
                $assessment
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Target Individu
    |--------------------------------------------------------------------------
    */

    protected function prepareIndividualTargets(
        ActivityAssessment $assessment
    ): int {
        $activity =
            $assessment->activity;

        $students =
            Student::query()
                ->where(
                    'status',
                    'active'
                )
                ->whereHas(
                    'enrollments',
                    function ($query) use (
                        $activity
                    ): void {
                        $query->where(
                            'academic_year_id',
                            $activity
                                ->academic_year_id
                        );
                    }
                )
                ->orderBy(
                    'name'
                )
                ->get([
                    'id',
                ]);

        $studentIds =
            $students
                ->pluck('id')
                ->map(
                    fn ($id): int => (int) $id
                )
                ->all();

        /*
        |--------------------------------------------------------------------------
        | Hapus target lama yang belum pernah dinilai dan tidak lagi eligible.
        |--------------------------------------------------------------------------
        */

        $obsoleteQuery =
            $assessment
                ->targets()
                ->whereNull(
                    'assessed_at'
                )
                ->whereNotNull(
                    'student_id'
                );

        if (
            count($studentIds) > 0
        ) {
            $obsoleteQuery
                ->whereNotIn(
                    'student_id',
                    $studentIds
                );
        }

        $obsoleteQuery->delete();

        $created = 0;

        foreach (
            $studentIds as $studentId
        ) {
            $target =
                $assessment
                    ->targets()
                    ->firstOrCreate(
                        [
                            'student_id' => $studentId,
                        ],
                        [
                            'scout_unit_id' => null,

                            'total_score' => 0,

                            'normalized_score' => 0,
                        ]
                    );

            if (
                $target->wasRecentlyCreated
            ) {
                $created++;
            }
        }

        return $created;
    }

    /*
    |--------------------------------------------------------------------------
    | Target Regu
    |--------------------------------------------------------------------------
    */

    protected function prepareTeamTargets(
        ActivityAssessment $assessment
    ): int {
        $activity =
            $assessment->activity;

        $schoolId =
            app(
                SchoolContext::class
            )->id();

        abort_unless(
            $schoolId,
            409,
            'Pilih sekolah aktif terlebih dahulu.'
        );

        /*
        |--------------------------------------------------------------------------
        | Scout Unit
        |--------------------------------------------------------------------------
        */

        $unitQuery =
            ScoutUnit::query();

        /*
        |--------------------------------------------------------------------------
        | Beberapa implementasi scout_units menyimpan academic_year_id,
        | beberapa tidak. Filter hanya jika kolom tersedia.
        |--------------------------------------------------------------------------
        */

        if (
            Schema::hasColumn(
                'scout_units',
                'academic_year_id'
            )
        ) {
            $unitQuery->where(
                'academic_year_id',
                $activity
                    ->academic_year_id
            );
        }

        if (
            Schema::hasColumn(
                'scout_units',
                'is_active'
            )
        ) {
            $unitQuery->where(
                'is_active',
                true
            );
        }

        $units =
            $unitQuery
                ->orderBy(
                    'name'
                )
                ->get();

        $unitIds =
            $units
                ->pluck('id')
                ->map(
                    fn ($id): int => (int) $id
                )
                ->all();

        /*
        |--------------------------------------------------------------------------
        | Hapus target kosong lama yang tidak lagi eligible.
        |--------------------------------------------------------------------------
        */

        $obsoleteQuery =
            $assessment
                ->targets()
                ->whereNull(
                    'assessed_at'
                )
                ->whereNotNull(
                    'scout_unit_id'
                );

        if (
            count($unitIds) > 0
        ) {
            $obsoleteQuery
                ->whereNotIn(
                    'scout_unit_id',
                    $unitIds
                );
        }

        $obsoleteQuery->delete();

        $created = 0;

        foreach (
            $units as $unit
        ) {
            $target =
                $assessment
                    ->targets()
                    ->firstOrCreate(
                        [
                            'scout_unit_id' => $unit->id,
                        ],
                        [
                            'student_id' => null,

                            'total_score' => 0,

                            'normalized_score' => 0,
                        ]
                    );

            if (
                $target->wasRecentlyCreated
            ) {
                $created++;
            }

            /*
            |--------------------------------------------------------------------------
            | Jika target sudah pernah dinilai, snapshot anggota tidak diubah.
            |--------------------------------------------------------------------------
            */

            if (
                $target->assessed_at
            ) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Ambil anggota regu dari master.
            |--------------------------------------------------------------------------
            */

            $memberQuery =
                DB::table(
                    'scout_unit_members'
                )
                    ->where(
                        'scout_unit_id',
                        $unit->id
                    );

            if (
                Schema::hasColumn(
                    'scout_unit_members',
                    'school_id'
                )
            ) {
                $memberQuery->where(
                    'school_id',
                    $schoolId
                );
            }

            if (
                Schema::hasColumn(
                    'scout_unit_members',
                    'academic_year_id'
                )
            ) {
                $memberQuery->where(
                    'academic_year_id',
                    $activity
                        ->academic_year_id
                );
            }

            if (
                Schema::hasColumn(
                    'scout_unit_members',
                    'is_active'
                )
            ) {
                $memberQuery->where(
                    'is_active',
                    true
                );
            }

            if (
                Schema::hasColumn(
                    'scout_unit_members',
                    'left_at'
                )
            ) {
                $memberQuery->whereNull(
                    'left_at'
                );
            }

            $memberIds =
                $memberQuery
                    ->pluck(
                        'student_id'
                    )
                    ->unique()
                    ->filter()
                    ->map(
                        fn ($id): int => (int) $id
                    );

            /*
            |--------------------------------------------------------------------------
            | Reset snapshot hanya selama target belum dinilai.
            |--------------------------------------------------------------------------
            */

            $target
                ->members()
                ->delete();

            foreach (
                $memberIds as $studentId
            ) {
                ActivityAssessmentTargetMember::query()
                    ->create([
                        'activity_assessment_target_id' => $target->id,

                        'student_id' => $studentId,
                    ]);
            }
        }

        return $created;
    }

    /*
    |--------------------------------------------------------------------------
    | Publish Form
    |--------------------------------------------------------------------------
    */

    public function publish(
        ActivityAssessment $assessment
    ): void {
        DB::transaction(
            function () use (
                $assessment
            ): void {
                $this->validateForPublish(
                    $assessment
                );

                $this->prepareTargets(
                    $assessment
                );

                $assessment->update([
                    'status' => 'published',

                    'published_by' => auth()->id(),

                    'published_at' => now(),
                ]);
            }
        );

        $this->syncToStudentScores(
            $assessment->fresh()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Buka Kembali Draft
    |--------------------------------------------------------------------------
    */

    public function reopen(
        ActivityAssessment $assessment
    ): void {
        $assessment->update([
            'status' => 'draft',

            'published_by' => null,

            'published_at' => null,
        ]);

        $this->syncToStudentScores(
            $assessment->fresh()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Simpan Nilai
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

        if (! $assessment) {
            throw ValidationException::withMessages([
                'scores' => 'Form penilaian tidak ditemukan.',
            ]);
        }

        if (
            ! $assessment->isPublished()
        ) {
            throw ValidationException::withMessages([
                'scores' => 'Form harus dipublikasikan sebelum melakukan penilaian.',
            ]);
        }

        $criteria =
            $assessment->criteria;

        if (
            $criteria->isEmpty()
        ) {
            throw ValidationException::withMessages([
                'scores' => 'Form belum memiliki kriteria penilaian.',
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
                    $criteria as $criterion
                ) {
                    $criterionId =
                        (int) $criterion->id;

                    if (
                        ! array_key_exists(
                            $criterionId,
                            $scores
                        )
                        &&
                        ! array_key_exists(
                            (string) $criterionId,
                            $scores
                        )
                    ) {
                        throw ValidationException::withMessages([
                            "scores.{$criterionId}" => "Nilai {$criterion->name} belum diisi.",
                        ]);
                    }

                    $score =
                        (float) (
                            $scores[
                                $criterionId
                            ]
                            ??
                            $scores[
                                (string) $criterionId
                            ]
                        );

                    $maxScore =
                        (float) $criterion
                            ->max_score;

                    if (
                        $score < 0
                        ||
                        $score > $maxScore
                    ) {
                        throw ValidationException::withMessages([
                            "scores.{$criterionId}" => "Nilai {$criterion->name} harus antara 0 sampai "
                                .number_format(
                                    $maxScore,
                                    2
                                )
                                .'.',
                        ]);
                    }

                    $weightedScore =
                        $maxScore > 0
                            ? (
                                $score
                                /
                                $maxScore
                            )
                            *
                            (float) $criterion
                                ->weight
                            : 0;

                    $target
                        ->scores()
                        ->updateOrCreate(
                            [
                                'activity_assessment_criterion_id' => $criterionId,
                            ],
                            [
                                'score' => round(
                                    $score,
                                    2
                                ),

                                'weighted_score' => round(
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
                    'total_score' => round(
                        $totalScore,
                        2
                    ),

                    'normalized_score' => round(
                        min(
                            100,
                            max(
                                0,
                                $normalizedScore
                            )
                        ),
                        2
                    ),

                    'notes' => trim(
                        (string) $notes
                    ) ?: null,

                    'assessed_by' => auth()->id(),

                    'assessed_at' => now(),
                ]);
            }
        );

        if (
            $assessment->isPublished()
        ) {
            $this->syncToStudentScores(
                $assessment
            );
        }

        return $target->fresh([
            'scores',
            'members.student',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Assessment Config Tujuan
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
                fn ($query) => $query->where(
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
                fn ($query) => $query->where(
                    'assessment_factor_id',
                    $assessment
                        ->assessment_factor_id
                )
            )
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Rekap Seluruh Form Published → StudentScore
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

        $activity =
            $assessment->activity;

        /*
        |--------------------------------------------------------------------------
        | Semua form published pada faktor dan periode yang sama
        |--------------------------------------------------------------------------
        */

        $forms =
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
                        $activity
                    ): void {
                        $query->where(
                            'academic_year_id',
                            $activity
                                ->academic_year_id
                        );

                        if (
                            $activity
                                ->semester_id
                        ) {
                            $query->where(
                                'semester_id',
                                $activity
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
        | Rekap nilai per siswa
        |--------------------------------------------------------------------------
        */

        $studentScores = [];

        foreach (
            $forms as $form
        ) {
            foreach (
                $form->targets as $target
            ) {
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
                        $target->members as $member
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

        /*
        |--------------------------------------------------------------------------
        | Siswa yang sebelumnya mempunyai rekap
        |--------------------------------------------------------------------------
        */

        $previousStudentIds =
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
                ->where(
                    'notes',
                    'like',
                    'Rekap otomatis penilaian kegiatan%'
                )
                ->pluck(
                    'student_id'
                );

        /*
        |--------------------------------------------------------------------------
        | Siswa hasil rekap baru
        |--------------------------------------------------------------------------
        */

        $newStudentIds =
            collect(
                array_keys(
                    $studentScores
                )
            );

        /*
        |--------------------------------------------------------------------------
        | Seluruh siswa terdampak
        |--------------------------------------------------------------------------
        */

        $affectedStudentIds =
            $previousStudentIds
                ->merge(
                    $newStudentIds
                )
                ->map(
                    fn ($id): int => (int) $id
                )
                ->unique()
                ->values();

        $updated =
            0;

        DB::transaction(
            function () use (
                $config,
                $assessment,
                $studentScores,
                $affectedStudentIds,
                &$updated
            ): void {

                /*
                |--------------------------------------------------------------------------
                | Hapus rekap lama
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
                    ->where(
                        'notes',
                        'like',
                        'Rekap otomatis penilaian kegiatan%'
                    )
                    ->delete();

                /*
                |--------------------------------------------------------------------------
                | Simpan rekap terbaru
                |--------------------------------------------------------------------------
                */

                foreach (
                    $studentScores as $studentId => $scores
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
                                'assessment_config_id' => $config->id,

                                'student_id' => (int) $studentId,

                                'assessment_factor_id' => $assessment
                                    ->assessment_factor_id,
                            ],
                            [
                                'score' => round(
                                    $average,
                                    2
                                ),

                                'source' => 'system',

                                'source_version' => null,

                                'source_synced_at' => now(),

                                'entered_by' => auth()->id(),

                                'notes' => 'Rekap otomatis penilaian kegiatan.',
                            ]
                        );

                    $updated++;
                }

                /*
                |--------------------------------------------------------------------------
                | Invalidasi nilai akhir siswa yang terdampak
                |--------------------------------------------------------------------------
                */

                app(
                    AssessmentService::class
                )
                    ->invalidateFinalGrades(
                        $config,
                        $affectedStudentIds
                    );
            }
        );

        return $updated;
    }
}
