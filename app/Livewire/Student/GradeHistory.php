<?php

namespace App\Livewire\Student;

use App\Models\AcademicYear;
use App\Models\Semester;
use App\Models\Student;
use App\Services\GradeReportService;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Throwable;

class GradeHistory extends Component
{
    public function render(
        GradeReportService $service
    ): View {
        $user = auth()->user();

        abort_unless(
            $user?->hasRole('student'),
            403
        );

        $student = Student::query()
            ->where(
                'user_id',
                $user->id
            )
            ->with('school')
            ->first();

        if (! $student) {
            return view(
                'livewire.student.grade-history',
                [
                    'student' => null,
                    'histories' => collect(),
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Tahun ajaran yang pernah diikuti siswa
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
            ->get()
            ->keyBy('id');

        /*
        |--------------------------------------------------------------------------
        | Semua semester pada tahun tersebut
        |--------------------------------------------------------------------------
        */

        $semesters = Semester::query()
            ->whereIn(
                'academic_year_id',
                $academicYearIds
            )
            ->orderByDesc('start_date')
            ->orderByDesc(
                'semester_number'
            )
            ->get();

        $histories = $semesters
            ->map(
                function (
                    Semester $semester
                ) use (
                    $student,
                    $academicYears,
                    $service
                ): ?array {
                    try {
                        $data = $service->getData(
                            $semester
                                ->academic_year_id,

                            $semester->id,

                            null,

                            filled($student->nis)
                                ? $student->nis
                                : $student->name
                        );
                    } catch (Throwable $exception) {
                        report($exception);

                        return null;
                    }

                    if (
                        ! $data[
                            'selectedConfig'
                        ]
                    ) {
                        return null;
                    }

                    $studentRow =
                        $data['students']
                            ->first(
                                function (
                                    $candidate
                                ) use (
                                    $student
                                ): bool {
                                    if (
                                        (int)
                                        $candidate->id
                                        ===
                                        (int)
                                        $student->id
                                    ) {
                                        return true;
                                    }

                                    return
                                        filled(
                                            $student->nis
                                        )
                                        &&
                                        (string)
                                        $candidate->nis
                                        ===
                                        (string)
                                        $student->nis;
                                }
                            );

                    if (! $studentRow) {
                        return null;
                    }

                    $scores = $data[
                        'scores'
                    ]->get(
                        $studentRow->id,
                        collect()
                    );

                    $final = $data[
                        'finalGrades'
                    ]->get(
                        $studentRow->id
                    );

                    $factors = collect(
                        $data[
                            'selectedConfig'
                        ]->items
                    )->map(
                        function (
                            $item
                        ) use (
                            $scores
                        ): array {
                            $score =
                                $scores->get(
                                    $item
                                        ->assessment_factor_id
                                );

                            return [
                                'name' => $item
                                    ->factor
                                    ->name,

                                'weight' => (float)
                                    $item->weight,

                                'score' => $score?->score,
                            ];
                        }
                    );

                    return [
                        'academicYear' => $academicYears->get(
                            $semester
                                ->academic_year_id
                        ),

                        'semester' => $semester,

                        'finalScore' => $final?->final_score,

                        'letterGrade' => $final?->letter_grade,

                        'description' => $final?->description,

                        'factors' => $factors,

                        'isOfficial' => $data[
                                'isOfficialSnapshot'
                            ] ?? false,

                        'closureVersion' => $data[
                                'selectedClosure'
                            ]?->version,

                        'reportSource' => $data[
                                'reportSource'
                            ] ?? null,
                    ];
                }
            )
            ->filter()
            ->values();

        return view(
            'livewire.student.grade-history',
            [
                'student' => $student,
                'histories' => $histories,
            ]
        );
    }
}
