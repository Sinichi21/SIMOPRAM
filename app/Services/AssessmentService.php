<?php

namespace App\Services;

use App\Models\AssessmentConfig;
use App\Models\FinalGrade;
use App\Models\GradeScaleConfig;
use App\Models\Student;
use App\Models\StudentScore;
use App\Support\SchoolContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssessmentService
{
    /*
    |--------------------------------------------------------------------------
    | Bobot status kehadiran
    |--------------------------------------------------------------------------
    */

    protected array $attendanceWeights;

    public function __construct()
    {
        $this->attendanceWeights = app(
            AttendanceWeightService::class
        )->factors();
    }

    public function attendanceScore(
        AssessmentConfig $config,
        Student $student
    ): float {
        /*
        |--------------------------------------------------------------------------
        | Denominator menggunakan sesi tempat siswa benar-benar menjadi peserta.
        |--------------------------------------------------------------------------
        */

        $participations =
            $student
                ->attendanceParticipations()
                ->whereHas(
                    'session',
                    fn ($query) => $query->active()
                )
                ->whereHas(
                    'session.activity',
                    function ($query) use (
                        $config
                    ): void {
                        $query->where(
                            'academic_year_id',
                            $config
                                ->academic_year_id
                        );

                        if (
                            $config
                                ->semester_id
                        ) {
                            $query->where(
                                'semester_id',
                                $config
                                    ->semester_id
                            );
                        }
                    }
                )
                ->with([
                    'session.attendances' => fn ($query) => $query->where(
                        'student_id',
                        $student->id
                    ),
                ])
                ->get();

        $totalSessions =
            $participations->count();

        if ($totalSessions === 0) {
            return 0;
        }

        $points = 0.0;

        foreach ($participations as $participation) {

            $attendance =
                $participation
                    ->session
                    ?->attendances
                    ?->first();

            if (! $attendance) {
                /*
                |--------------------------------------------------------------------------
                | Belum tercatat dianggap 0.
                |--------------------------------------------------------------------------
                */

                continue;
            }

            $points +=
                $this->attendanceWeights[
                    $attendance->status
                ] ?? 0;
        }

        return round(
            ($points / $totalSessions)
            * 100,
            2
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Sinkronkan faktor otomatis
    |--------------------------------------------------------------------------
    */

    public function syncAutomaticScores(
        AssessmentConfig $config,
        Student $student
    ): void {
        $config->loadMissing(
            'items.factor'
        );

        foreach ($config->items as $item) {

            $factor =
                $item->factor;

            if (
                $factor->source_type !==
                'attendance'
            ) {
                continue;
            }

            $score =
                $this->attendanceScore(
                    $config,
                    $student
                );

            StudentScore::query()
                ->updateOrCreate(
                    [
                        'assessment_config_id' => $config->id,

                        'student_id' => $student->id,

                        'assessment_factor_id' => $factor->id,
                    ],
                    [
                        'score' => $score,

                        'source' => 'attendance',

                        'entered_by' => null,
                    ]
                );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Nilai manual
    |--------------------------------------------------------------------------
    */

    public function saveManualScore(
        AssessmentConfig $config,
        Student $student,
        int $factorId,
        float $score,
        ?string $notes = null
    ): StudentScore {
        if (
            $score < 0
            ||
            $score > 100
        ) {
            throw ValidationException::withMessages([
                'scores' => 'Nilai harus antara 0 sampai 100.',
            ]);
        }

        $item =
            $config
                ->items()
                ->with('factor')
                ->where(
                    'assessment_factor_id',
                    $factorId
                )
                ->firstOrFail();

        if (
            $item->factor
                ->source_type !==
            'manual'
        ) {
            throw ValidationException::withMessages([
                'scores' => 'Faktor otomatis tidak dapat diisi manual.',
            ]);
        }

        return StudentScore::query()
            ->updateOrCreate(
                [
                    'assessment_config_id' => $config->id,

                    'student_id' => $student->id,

                    'assessment_factor_id' => $factorId,
                ],
                [
                    'score' => round(
                        $score,
                        2
                    ),

                    'source' => 'manual',

                    'entered_by' => auth()->id(),

                    'notes' => $notes,
                ]
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Hitung nilai akhir
    |--------------------------------------------------------------------------
    */

    public function calculateFinalGrade(
        AssessmentConfig $config,
        Student $student
    ): FinalGrade {
        $config->loadMissing(
            'items.factor'
        );

        $this->syncAutomaticScores(
            $config,
            $student
        );

        $scores =
            StudentScore::query()
                ->where(
                    'assessment_config_id',
                    $config->id
                )
                ->where(
                    'student_id',
                    $student->id
                )
                ->get()
                ->keyBy(
                    'assessment_factor_id'
                );

        $finalScore = 0.0;

        foreach ($config->items as $item) {

            $score =
                $scores->get(
                    $item
                        ->assessment_factor_id
                );

            if (! $score) {
                throw ValidationException::withMessages([
                    'scores' => 'Nilai '
                        .$item->factor->name
                        .' untuk '
                        .$student->name
                        .' belum diisi.',
                ]);
            }

            $finalScore +=
                (float) $score->score
                *
                (
                    (float)
                    $item->weight
                    /
                    100
                );
        }

        $finalScore =
            round(
                $finalScore,
                2
            );

        $grade =
            $this->resolveGrade(
                $finalScore
            );

        return DB::transaction(
            fn () => FinalGrade::query()
                ->updateOrCreate(
                    [
                        'assessment_config_id' => $config->id,

                        'student_id' => $student->id,
                    ],
                    [
                        'final_score' => $finalScore,

                        'letter_grade' => $grade[
                                'letter_grade'
                            ] ?? null,

                        'description' => $grade[
                                'description'
                            ] ?? null,

                        'attendance_source_version' => app(
                            AttendanceWeightService::class
                        )->version(),

                        'assessment_config_signature' => $this->configurationSignature(
                            $config
                        ),

                        'calculated_at' => now(),

                        'calculated_by' => auth()->id(),
                    ]
                )
        );
    }

    protected function resolveGrade(
        float $score
    ): array {
        $schoolId =
            app(SchoolContext::class)
                ->id();

        $config =
            GradeScaleConfig::query()
                ->where(
                    'is_active',
                    true
                )
                ->with('scales')
                ->first();

        if (! $config) {
            return [
                'letter_grade' => null,

                'description' => null,
            ];
        }

        $scale =
            $config
                ->scales
                ->first(
                    fn ($scale) => $score >=
                            (float)
                            $scale
                                ->min_score
                        &&
                        $score <=
                            (float)
                            $scale
                                ->max_score
                );

        return [
            'letter_grade' => $scale
                ?->letter_grade,

            'description' => $scale
                ?->description,
        ];
    }

    public function syncAttendanceScores(
        AssessmentConfig $config
    ): int {
        $config->loadMissing([
            'items.factor',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Cari faktor yang bersumber dari absensi
        |--------------------------------------------------------------------------
        */

        $attendanceItems =
            $config
                ->items
                ->filter(
                    fn ($item): bool => $item->factor
                        &&
                        $item->factor
                            ->source_type
                        === 'attendance'
                );

        if (
            $attendanceItems
                ->isEmpty()
        ) {
            return 0;
        }

        /*
        |--------------------------------------------------------------------------
        | Siswa pada tahun ajaran konfigurasi
        |--------------------------------------------------------------------------
        */

        $students =
            Student::query()
                ->where(
                    'status',
                    'active'
                )
                ->whereHas(
                    'enrollments',
                    fn ($query) => $query->where(
                        'academic_year_id',
                        $config
                            ->academic_year_id
                    )
                )
                ->get();

        $version =
            app(
                AttendanceWeightService::class
            )->version();

        $updated =
            0;

        DB::transaction(
            function () use (
                $config,
                $attendanceItems,
                $students,
                $version,
                &$updated
            ): void {

                foreach (
                    $students as $student
                ) {

                    $attendanceScore =
                        $this->attendanceScore(
                            $config,
                            $student
                        );

                    foreach (
                        $attendanceItems as $item
                    ) {

                        // StudentScore::query()
                        //     ->updateOrCreate(
                        //         [
                        //             'assessment_config_id' => $config->id,

                        //             'student_id' => $student->id,

                        //             'assessment_factor_id' => $item
                        //                 ->assessment_factor_id,
                        //         ],
                        //         [
                        //             'score' => $attendanceScore,

                        //             'source' => 'attendance',

                        //             'source_version' => $version,

                        //             'source_synced_at' => now(),

                        //             'entered_by' => auth()->id(),

                        //             'notes' => 'Nilai otomatis dari rekap kehadiran.',
                        //         ]
                        //     );

                        $result =
                            app(
                                StudentScoreWriter::class
                            )->writeAutomatic(
                                assessmentConfigId: $config->id,

                                studentId: $student->id,

                                assessmentFactorId: $item->assessment_factor_id,

                                score: $attendanceScore,

                                source: StudentScoreWriter::SOURCE_ATTENDANCE,

                                sourceVersion: $version,

                                notes: 'Sinkronisasi otomatis nilai kehadiran'
                            );

                        if (
                            $result[
                                'skipped_manual'
                            ]
                        ) {
                            continue;
                        }

                        $updated++;
                    }
                }
            }
        );

        return $updated;
    }

    public function attendanceSyncStatus(
        AssessmentConfig $config
    ): array {
        $config->loadMissing([
            'items.factor',
        ]);

        $factorIds =
            $config
                ->items
                ->filter(
                    fn ($item): bool => $item->factor
                        &&
                        $item->factor
                            ->source_type
                        === 'attendance'
                )
                ->pluck(
                    'assessment_factor_id'
                )
                ->values();

        if (
            $factorIds
                ->isEmpty()
        ) {
            return [
                'version' => app(
                    AttendanceWeightService::class
                )->version(),

                'expected_count' => 0,

                'current_count' => 0,

                'stale_count' => 0,

                'is_stale' => false,
            ];
        }

        $studentIds =
            Student::query()
                ->where(
                    'status',
                    'active'
                )
                ->whereHas(
                    'enrollments',
                    fn ($query) => $query->where(
                        'academic_year_id',
                        $config
                            ->academic_year_id
                    )
                )
                ->pluck(
                    'id'
                );

        $version =
            app(
                AttendanceWeightService::class
            )->version();

        $expectedCount =
            $studentIds->count()
            *
            $factorIds->count();

        /*
        |--------------------------------------------------------------------------
        | Score yang sudah menggunakan version terbaru
        |--------------------------------------------------------------------------
        */

        $currentCount =
            StudentScore::query()
                ->where(
                    'assessment_config_id',
                    $config->id
                )
                ->whereIn(
                    'student_id',
                    $studentIds
                )
                ->whereIn(
                    'assessment_factor_id',
                    $factorIds
                )
                ->where(
                    'source',
                    'attendance'
                )
                ->where(
                    'source_version',
                    $version
                )
                ->count();

        $staleCount =
            max(
                0,
                $expectedCount
                - $currentCount
            );

        return [
            'version' => $version,

            'expected_count' => $expectedCount,

            'current_count' => $currentCount,

            'stale_count' => $staleCount,

            'is_stale' => $staleCount > 0,
        ];
    }

    public function finalGradeSyncStatus(
        AssessmentConfig $config
    ): array {
        $config->loadMissing([
            'items.factor',
        ]);

        $currentConfigSignature =
            $this->configurationSignature(
                $config
            );

        /*
        |--------------------------------------------------------------------------
        | Siswa yang termasuk periode konfigurasi
        |--------------------------------------------------------------------------
        */

        $studentIds =
            Student::query()
                ->where(
                    'status',
                    'active'
                )
                ->whereHas(
                    'enrollments',
                    fn ($query) => $query->where(
                        'academic_year_id',
                        $config->academic_year_id
                    )
                )
                ->pluck('id');

        $expectedCount =
            $studentIds->count();

        /*
        |--------------------------------------------------------------------------
        | Apakah konfigurasi menggunakan faktor absensi?
        |--------------------------------------------------------------------------
        */

        $usesAttendance =
            $config
                ->items
                ->contains(
                    fn ($item): bool => $item->factor
                        &&
                        $item->factor->source_type
                            === 'attendance'
                );

        $attendanceVersion =
            app(
                AttendanceWeightService::class
            )->version();

        if ($expectedCount === 0) {
            return [
                'version' => $attendanceVersion,

                'uses_attendance' => $usesAttendance,

                'expected_count' => 0,

                'current_count' => 0,

                'stale_count' => 0,

                'missing_final_count' => 0,

                'attendance_version_stale_count' => 0,

                'score_changed_count' => 0,

                'configuration_signature' => $currentConfigSignature,

                'configuration_changed_count' => 0,

                'is_stale' => false,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Final Grade
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | Perubahan terakhir StudentScore per siswa
        |--------------------------------------------------------------------------
        |
        | Ini membuat perubahan dari sumber mana pun terdeteksi:
        |
        | - attendance
        | - manual
        | - system
        | - activity assessment
        |
        |--------------------------------------------------------------------------
        */

        $latestScoreUpdates =
            StudentScore::query()
                ->where(
                    'assessment_config_id',
                    $config->id
                )
                ->whereIn(
                    'student_id',
                    $studentIds
                )
                ->selectRaw(
                    'student_id, MAX(updated_at) AS latest_score_at'
                )
                ->groupBy(
                    'student_id'
                )
                ->pluck(
                    'latest_score_at',
                    'student_id'
                );

        /*
        |--------------------------------------------------------------------------
        | Hitung status
        |--------------------------------------------------------------------------
        */

        $staleCount = 0;

        $missingFinalCount = 0;

        $attendanceVersionStaleCount = 0;

        $scoreChangedCount = 0;

        $configurationChangedCount = 0;

        foreach (
            $studentIds as $studentId
        ) {
            $finalGrade =
                $finalGrades->get(
                    $studentId
                );

            /*
            |--------------------------------------------------------------------------
            | Belum punya FinalGrade
            |--------------------------------------------------------------------------
            */

            if (! $finalGrade) {
                $staleCount++;

                $missingFinalCount++;

                continue;
            }

            $studentIsStale =
                false;

            /*
            |--------------------------------------------------------------------------
            | Konfigurasi Penilaian Berubah
            |--------------------------------------------------------------------------
            */

            if (
                ! $finalGrade
                    ->assessment_config_signature
                ||
                ! hash_equals(
                    $currentConfigSignature,
                    (string) $finalGrade
                        ->assessment_config_signature
                )
            ) {
                $studentIsStale =
                    true;

                $configurationChangedCount++;
            }

            /*
            |--------------------------------------------------------------------------
            | Versi absensi berubah
            |--------------------------------------------------------------------------
            |
            | Hanya diperiksa jika konfigurasi memang mempunyai faktor attendance.
            |--------------------------------------------------------------------------
            */

            if (
                $usesAttendance
                &&
                (
                    $finalGrade
                        ->attendance_source_version
                    === null
                    ||
                    (int) $finalGrade
                        ->attendance_source_version
                        !== $attendanceVersion
                )
            ) {
                $studentIsStale =
                    true;

                $attendanceVersionStaleCount++;
            }

            /*
            |--------------------------------------------------------------------------
            | StudentScore berubah setelah FinalGrade dihitung
            |--------------------------------------------------------------------------
            */

            $latestScoreAt =
                $latestScoreUpdates->get(
                    $studentId
                );

            if ($latestScoreAt) {
                $scoreChanged =
                    ! $finalGrade
                        ->calculated_at
                    ||
                    $finalGrade
                        ->calculated_at
                        ->lt(
                            Carbon::parse(
                                $latestScoreAt
                            )
                        );

                if ($scoreChanged) {
                    $studentIsStale =
                        true;

                    $scoreChangedCount++;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | FinalGrade legacy tanpa calculated_at
            |--------------------------------------------------------------------------
            */

            if (
                ! $latestScoreAt
                &&
                ! $finalGrade
                    ->calculated_at
            ) {
                $studentIsStale =
                    true;

                $scoreChangedCount++;
            }

            if ($studentIsStale) {
                $staleCount++;
            }
        }

        return [
            'version' => $attendanceVersion,

            'uses_attendance' => $usesAttendance,

            'expected_count' => $expectedCount,

            'current_count' => max(
                0,
                $expectedCount
                - $staleCount
            ),

            'stale_count' => $staleCount,

            'missing_final_count' => $missingFinalCount,

            'attendance_version_stale_count' => $attendanceVersionStaleCount,

            'score_changed_count' => $scoreChangedCount,

            'configuration_changed_count' => $configurationChangedCount,

            'configuration_signature' => $currentConfigSignature,

            'is_stale' => $staleCount > 0,
        ];
    }

    public function syncFinalGrades(
        AssessmentConfig $config
    ): int {
        $students =
            Student::query()
                ->where(
                    'status',
                    'active'
                )
                ->whereHas(
                    'enrollments',
                    fn ($query) => $query->where(
                        'academic_year_id',
                        $config->academic_year_id
                    )
                )
                ->get();

        $updated = 0;

        foreach ($students as $student) {
            try {
                $this->calculateFinalGrade(
                    $config,
                    $student
                );

                $updated++;
            } catch (ValidationException) {
                /*
                |--------------------------------------------------------------------------
                | Nilai faktor yang belum lengkap tetap ditandai belum sinkron.
                |--------------------------------------------------------------------------
                */
            }
        }

        return $updated;
    }

    public function syncAllScores(
        AssessmentConfig $config
    ): array {

        app(
            SemesterClosureService::class
        )
            ->assertOpen(
                $config->academic_year_id,
                $config->semester_id
            );

        $beforeAttendance =
            $this->attendanceSyncStatus(
                $config
            );

        $beforeFinal =
            $this->finalGradeSyncStatus(
                $config
            );

        $result =
            DB::transaction(
                function () use (
                    $config
                ): array {
                    $attendanceUpdated =
                        $this->syncAttendanceScores(
                            $config
                        );

                    $finalGradesUpdated =
                        $this->syncFinalGrades(
                            $config
                        );

                    return [
                        'attendance_scores' => $attendanceUpdated,

                        'final_grades' => $finalGradesUpdated,
                    ];
                }
            );

        $afterAttendance =
            $this->attendanceSyncStatus(
                $config
            );

        $afterFinal =
            $this->finalGradeSyncStatus(
                $config
            );

        app(
            AssessmentAuditService::class
        )
            ->record(
                action: 'assessment.synchronized',

                subject: $config,

                description: 'Data penilaian disinkronkan.',

                oldValues: [
                    'attendance_stale' => $beforeAttendance[
                            'stale_count'
                        ]
                        ?? 0,

                    'final_stale' => $beforeFinal[
                            'stale_count'
                        ]
                        ?? 0,
                ],

                newValues: [
                    'attendance_stale' => $afterAttendance[
                            'stale_count'
                        ]
                        ?? 0,

                    'final_stale' => $afterFinal[
                            'stale_count'
                        ]
                        ?? 0,
                ],

                metadata: [
                    'attendance_scores_updated' => $result[
                            'attendance_scores'
                        ],

                    'final_grades_updated' => $result[
                            'final_grades'
                        ],

                    'academic_year_id' => $config
                        ->academic_year_id,

                    'semester_id' => $config
                        ->semester_id,
                ],

                module: 'synchronization'
            );

        return $result;
    }

    public function invalidateFinalGrades(
        AssessmentConfig $config,
        ?iterable $studentIds = null
    ): int {
        $query =
            FinalGrade::query()
                ->where(
                    'assessment_config_id',
                    $config->id
                );

        if ($studentIds !== null) {
            $ids =
                collect(
                    $studentIds
                )
                    ->map(
                        fn ($id): int => (int) $id
                    )
                    ->filter()
                    ->unique()
                    ->values();

            if ($ids->isEmpty()) {
                return 0;
            }

            $query->whereIn(
                'student_id',
                $ids
            );
        }

        /*
        |--------------------------------------------------------------------------
        | calculated_at = null berarti nilai akhir wajib dihitung ulang.
        |--------------------------------------------------------------------------
        */

        return $query->update([
            'calculated_at' => null,

            'calculated_by' => null,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Assessment Configuration Signature
    |--------------------------------------------------------------------------
    |
    | Signature digunakan untuk mendeteksi perubahan struktur konfigurasi
    | penilaian tanpa bergantung pada version counter manual.
    |
    */

    public function configurationSignature(
        AssessmentConfig $config
    ): string {
        $config->loadMissing([
            'items.factor',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Urutkan berdasarkan assessment_factor_id agar signature deterministik.
        |--------------------------------------------------------------------------
        */

        $items =
            $config
                ->items
                ->sortBy(
                    fn ($item): int => (int) $item
                        ->assessment_factor_id
                )
                ->values()
                ->map(
                    function ($item): array {
                        return [
                            'assessment_factor_id' => (int) $item
                                ->assessment_factor_id,

                            /*
                            |--------------------------------------------------------------------------
                            | Normalisasi menjadi string agar:
                            |
                            | 20
                            | 20.0
                            | 20.00
                            |
                            | menghasilkan representasi yang konsisten.
                            |--------------------------------------------------------------------------
                            */

                            'weight' => number_format(
                                (float) $item
                                    ->weight,
                                4,
                                '.',
                                ''
                            ),

                            'source_type' => $item
                                ->factor
                                ?->source_type,
                        ];
                    }
                )
                ->all();

        /*
        |--------------------------------------------------------------------------
        | Hanya field yang berpengaruh terhadap perhitungan.
        |--------------------------------------------------------------------------
        */

        $payload = [
            'assessment_config_id' => (int) $config->id,

            'academic_year_id' => (int) $config
                ->academic_year_id,

            'semester_id' => (int) $config
                ->semester_id,

            'items' => $items,
        ];

        return hash(
            'sha256',
            json_encode(
                $payload,
                JSON_UNESCAPED_UNICODE
                |
                JSON_UNESCAPED_SLASHES
                |
                JSON_PRESERVE_ZERO_FRACTION
            )
        );
    }
}
