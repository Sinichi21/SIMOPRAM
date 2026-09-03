<?php

namespace App\Services;

use App\Models\AssessmentConfig;
use App\Models\FinalGrade;
use App\Models\SemesterClosure;
use App\Models\Student;
use App\Models\StudentScore;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class GradeReportService
{
    /*
    |--------------------------------------------------------------------------
    | Riwayat Snapshot Semester
    |--------------------------------------------------------------------------
    */

    public function closures(
        ?int $academicYearId,
        ?int $semesterId
    ): Collection {
        if (
            ! $academicYearId
            ||
            ! $semesterId
        ) {
            return collect();
        }

        return SemesterClosure::query()
            ->with([
                'assessmentConfig.items.factor',
                'academicYear',
                'semester',
                'locker',
                'reopener',
            ])
            ->where(
                'academic_year_id',
                $academicYearId
            )
            ->where(
                'semester_id',
                $semesterId
            )
            ->orderByDesc(
                'version'
            )
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Snapshot Default
    |--------------------------------------------------------------------------
    |
    | Bila versi semester terbaru masih berstatus locked, laporan resmi secara
    | otomatis memakai snapshot tersebut. Bila versi terbaru sudah reopened,
    | laporan kembali menggunakan data berjalan.
    |--------------------------------------------------------------------------
    */

    public function defaultClosure(
        ?int $academicYearId,
        ?int $semesterId
    ): ?SemesterClosure {
        $latest =
            $this->closures(
                $academicYearId,
                $semesterId
            )->first();

        if (
            $latest
            &&
            $latest->isLocked()
        ) {
            return $latest;
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | Resolve Snapshot yang Dipilih
    |--------------------------------------------------------------------------
    |
    | Query tetap melalui model SemesterClosure yang menggunakan BelongsToSchool,
    | sehingga closure tenant lain tidak dapat diakses melalui manipulasi ID.
    |--------------------------------------------------------------------------
    */

    public function resolveClosure(
        ?int $academicYearId,
        ?int $semesterId,
        ?int $closureId
    ): ?SemesterClosure {
        if (
            ! $academicYearId
            ||
            ! $semesterId
            ||
            ! $closureId
        ) {
            return null;
        }

        return SemesterClosure::query()
            ->with([
                'assessmentConfig.items.factor',
                'academicYear',
                'semester',
            ])
            ->where(
                'academic_year_id',
                $academicYearId
            )
            ->where(
                'semester_id',
                $semesterId
            )
            ->findOrFail(
                $closureId
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Sumber Data Laporan
    |--------------------------------------------------------------------------
    |
    | Semester terbuka:
    |   StudentScore + FinalGrade
    |
    | Semester terkunci atau closure dipilih:
    |   SemesterGradeSnapshot
    |--------------------------------------------------------------------------
    */

    public function getData(
        ?int $academicYearId,
        ?int $semesterId,
        ?int $classroomId = null,
        string $search = '',
        ?int $closureId = null
    ): array {
        if (
            ! $academicYearId
            ||
            ! $semesterId
        ) {
            return $this->emptyData();
        }

        $closure =
            $this->resolveClosure(
                $academicYearId,
                $semesterId,
                $closureId
            );

        /*
        |--------------------------------------------------------------------------
        | Bila user tidak memilih closure tertentu, semester locked otomatis
        | diarahkan ke snapshot resmi terbaru.
        |--------------------------------------------------------------------------
        */

        if (! $closure) {
            $closure =
                $this->defaultClosure(
                    $academicYearId,
                    $semesterId
                );
        }

        if ($closure) {
            return $this->snapshotData(
                $closure,
                $classroomId,
                $search
            );
        }

        return $this->liveData(
            $academicYearId,
            $semesterId,
            $classroomId,
            $search
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Validasi Export
    |--------------------------------------------------------------------------
    |
    | Snapshot resmi tidak perlu diperiksa terhadap data live karena memang
    | merupakan arsip frozen. Data berjalan hanya dapat diekspor bila seluruh
    | sumber nilai dan FinalGrade sudah sinkron.
    |--------------------------------------------------------------------------
    */

    public function assertExportable(
        array $data
    ): void {
        if (
            $data[
                'isOfficialSnapshot'
            ]
            ?? false
        ) {
            return;
        }

        $config =
            $data[
                'selectedConfig'
            ]
            ?? null;

        if (! $config) {
            throw ValidationException::withMessages([
                'export' =>
                    'Konfigurasi penilaian aktif tidak ditemukan.',
            ]);
        }

        $assessmentService =
            app(
                AssessmentService::class
            );

        $attendance =
            $assessmentService
                ->attendanceSyncStatus(
                    $config
                );

        $final =
            $assessmentService
                ->finalGradeSyncStatus(
                    $config
                );

        if (
            (
                $attendance[
                    'is_stale'
                ]
                ?? false
            )
            ||
            (
                $final[
                    'is_stale'
                ]
                ?? false
            )
        ) {
            throw ValidationException::withMessages([
                'export' =>
                    'Data penilaian belum sinkron. '
                    . 'Lakukan Sinkronisasi Penilaian '
                    . 'sebelum mengekspor laporan.',
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Data Berjalan
    |--------------------------------------------------------------------------
    */

    protected function liveData(
        int $academicYearId,
        int $semesterId,
        ?int $classroomId,
        string $search
    ): array {
        $config =
            AssessmentConfig::query()
                ->with([
                    'items' =>
                        function ($query): void {
                            $query->orderBy(
                                'sort_order'
                            );
                        },
                    'items.factor',
                    'academicYear',
                    'semester',
                ])
                ->where(
                    'academic_year_id',
                    $academicYearId
                )
                ->where(
                    'semester_id',
                    $semesterId
                )
                ->where(
                    'is_active',
                    true
                )
                ->first();

        if (! $config) {
            return $this->emptyData();
        }

        $students =
            Student::query()
                ->with([
                    'enrollments' =>
                        function ($query) use (
                            $config
                        ): void {
                            $query
                                ->where(
                                    'academic_year_id',
                                    $config
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
                        $config,
                        $classroomId
                    ): void {
                        $query->where(
                            'academic_year_id',
                            $config
                                ->academic_year_id
                        );

                        if ($classroomId) {
                            $query->where(
                                'classroom_id',
                                $classroomId
                            );
                        }
                    }
                )
                ->when(
                    trim(
                        $search
                    ) !== '',
                    function ($query) use (
                        $search
                    ): void {
                        $term =
                            '%'
                            . trim(
                                $search
                            )
                            . '%';

                        $query->where(
                            function ($query) use (
                                $term
                            ): void {
                                $query
                                    ->where(
                                        'name',
                                        'like',
                                        $term
                                    )
                                    ->orWhere(
                                        'nis',
                                        'like',
                                        $term
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
        | Bentuk collection dipertahankan kompatibel dengan Grades.php:
        |
        | $scores
        |   ->get($student->id, collect())
        |   ->get($item->assessment_factor_id)
        |--------------------------------------------------------------------------
        */

        $scores =
            StudentScore::query()
                ->where(
                    'assessment_config_id',
                    $config->id
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

        $finalGrades =
            FinalGrade::query()
                ->where(
                    'assessment_config_id',
                    $config->id
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
                $config,

            'students' =>
                $students,

            'scores' =>
                $scores,

            'finalGrades' =>
                $finalGrades,

            'reportSource' =>
                'live',

            'selectedClosure' =>
                null,

            'isOfficialSnapshot' =>
                false,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Data Snapshot Resmi
    |--------------------------------------------------------------------------
    */

    protected function snapshotData(
        SemesterClosure $closure,
        ?int $classroomId,
        string $search
    ): array {
        $closure->loadMissing([
            'assessmentConfig.items.factor',
            'academicYear',
            'semester',
        ]);

        $config =
            $closure->assessmentConfig;

        if (! $config) {
            throw ValidationException::withMessages([
                'report' =>
                    'Konfigurasi penilaian pada snapshot tidak ditemukan.',
            ]);
        }

        $query =
            $closure
                ->snapshots()
                ->when(
                    $classroomId,
                    fn ($query) =>
                        $query->where(
                            'classroom_id',
                            $classroomId
                        )
                )
                ->when(
                    trim(
                        $search
                    ) !== '',
                    function ($query) use (
                        $search
                    ): void {
                        $term =
                            '%'
                            . trim(
                                $search
                            )
                            . '%';

                        $query->where(
                            function ($query) use (
                                $term
                            ): void {
                                $query
                                    ->where(
                                        'student_name',
                                        'like',
                                        $term
                                    )
                                    ->orWhere(
                                        'student_nis',
                                        'like',
                                        $term
                                    );
                            }
                        );
                    }
                )
                ->orderBy(
                    'student_name'
                );

        $snapshots =
            $query->get();

        /*
        |--------------------------------------------------------------------------
        | Jangan menggunakan bobot konfigurasi live untuk laporan historis.
        |
        | selectedConfig versi snapshot dibangun dari metadata closure agar bila
        | bobot faktor diubah setelah semester dibuka kembali, laporan v1 tetap
        | menampilkan bobot yang berlaku ketika v1 dikunci.
        |--------------------------------------------------------------------------
        */

        $snapshotConfig =
            $this->buildSnapshotConfig(
                $closure,
                $config,
                $snapshots
            );

        $students =
            collect();

        $scores =
            collect();

        $finalGrades =
            collect();

        foreach (
            $snapshots
            as $snapshot
        ) {
            /*
            |--------------------------------------------------------------------------
            | student_id dapat NULL bila master siswa sudah dihapus.
            | Negative snapshot ID digunakan sebagai key internal fallback.
            |--------------------------------------------------------------------------
            */

            $studentKey =
                $snapshot->student_id
                    ? (int) $snapshot
                        ->student_id
                    : -1
                        * (int) $snapshot
                            ->id;

            $classroom =
                (object) [
                    'id' =>
                        $snapshot
                            ->classroom_id,

                    'name' =>
                        $snapshot
                            ->classroom_name,
                ];

            $enrollment =
                (object) [
                    'classroom_id' =>
                        $snapshot
                            ->classroom_id,

                    'classroom' =>
                        $classroom,
                ];

            $student =
                (object) [
                    'id' =>
                        $studentKey,

                    'nis' =>
                        $snapshot
                            ->student_nis,

                    'name' =>
                        $snapshot
                            ->student_name,

                    'enrollments' =>
                        collect([
                            $enrollment,
                        ]),
                ];

            $students->push(
                $student
            );

            /*
            |--------------------------------------------------------------------------
            | Nilai faktor snapshot
            |--------------------------------------------------------------------------
            */

            $studentScores =
                collect();

            foreach (
                $snapshot->factor_scores
                ?? []
                as $factorScore
            ) {
                $factorId =
                    (int) (
                        $factorScore[
                            'assessment_factor_id'
                        ]
                        ?? 0
                    );

                if (
                    $factorId <= 0
                ) {
                    continue;
                }

                $studentScores->put(
                    $factorId,
                    (object) [
                        'assessment_factor_id' =>
                            $factorId,

                        'score' =>
                            $factorScore[
                                'score'
                            ]
                            ?? null,

                        'source' =>
                            'snapshot',
                    ]
                );
            }

            $scores->put(
                $studentKey,
                $studentScores
            );

            /*
            |--------------------------------------------------------------------------
            | Nilai akhir snapshot
            |--------------------------------------------------------------------------
            */

            $finalGrades->put(
                $studentKey,
                (object) [
                    'student_id' =>
                        $studentKey,

                    'final_score' =>
                        $snapshot
                            ->final_score,

                    'letter_grade' =>
                        $snapshot
                            ->letter_grade,

                    'description' =>
                        $snapshot
                            ->description,

                    'calculated_at' =>
                        $snapshot
                            ->source_calculated_at,
                ]
            );
        }

        return [
            'selectedConfig' =>
                $snapshotConfig,

            'students' =>
                $students,

            'scores' =>
                $scores,

            'finalGrades' =>
                $finalGrades,

            'reportSource' =>
                'snapshot',

            'selectedClosure' =>
                $closure,

            'isOfficialSnapshot' =>
                true,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Konfigurasi Faktor Versi Snapshot
    |--------------------------------------------------------------------------
    */

    protected function buildSnapshotConfig(
        SemesterClosure $closure,
        AssessmentConfig $config,
        Collection $snapshots
    ): AssessmentConfig {
        $definitions =
            data_get(
                $closure->metadata,
                'factors',
                []
            );

        /*
        |--------------------------------------------------------------------------
        | Fallback untuk snapshot lama yang belum memiliki metadata factors.
        |--------------------------------------------------------------------------
        */

        if (
            ! is_array(
                $definitions
            )
            ||
            count(
                $definitions
            ) === 0
        ) {
            $definitions =
                $snapshots
                    ->first()
                    ?->factor_scores
                ?? [];
        }

        $items =
            collect(
                $definitions
            )
                ->map(
                    function (
                        array $definition,
                        int $index
                    ): object {
                        $factorId =
                            (int) (
                                $definition[
                                    'assessment_factor_id'
                                ]
                                ?? 0
                            );

                        $factor =
                            (object) [
                                'id' =>
                                    $factorId,

                                'name' =>
                                    $definition[
                                        'name'
                                    ]
                                    ?? (
                                        'Faktor '
                                        . $factorId
                                    ),

                                'source_type' =>
                                    $definition[
                                        'source_type'
                                    ]
                                    ?? null,
                            ];

                        return (object) [
                            'assessment_factor_id' =>
                                $factorId,

                            'weight' =>
                                (float) (
                                    $definition[
                                        'weight'
                                    ]
                                    ?? 0
                                ),

                            'sort_order' =>
                                (int) (
                                    $definition[
                                        'sort_order'
                                    ]
                                    ?? (
                                        $index
                                        + 1
                                    )
                                ),

                            'factor' =>
                                $factor,
                        ];
                    }
                )
                ->filter(
                    fn ($item): bool =>
                        $item
                            ->assessment_factor_id
                        > 0
                )
                ->sortBy(
                    'sort_order'
                )
                ->values();

        $snapshotConfig =
            clone $config;

        $snapshotConfig->setRelation(
            'items',
            $items
        );

        return $snapshotConfig;
    }

    /*
    |--------------------------------------------------------------------------
    | Empty Data
    |--------------------------------------------------------------------------
    */

    protected function emptyData(): array
    {
        return [
            'selectedConfig' =>
                null,

            'students' =>
                collect(),

            'scores' =>
                collect(),

            'finalGrades' =>
                collect(),

            'reportSource' =>
                'live',

            'selectedClosure' =>
                null,

            'isOfficialSnapshot' =>
                false,
        ];
    }
}
