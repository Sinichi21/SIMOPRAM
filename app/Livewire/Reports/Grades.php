<?php

namespace App\Livewire\Reports;

use App\Exports\ReportViewExport;
use App\Models\AcademicYear;
use App\Models\AssessmentConfig;
use App\Models\Classroom;
use App\Models\FinalGrade;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentScore;
use App\Services\AssessmentService;
use App\Support\SchoolContext;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class Grades extends Component
{
    public ?int $academicYearId = null;

    public ?int $semesterId = null;

    public ?int $classroomId = null;

    public string $search = '';


    /*
    |--------------------------------------------------------------------------
    | Mount
    |--------------------------------------------------------------------------
    */

    public function mount(): void
    {
        $year =
            AcademicYear::query()
                ->where(
                    'is_active',
                    true
                )
                ->first();


        $this->academicYearId =
            $year?->id;


        if (! $this->academicYearId) {
            return;
        }


        $semester =
            Semester::query()
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


    /*
    |--------------------------------------------------------------------------
    | Tahun Ajaran Berubah
    |--------------------------------------------------------------------------
    */

    public function updatedAcademicYearId(): void
    {
        $this->semesterId = null;

        $this->classroomId = null;


        if (! $this->academicYearId) {
            return;
        }


        $semester =
            Semester::query()
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


    /*
    |--------------------------------------------------------------------------
    | Data Laporan
    |--------------------------------------------------------------------------
    */

    protected function getReportData(): array
    {
        $selectedConfig = null;


        /*
        |--------------------------------------------------------------------------
        | Konfigurasi Penilaian
        |--------------------------------------------------------------------------
        */

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


        /*
        |--------------------------------------------------------------------------
        | Tidak Ada Konfigurasi
        |--------------------------------------------------------------------------
        */

        if (! $selectedConfig) {
            return [
                'selectedConfig' =>
                    null,

                'students' =>
                    collect(),

                'scores' =>
                    collect(),

                'finalGrades' =>
                    collect(),
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Siswa
        |--------------------------------------------------------------------------
        */

        $students =
            Student::query()
                ->with([
                    'enrollments' =>
                        function ($query) use (
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
                    trim(
                        $this->search
                    ) !== '',
                    function ($query): void {
                        $search =
                            '%'
                            . trim(
                                $this->search
                            )
                            . '%';


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
                ->orderBy(
                    'name'
                )
                ->get();


        $studentIds =
            $students->pluck(
                'id'
            );


        /*
        |--------------------------------------------------------------------------
        | Nilai Faktor
        |--------------------------------------------------------------------------
        */

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
                ->groupBy(
                    'student_id'
                )
                ->map(
                    fn ($items) =>
                        $items->keyBy(
                            'assessment_factor_id'
                        )
                );


        /*
        |--------------------------------------------------------------------------
        | Nilai Akhir
        |--------------------------------------------------------------------------
        */

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
                ->keyBy(
                    'student_id'
                );


        return [
            'selectedConfig' =>
                $selectedConfig,

            'students' =>
                $students,

            'scores' =>
                $scores,

            'finalGrades' =>
                $finalGrades,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Status Sinkronisasi
    |--------------------------------------------------------------------------
    */

    protected function getSyncStatus(
        ?AssessmentConfig $selectedConfig
    ): ?array {
        if (! $selectedConfig) {
            return null;
        }


        $assessmentService =
            app(
                AssessmentService::class
            );


        return [
            'attendance' =>
                $assessmentService
                    ->attendanceSyncStatus(
                        $selectedConfig
                    ),

            'final' =>
                $assessmentService
                    ->finalGradeSyncStatus(
                        $selectedConfig
                    ),
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Apakah Laporan Sinkron?
    |--------------------------------------------------------------------------
    */

    protected function reportIsSynchronized(
        AssessmentConfig $selectedConfig
    ): bool {
        $syncStatus =
            $this->getSyncStatus(
                $selectedConfig
            );


        if (! $syncStatus) {
            return false;
        }


        return ! (
            $syncStatus[
                'attendance'
            ][
                'is_stale'
            ]
            ||
            $syncStatus[
                'final'
            ][
                'is_stale'
            ]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Validasi Sebelum Export
    |--------------------------------------------------------------------------
    */

    protected function ensureReportCanBeExported(
        AssessmentConfig $selectedConfig
    ): void {
        if (
            ! $this->reportIsSynchronized(
                $selectedConfig
            )
        ) {
            throw ValidationException::withMessages([
                'export' =>
                    'Nilai belum sinkron. '
                    . 'Sinkronkan nilai kehadiran dan nilai akhir '
                    . 'sebelum melakukan export laporan.',
            ]);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Export CSV
    |--------------------------------------------------------------------------
    */

    public function exportCsv()
    {
        abort_unless(
            auth()
                ->user()
                ->can(
                    'reports.export'
                ),
            403
        );


        $data =
            $this->getReportData();


        $config =
            $data[
                'selectedConfig'
            ];


        if (! $config) {
            throw ValidationException::withMessages([
                'export' =>
                    'Konfigurasi penilaian aktif tidak ditemukan.',
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Jangan export nilai stale
        |--------------------------------------------------------------------------
        */

        $this->ensureReportCanBeExported(
            $config
        );


        $filename =
            $this->exportFilename(
                'rekap-nilai'
            )
            . '.csv';


        return response()->streamDownload(
            function () use (
                $data
            ): void {
                $handle =
                    fopen(
                        'php://output',
                        'w'
                    );


                if (! $handle) {
                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | UTF-8 BOM agar nyaman dibuka di Excel
                |--------------------------------------------------------------------------
                */

                fwrite(
                    $handle,
                    "\xEF\xBB\xBF"
                );


                /*
                |--------------------------------------------------------------------------
                | Header
                |--------------------------------------------------------------------------
                */

                $header = [
                    'NIS',
                    'Nama Siswa',
                    'Kelas',
                ];


                foreach (
                    $data[
                        'selectedConfig'
                    ]->items
                    as $item
                ) {
                    $header[] =
                        $item
                            ->factor
                            ->name
                        . ' ('
                        . number_format(
                            (float) $item
                                ->weight,
                            2
                        )
                        . '%)';
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


                /*
                |--------------------------------------------------------------------------
                | Isi
                |--------------------------------------------------------------------------
                */

                foreach (
                    $data[
                        'students'
                    ]
                    as $student
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
                            ?->name
                            ?? '-',
                    ];


                    foreach (
                        $data[
                            'selectedConfig'
                        ]->items
                        as $item
                    ) {
                        $score =
                            $data[
                                'scores'
                            ]
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
                                    (float) $score
                                        ->score,
                                    2,
                                    '.',
                                    ''
                                )
                                : '';
                    }


                    $final =
                        $data[
                            'finalGrades'
                        ]
                            ->get(
                                $student->id
                            );


                    $row[] =
                        $final
                            ? number_format(
                                (float) $final
                                    ->final_score,
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


                fclose(
                    $handle
                );
            },
            $filename,
            [
                'Content-Type' =>
                    'text/csv; charset=UTF-8',
            ]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Data Export
    |--------------------------------------------------------------------------
    */

    protected function exportViewData(): array
    {
        $data =
            $this->getReportData();


        if (
            ! $data[
                'selectedConfig'
            ]
        ) {
            throw ValidationException::withMessages([
                'export' =>
                    'Belum ada konfigurasi penilaian aktif '
                    . 'untuk periode yang dipilih.',
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Pastikan nilai sudah sinkron
        |--------------------------------------------------------------------------
        */

        $this->ensureReportCanBeExported(
            $data[
                'selectedConfig'
            ]
        );


        /*
        |--------------------------------------------------------------------------
        | Sekolah Aktif
        |--------------------------------------------------------------------------
        */

        $school =
            app(
                SchoolContext::class
            )->school();


        abort_unless(
            $school,
            409,
            'Pilih sekolah aktif terlebih dahulu.'
        );


        return array_merge(
            $data,
            [
                'school' =>
                    $school,

                'academicYear' =>
                    $this->academicYearId
                        ? AcademicYear::query()
                            ->find(
                                $this->academicYearId
                            )
                        : null,

                'semester' =>
                    $this->semesterId
                        ? Semester::query()
                            ->find(
                                $this->semesterId
                            )
                        : null,

                'classroom' =>
                    $this->classroomId
                        ? Classroom::query()
                            ->find(
                                $this->classroomId
                            )
                        : null,
            ]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Nama File Export
    |--------------------------------------------------------------------------
    */

    protected function exportFilename(
        string $prefix
    ): string {
        $school =
            app(
                SchoolContext::class
            )->school();


        return implode(
            '-',
            array_filter([
                $prefix,

                Str::slug(
                    $school?->name
                    ?? 'sekolah'
                ),

                now()->format(
                    'Ymd-His'
                ),
            ])
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Export Excel
    |--------------------------------------------------------------------------
    */

    public function exportExcel()
    {
        abort_unless(
            auth()
                ->user()
                ->can(
                    'reports.export'
                ),
            403
        );

        $assessmentService =
            app(
                AssessmentService::class
            );


        $attendanceStatus =
            $assessmentService
                ->attendanceSyncStatus(
                    $data['selectedConfig']
                );


        $finalStatus =
            $assessmentService
                ->finalGradeSyncStatus(
                    $data['selectedConfig']
                );


        if (
            $attendanceStatus['is_stale']
            ||
            $finalStatus['is_stale']
        ) {
            $this->addError(
                'export',
                'Nilai belum sinkron. Sinkronkan nilai sebelum melakukan export.'
            );

            return null;
        }

        $data =
            $this->exportViewData();


        return Excel::download(
            new ReportViewExport(
                viewName:
                    'exports.reports.grades',

                viewData:
                    $data,

                sheetTitle:
                    'Rekap Nilai'
            ),
            $this->exportFilename(
                'rekap-nilai'
            )
            . '.xlsx'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Render
    |--------------------------------------------------------------------------
    */

    public function render()
    {
        /*
        |--------------------------------------------------------------------------
        | Tahun Ajaran
        |--------------------------------------------------------------------------
        */

        $academicYears =
            AcademicYear::query()
                ->orderByDesc(
                    'start_date'
                )
                ->get();


        /*
        |--------------------------------------------------------------------------
        | Semester
        |--------------------------------------------------------------------------
        */

        $semesters =
            Semester::query()
                ->when(
                    $this->academicYearId,
                    fn ($query) =>
                        $query->where(
                            'academic_year_id',
                            $this->academicYearId
                        )
                )
                ->orderBy(
                    'semester_number'
                )
                ->get();


        /*
        |--------------------------------------------------------------------------
        | Kelas
        |--------------------------------------------------------------------------
        */

        $classrooms =
            Classroom::query()
                ->orderBy(
                    'name'
                )
                ->get();


        /*
        |--------------------------------------------------------------------------
        | Laporan
        |--------------------------------------------------------------------------
        */

        $data =
            $this->getReportData();


        /*
        |--------------------------------------------------------------------------
        | Status Sinkronisasi
        |--------------------------------------------------------------------------
        */

        $syncStatus =
            $this->getSyncStatus(
                $data[
                    'selectedConfig'
                ]
            );


        /*
        |--------------------------------------------------------------------------
        | View
        |--------------------------------------------------------------------------
        */

        return view(
            'livewire.reports.grades',
            array_merge(
                [
                    'academicYears' =>
                        $academicYears,

                    'semesters' =>
                        $semesters,

                    'classrooms' =>
                        $classrooms,

                    'syncStatus' =>
                        $syncStatus,
                ],
                $data
            )
        );
    }
}