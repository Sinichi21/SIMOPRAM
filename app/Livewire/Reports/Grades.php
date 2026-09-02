<?php

namespace App\Livewire\Reports;

use App\Models\AcademicYear;
use App\Models\AssessmentConfig;
use App\Models\Classroom;
use App\Models\FinalGrade;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentScore;
use Livewire\Component;

class Grades extends Component
{
    public ?int $academicYearId = null;

    public ?int $semesterId = null;

    public ?int $classroomId = null;

    public string $search = '';

    public function mount(): void
    {
        $year = AcademicYear::query()
            ->where('is_active', true)
            ->first();

        $this->academicYearId =
            $year?->id;

        if ($this->academicYearId) {
            $semester = Semester::query()
                ->where(
                    'academic_year_id',
                    $this->academicYearId
                )
                ->where(
                    'is_active',
                    true
                )
                ->first();

            $this->semesterId =
                $semester?->id;
        }
    }

    public function updatedAcademicYearId(): void
    {
        $this->semesterId = null;
        $this->classroomId = null;

        if (! $this->academicYearId) {
            return;
        }

        $semester = Semester::query()
            ->where(
                'academic_year_id',
                $this->academicYearId
            )
            ->where(
                'is_active',
                true
            )
            ->first();

        $this->semesterId =
            $semester?->id;
    }

    protected function getReportData(): array
    {
        $selectedConfig = null;

        if (
            $this->academicYearId
            &&
            $this->semesterId
        ) {
            $selectedConfig =
                AssessmentConfig::query()
                    ->with([
                        'items.factor',
                        'academicYear',
                        'semester',
                    ])
                    ->where(
                        'academic_year_id',
                        $this->academicYearId
                    )
                    ->where(
                        'semester_id',
                        $this->semesterId
                    )
                    ->where(
                        'is_active',
                        true
                    )
                    ->first();
        }

        if (! $selectedConfig) {
            return [
                'selectedConfig' => null,
                'students' => collect(),
                'scores' => collect(),
                'finalGrades' => collect(),
            ];
        }

        $students =
            Student::query()
                ->with([
                    'enrollments' => function ($query) use (
                        $selectedConfig
                    ): void {
                        $query
                            ->where(
                                'academic_year_id',
                                $selectedConfig
                                    ->academic_year_id
                            )
                            ->with(
                                'classroom'
                            );
                    },
                ])
                ->where(
                    'status',
                    'active'
                )
                ->whereHas(
                    'enrollments',
                    function ($query) use (
                        $selectedConfig
                    ): void {
                        $query->where(
                            'academic_year_id',
                            $selectedConfig
                                ->academic_year_id
                        );

                        if ($this->classroomId) {
                            $query->where(
                                'classroom_id',
                                $this->classroomId
                            );
                        }
                    }
                )
                ->when(
                    trim($this->search) !== '',
                    function ($query): void {
                        $search =
                            '%'.
                            trim($this->search).
                            '%';

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
                )
                ->orderBy('name')
                ->get();

        $studentIds =
            $students->pluck('id');

        $scores =
            StudentScore::query()
                ->where(
                    'assessment_config_id',
                    $selectedConfig->id
                )
                ->whereIn(
                    'student_id',
                    $studentIds
                )
                ->get()
                ->groupBy('student_id')
                ->map(
                    fn ($items) => $items->keyBy(
                        'assessment_factor_id'
                    )
                );

        $finalGrades =
            FinalGrade::query()
                ->where(
                    'assessment_config_id',
                    $selectedConfig->id
                )
                ->whereIn(
                    'student_id',
                    $studentIds
                )
                ->get()
                ->keyBy('student_id');

        return compact(
            'selectedConfig',
            'students',
            'scores',
            'finalGrades'
        );
    }

    public function exportCsv()
    {
        abort_unless(
            auth()->user()->can(
                'reports.export'
            ),
            403
        );

        $data =
            $this->getReportData();

        $config =
            $data['selectedConfig'];

        abort_unless(
            $config,
            422,
            'Konfigurasi penilaian aktif tidak ditemukan.'
        );

        $filename =
            'rekap-nilai-'.
            now()->format('Y-m-d-His').
            '.csv';

        return response()->streamDownload(
            function () use ($data): void {
                $handle =
                    fopen(
                        'php://output',
                        'w'
                    );

                /*
                |--------------------------------------------------------------------------
                | UTF-8 BOM agar nyaman dibuka di Excel
                |--------------------------------------------------------------------------
                */
                fwrite(
                    $handle,
                    "\xEF\xBB\xBF"
                );

                $header = [
                    'NIS',
                    'Nama Siswa',
                    'Kelas',
                ];

                foreach (
                    $data['selectedConfig']
                        ->items as $item
                ) {
                    $header[] =
                        $item->factor->name.
                        ' ('.
                        number_format(
                            $item->weight,
                            2
                        ).
                        '%)';
                }

                $header[] =
                    'Nilai Akhir';

                $header[] =
                    'Predikat';

                $header[] =
                    'Deskripsi';

                fputcsv(
                    $handle,
                    $header,
                    ';'
                );

                foreach (
                    $data['students'] as $student
                ) {
                    $enrollment =
                        $student
                            ->enrollments
                            ->first();

                    $row = [
                        $student->nis,
                        $student->name,
                        $enrollment
                            ?->classroom
                            ?->name ?? '-',
                    ];

                    foreach (
                        $data['selectedConfig']
                            ->items as $item
                    ) {
                        $score =
                            $data['scores']
                                ->get(
                                    $student->id,
                                    collect()
                                )
                                ->get(
                                    $item
                                        ->assessment_factor_id
                                );

                        $row[] =
                            $score
                                ? number_format(
                                    (float) $score->score,
                                    2,
                                    '.',
                                    ''
                                )
                                : '';
                    }

                    $final =
                        $data['finalGrades']
                            ->get(
                                $student->id
                            );

                    $row[] =
                        $final
                            ? number_format(
                                (float)
                                $final->final_score,
                                2,
                                '.',
                                ''
                            )
                            : '';

                    $row[] =
                        $final
                            ?->letter_grade
                        ?? '';

                    $row[] =
                        $final
                            ?->description
                        ?? '';

                    fputcsv(
                        $handle,
                        $row,
                        ';'
                    );
                }

                fclose($handle);
            },
            $filename,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]
        );
    }

    public function render()
    {
        $academicYears =
            AcademicYear::query()
                ->orderByDesc(
                    'start_date'
                )
                ->get();

        $semesters =
            Semester::query()
                ->when(
                    $this->academicYearId,
                    fn ($query) => $query->where(
                        'academic_year_id',
                        $this->academicYearId
                    )
                )
                ->orderBy(
                    'semester_number'
                )
                ->get();

        $classrooms =
            Classroom::query()
                ->orderBy('name')
                ->get();

        $data =
            $this->getReportData();

        return view(
            'livewire.reports.grades',
            array_merge(
                compact(
                    'academicYears',
                    'semesters',
                    'classrooms'
                ),
                $data
            )
        );
    }
}
