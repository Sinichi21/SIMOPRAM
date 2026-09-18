<?php

namespace App\Livewire\Student;

use App\Models\AcademicYear;
use App\Models\Semester;
use App\Models\Student;
use App\Services\GradeReportService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class MyGrades extends Component
{
    public function render(
        GradeReportService $gradeReportService
    ): View {
        /*
        |--------------------------------------------------------------------------
        | Keamanan role
        |--------------------------------------------------------------------------
        */

        $user = auth()->user();

        abort_unless(
            $user?->hasRole('student'),
            403
        );

        /*
        |--------------------------------------------------------------------------
        | Data siswa milik user login
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
                'livewire.student.my-grades',
                $this->emptyData()
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Tahun ajaran aktif
        |--------------------------------------------------------------------------
        */

        $academicYear = AcademicYear::query()
            ->where(
                'is_active',
                true
            )
            ->first();

        if (! $academicYear) {
            return view(
                'livewire.student.my-grades',
                array_merge(
                    $this->emptyData(),
                    [
                        'student' => $student,
                    ]
                )
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Semester aktif
        |--------------------------------------------------------------------------
        */

        $semester = Semester::query()
            ->where(
                'academic_year_id',
                $academicYear->id
            )
            ->where(
                'is_active',
                true
            )
            ->first();

        if (! $semester) {
            return view(
                'livewire.student.my-grades',
                array_merge(
                    $this->emptyData(),
                    [
                        'student' => $student,
                        'academicYear' => $academicYear,
                    ]
                )
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Enrollment / kelas siswa
        |--------------------------------------------------------------------------
        */

        $enrollment = $student
            ->enrollments()
            ->where(
                'academic_year_id',
                $academicYear->id
            )
            ->where(
                'status',
                'active'
            )
            ->with('classroom')
            ->latest('id')
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Ambil data melalui GradeReportService
        |--------------------------------------------------------------------------
        |
        | Penting:
        |
        | Semester terbuka:
        | StudentScore + FinalGrade
        |
        | Semester terkunci:
        | SemesterGradeSnapshot
        |
        */

        $data = $gradeReportService->getData(
            $academicYear->id,
            $semester->id,
            $enrollment?->classroom_id,
            filled($student->nis)
                ? $student->nis
                : $student->name
        );

        /*
        |--------------------------------------------------------------------------
        | Pastikan hanya siswa login yang diambil
        |--------------------------------------------------------------------------
        */

        $studentRow = $data['students']
            ->first(
                function ($candidate) use (
                    $student
                ): bool {
                    /*
                    | Live data menggunakan student ID asli.
                    */
                    if (
                        (int) $candidate->id
                        ===
                        (int) $student->id
                    ) {
                        return true;
                    }

                    /*
                    | Snapshot tetap dapat ditemukan
                    | menggunakan NIS apabila diperlukan.
                    */
                    return filled($student->nis)
                        &&
                        (string) $candidate->nis
                        ===
                        (string) $student->nis;
                }
            );

        /*
        |--------------------------------------------------------------------------
        | Tidak ada nilai siswa pada konfigurasi tersebut
        |--------------------------------------------------------------------------
        */

        if (! $studentRow) {
            return view(
                'livewire.student.my-grades',
                [
                    'student' => $student,
                    'academicYear' => $academicYear,

                    'semester' => $semester,

                    'enrollment' => $enrollment,

                    'selectedConfig' => $data[
                            'selectedConfig'
                        ],

                    'scores' => collect(),

                    'finalGrade' => null,

                    'reportSource' => $data[
                            'reportSource'
                        ] ?? null,

                    'selectedClosure' => $data[
                            'selectedClosure'
                        ] ?? null,

                    'isOfficialSnapshot' => $data[
                            'isOfficialSnapshot'
                        ] ?? false,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Nilai per faktor
        |--------------------------------------------------------------------------
        */

        $scores = $data['scores']
            ->get(
                $studentRow->id,
                collect()
            );

        /*
        |--------------------------------------------------------------------------
        | Nilai akhir
        |--------------------------------------------------------------------------
        */

        $finalGrade = $data[
            'finalGrades'
        ]->get(
            $studentRow->id
        );

        return view(
            'livewire.student.my-grades',
            [
                'student' => $student,

                'academicYear' => $academicYear,

                'semester' => $semester,

                'enrollment' => $enrollment,

                'selectedConfig' => $data[
                        'selectedConfig'
                    ],

                'scores' => $scores,

                'finalGrade' => $finalGrade,

                'reportSource' => $data[
                        'reportSource'
                    ] ?? null,

                'selectedClosure' => $data[
                        'selectedClosure'
                    ] ?? null,

                'isOfficialSnapshot' => $data[
                        'isOfficialSnapshot'
                    ] ?? false,
            ]
        );
    }

    private function emptyData(): array
    {
        return [
            'student' => null,

            'academicYear' => null,

            'semester' => null,

            'enrollment' => null,

            'selectedConfig' => null,

            'scores' => collect(),

            'finalGrade' => null,

            'reportSource' => null,

            'selectedClosure' => null,

            'isOfficialSnapshot' => false,
        ];
    }
}
