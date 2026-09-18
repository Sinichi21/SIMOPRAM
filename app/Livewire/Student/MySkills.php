<?php

namespace App\Livewire\Student;

use App\Models\AcademicYear;
use App\Models\Semester;
use App\Models\Student;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class MySkills extends Component
{
    public ?int $academicYearId = null;

    public ?int $semesterId = null;

    public function updatedAcademicYearId(): void
    {
        $this->semesterId = null;
    }

    public function render(): View
    {
        /*
        |--------------------------------------------------------------------------
        | User
        |--------------------------------------------------------------------------
        */

        $user = auth()->user();

        abort_unless(
            $user?->hasRole('student'),
            403
        );

        /*
        |--------------------------------------------------------------------------
        | Data siswa
        |--------------------------------------------------------------------------
        */

        $student = Student::query()
            ->where(
                'user_id',
                $user->id
            )
            ->with('school')
            ->first();

        if (! $student) {
            return view(
                'livewire.student.my-skills',
                $this->emptyData()
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Tahun ajaran milik siswa
        |--------------------------------------------------------------------------
        */

        $academicYearIds = $student
            ->enrollments()
            ->pluck(
                'academic_year_id'
            )
            ->filter()
            ->unique()
            ->values();

        $academicYears = AcademicYear::query()
            ->whereIn(
                'id',
                $academicYearIds
            )
            ->orderByDesc('start_date')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Default tahun ajaran
        |--------------------------------------------------------------------------
        */

        if (! $this->academicYearId) {
            $activeYear = $academicYears
                ->firstWhere(
                    'is_active',
                    true
                );

            $this->academicYearId =
                $activeYear?->id
                ?? $academicYears
                    ->first()
                    ?->id;
        }

        /*
        |--------------------------------------------------------------------------
        | Semester
        |--------------------------------------------------------------------------
        */

        $semesters = collect();

        if ($this->academicYearId) {
            $semesters = Semester::query()
                ->where(
                    'academic_year_id',
                    $this->academicYearId
                )
                ->orderBy(
                    'semester_number'
                )
                ->get();
        }

        if (
            ! $this->semesterId
            &&
            $semesters->isNotEmpty()
        ) {
            $activeSemester = $semesters
                ->firstWhere(
                    'is_active',
                    true
                );

            $this->semesterId =
                $activeSemester?->id
                ?? $semesters
                    ->first()
                    ?->id;
        }

        /*
        |--------------------------------------------------------------------------
        | Penilaian keterampilan
        |--------------------------------------------------------------------------
        */

        $assessments = $this
            ->assessmentTargets(
                $student
            );

        /*
        |--------------------------------------------------------------------------
        | Statistik
        |--------------------------------------------------------------------------
        */

        $statistics = [
            'total' => $assessments->count(),

            'individual' => $assessments
                ->where(
                    'mode',
                    'individual'
                )
                ->count(),

            'group' => $assessments
                ->where(
                    'mode',
                    'group'
                )
                ->count(),

            'average' => $assessments->isNotEmpty()
                    ? round(
                        (float)
                        $assessments
                            ->avg(
                                'normalized_score'
                            ),
                        1
                    )
                    : null,

            'highest' => $assessments->isNotEmpty()
                    ? round(
                        (float)
                        $assessments
                            ->max(
                                'normalized_score'
                            ),
                        1
                    )
                    : null,
        ];

        return view(
            'livewire.student.my-skills',
            [
                'student' => $student,

                'academicYears' => $academicYears,

                'semesters' => $semesters,

                'assessments' => $assessments,

                'statistics' => $statistics,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Ambil Target Penilaian Milik Siswa
    |--------------------------------------------------------------------------
    |
    | Target dapat berasal dari:
    |
    | 1. Individual
    |    activity_assessment_targets.student_id
    |
    | 2. Regu / Barung
    |    activity_assessment_target_members.student_id
    |
    |--------------------------------------------------------------------------
    */

    private function assessmentTargets(
        Student $student
    ): Collection {
        if (
            ! $this->academicYearId
            ||
            ! $this->semesterId
        ) {
            return collect();
        }

        $targets = DB::table(
            'activity_assessment_targets as aat'
        )
            ->join(
                'activity_assessments as aa',
                'aa.id',
                '=',
                'aat.activity_assessment_id'
            )
            ->join(
                'activities as a',
                'a.id',
                '=',
                'aa.activity_id'
            )
            ->leftJoin(
                'assessment_factors as af',
                'af.id',
                '=',
                'aa.assessment_factor_id'
            )
            ->where(
                'aa.school_id',
                $student->school_id
            )
            ->where(
                'aa.status',
                'published'
            )
            ->where(
                'a.academic_year_id',
                $this->academicYearId
            )
            ->where(
                'a.semester_id',
                $this->semesterId
            )
            ->where(
                function ($query) use (
                    $student
                ): void {
                    /*
                    |--------------------------------------------------------------------------
                    | Target individu
                    |--------------------------------------------------------------------------
                    */

                    $query->where(
                        'aat.student_id',
                        $student->id
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Target Regu / Barung
                    |--------------------------------------------------------------------------
                    */

                    $query->orWhereExists(
                        function (
                            $query
                        ) use (
                            $student
                        ): void {
                            $query
                                ->selectRaw('1')
                                ->from(
                                    'activity_assessment_target_members as aatm'
                                )
                                ->whereColumn(
                                    'aatm.activity_assessment_target_id',
                                    'aat.id'
                                )
                                ->where(
                                    'aatm.student_id',
                                    $student->id
                                );
                        }
                    );
                }
            )
            ->select([
                'aat.id as target_id',

                'aat.student_id',
                'aat.scout_unit_id',
                'aat.total_score',
                'aat.normalized_score',
                'aat.notes as target_notes',
                'aat.assessed_at',

                'aa.id as assessment_id',
                'aa.title as assessment_title',
                'aa.description as assessment_description',
                'aa.mode',
                'aa.published_at',

                'a.id as activity_id',
                'a.title as activity_title',
                'a.start_at',

                'af.name as factor_name',
            ])
            ->orderByDesc(
                'a.start_at'
            )
            ->orderByDesc(
                'aa.published_at'
            )
            ->get();

        if ($targets->isEmpty()) {
            return collect();
        }

        /*
        |--------------------------------------------------------------------------
        | Ambil skor tiap kriteria sekaligus
        |--------------------------------------------------------------------------
        */

        $criteria = DB::table(
            'activity_assessment_scores as aas'
        )
            ->join(
                'activity_assessment_criteria as aac',
                'aac.id',
                '=',
                'aas.activity_assessment_criterion_id'
            )
            ->whereIn(
                'aas.activity_assessment_target_id',
                $targets->pluck(
                    'target_id'
                )
            )
            ->select([
                'aas.activity_assessment_target_id',
                'aas.score',
                'aas.weighted_score',
                'aas.notes as score_notes',

                'aac.id as criterion_id',
                'aac.name',
                'aac.description',
                'aac.max_score',
                'aac.weight',
                'aac.sort_order',
            ])
            ->orderBy(
                'aac.sort_order'
            )
            ->orderBy(
                'aac.id'
            )
            ->get()
            ->groupBy(
                'activity_assessment_target_id'
            );

        /*
        |--------------------------------------------------------------------------
        | Bentuk data siap Blade
        |--------------------------------------------------------------------------
        */

        return $targets
            ->map(
                function (
                    object $target
                ) use (
                    $criteria
                ): array {
                    $criterionRows =
                        $criteria->get(
                            $target->target_id,
                            collect()
                        );

                    return [
                        'targetId' => (int)
                            $target->target_id,

                        'assessmentId' => (int)
                            $target
                                ->assessment_id,

                        'activityId' => (int)
                            $target->activity_id,

                        'title' => $target
                            ->assessment_title,

                        'description' => $target
                            ->assessment_description,

                        'activityTitle' => $target
                            ->activity_title,

                        'factorName' => $target
                            ->factor_name
                            ?: 'Keterampilan',

                        'mode' => $target->mode,

                        'isGroup' => $target
                            ->scout_unit_id
                            !== null,

                        'totalScore' => (float)
                            $target
                                ->total_score,

                        'normalized_score' => (float)
                            $target
                                ->normalized_score,

                        'notes' => $target
                            ->target_notes,

                        'assessedAt' => $target
                            ->assessed_at,

                        'publishedAt' => $target
                            ->published_at,

                        'activityStartAt' => $target
                            ->start_at,

                        'criteria' => $criterionRows
                            ->map(
                                function (
                                    object $row
                                ): array {
                                    $maxScore =
                                        (float)
                                        $row
                                            ->max_score;

                                    $score =
                                        (float)
                                        $row
                                            ->score;

                                    $percentage =
                                        $maxScore > 0
                                            ? min(
                                                100,
                                                max(
                                                    0,
                                                    (
                                                        $score
                                                        /
                                                        $maxScore
                                                    )
                                                    * 100
                                                )
                                            )
                                            : 0;

                                    return [
                                        'id' => (int)
                                            $row
                                                ->criterion_id,

                                        'name' => $row
                                            ->name,

                                        'description' => $row
                                            ->description,

                                        'score' => $score,

                                        'maxScore' => $maxScore,

                                        'weight' => (float)
                                            $row
                                                ->weight,

                                        'weightedScore' => (float)
                                            $row
                                                ->weighted_score,

                                        'notes' => $row
                                            ->score_notes,

                                        'percentage' => round(
                                            $percentage,
                                            1
                                        ),
                                    ];
                                }
                            )
                            ->values(),
                    ];
                }
            )
            ->values();
    }

    private function emptyData(): array
    {
        return [
            'student' => null,

            'academicYears' => collect(),

            'semesters' => collect(),

            'assessments' => collect(),

            'statistics' => [
                'total' => 0,
                'individual' => 0,
                'group' => 0,
                'average' => null,
                'highest' => null,
            ],
        ];
    }
}
