<?php

namespace App\Livewire\Reports;

use App\Exports\ReportViewExport;
use App\Models\AcademicYear;
use App\Models\AssessmentConfig;
use App\Models\Classroom;
use App\Models\Semester;
use App\Services\AssessmentService;
use App\Services\GradeReportService;
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

    public ?int $closureId = null;

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

        $this->selectDefaultReportSource();
    }

    /*
    |--------------------------------------------------------------------------
    | Tahun Ajaran Berubah
    |--------------------------------------------------------------------------
    */

    public function updatedAcademicYearId(): void
    {
        $this->semesterId =
            null;

        $this->classroomId =
            null;

        $this->closureId =
            null;

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

        $this->selectDefaultReportSource();
    }

    /*
    |--------------------------------------------------------------------------
    | Data Laporan
    |--------------------------------------------------------------------------
    */

    protected function getReportData(): array
    {
        return app(
            GradeReportService::class
        )->getData(
            academicYearId: $this->academicYearId,

            semesterId: $this->semesterId,

            classroomId: $this->classroomId,

            search: $this->search,

            closureId: $this->closureId
        );
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
            'attendance' => $assessmentService
                ->attendanceSyncStatus(
                    $selectedConfig
                ),

            'final' => $assessmentService
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
        array $data
    ): void {
        app(
            GradeReportService::class
        )
            ->assertExportable(
                $data
            );
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
                'export' => 'Konfigurasi penilaian aktif tidak ditemukan.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Jangan export nilai stale
        |--------------------------------------------------------------------------
        */

        $this->ensureReportCanBeExported(
            $data
        );

        $filename =
            $this->exportFilename(
                'rekap-nilai'
            )
            .'.csv';

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

                fputcsv(
                    $handle,
                    [
                        'Sumber Data',
                        (
                            $data[
                                'reportSource'
                            ]
                            ?? 'live'
                        ) === 'snapshot'
                            ? 'Snapshot Resmi'
                            : 'Data Berjalan',
                    ],
                    ';'
                );

                if (
                    (
                        $data[
                            'reportSource'
                        ]
                        ?? 'live'
                    ) === 'snapshot'
                    &&
                    $data[
                        'selectedClosure'
                    ]
                ) {
                    $closure =
                        $data[
                            'selectedClosure'
                        ];

                    fputcsv(
                        $handle,
                        [
                            'Versi Snapshot',
                            'v'
                            .$closure->version,
                        ],
                        ';'
                    );

                    fputcsv(
                        $handle,
                        [
                            'Dikunci Pada',
                            $closure
                                ->locked_at
                                ?->format(
                                    'd/m/Y H:i:s'
                                ),
                        ],
                        ';'
                    );

                    fputcsv(
                        $handle,
                        [
                            'Snapshot Checksum',
                            $closure
                                ->snapshot_checksum,
                        ],
                        ';'
                    );
                }

                fputcsv(
                    $handle,
                    [],
                    ';'
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
                    ]->items as $item
                ) {
                    $header[] =
                        $item
                            ->factor
                            ->name
                        .' ('
                        .number_format(
                            (float) $item
                                ->weight,
                            2
                        )
                        .'%)';
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
                    ] as $student
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
                        ]->items as $item
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
                'Content-Type' => 'text/csv; charset=UTF-8',
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
                'export' => 'Belum ada konfigurasi penilaian aktif '
                    .'untuk periode yang dipilih.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Pastikan nilai sudah sinkron
        |--------------------------------------------------------------------------
        */

        $this->ensureReportCanBeExported(
            $data
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
                'school' => $school,

                'academicYear' => $this->academicYearId
                        ? AcademicYear::query()
                            ->find(
                                $this->academicYearId
                            )
                        : null,

                'semester' => $this->semesterId
                        ? Semester::query()
                            ->find(
                                $this->semesterId
                            )
                        : null,

                'classroom' => $this->classroomId
                        ? Classroom::query()
                            ->find(
                                $this->classroomId
                            )
                        : null,

                'reportGeneratedAt' => now(),

                'reportSourceLabel' => (
                    $data[
                        'reportSource'
                    ]
                    ?? 'live'
                ) === 'snapshot'
                        ? 'Snapshot Resmi'
                        : 'Data Berjalan',
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
                viewName: 'exports.reports.grades',

                viewData: $data,

                sheetTitle: 'Rekap Nilai'
            ),
            $this->exportFilename(
                'rekap-nilai'
            )
            .'.xlsx'
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
                    fn ($query) => $query->where(
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
            (
                $data[
                    'reportSource'
                ]
                ?? 'live'
            ) === 'live'
                ? $this->getSyncStatus(
                    $data[
                        'selectedConfig'
                    ]
                )
                : null;

        /*
        |--------------------------------------------------------------------------
        | View
        |--------------------------------------------------------------------------
        */

        $closures =
            app(
                GradeReportService::class
            )
                ->closures(
                    $this->academicYearId,
                    $this->semesterId
                );

        return view(
            'livewire.reports.grades',
            array_merge(
                [
                    'academicYears' => $academicYears,

                    'semesters' => $semesters,

                    'classrooms' => $classrooms,

                    'closures' => $closures,

                    'syncStatus' => $syncStatus,
                ],
                $data
            )
        );
    }

    public function updatedSemesterId(): void
    {
        $this->classroomId =
            null;

        $this->closureId =
            null;

        $this->selectDefaultReportSource();
    }

    protected function selectDefaultReportSource(): void
    {
        if (
            ! $this->academicYearId
            ||
            ! $this->semesterId
        ) {
            $this->closureId =
                null;

            return;
        }

        $closure =
            app(
                GradeReportService::class
            )
                ->defaultClosure(
                    $this->academicYearId,
                    $this->semesterId
                );

        $this->closureId =
            $closure?->id;
    }
}
