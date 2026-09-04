<?php

namespace App\Services;

use App\Models\AssessmentConfig;
use App\Models\FinalGrade;
use App\Models\SemesterClosure;
use App\Models\SemesterGradeSnapshot;
use App\Models\Student;
use App\Models\StudentScore;
use App\Support\SchoolContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SemesterClosureService
{
    /*
    |--------------------------------------------------------------------------
    | Closure Terakhir
    |--------------------------------------------------------------------------
    */

    public function currentClosure(
        int $academicYearId,
        int $semesterId
    ): ?SemesterClosure {
        return SemesterClosure::query()
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
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Apakah Periode Dikunci
    |--------------------------------------------------------------------------
    */

    public function isLocked(
        int $academicYearId,
        int $semesterId
    ): bool {
        return $this
            ->currentClosure(
                $academicYearId,
                $semesterId
            )
            ?->isLocked()
            ?? false;
    }

    /*
    |--------------------------------------------------------------------------
    | Guard Write
    |--------------------------------------------------------------------------
    */

    public function assertOpen(
        int $academicYearId,
        ?int $semesterId
    ): void {
        if ($semesterId === null) {
            return;
        }

        if (
            $this->isLocked(
                $academicYearId,
                $semesterId
            )
        ) {
            throw ValidationException::withMessages([
                'semester' => 'Semester telah dikunci. '
                    .'Buka kembali semester terlebih dahulu '
                    .'untuk melakukan perubahan data penilaian.',
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Kunci Semester
    |--------------------------------------------------------------------------
    */

    public function lock(
        AssessmentConfig $config
    ): SemesterClosure {
        $school =
            app(
                SchoolContext::class
            )->school();

        abort_unless(
            $school,
            409,
            'Pilih sekolah aktif terlebih dahulu.'
        );

        if (
            ! $config->is_active
        ) {
            throw ValidationException::withMessages([
                'semester' => 'Konfigurasi penilaian harus aktif '
                    .'sebelum semester dapat dikunci.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Jangan lock dua kali tanpa reopen.
        |--------------------------------------------------------------------------
        */

        $current =
            $this->currentClosure(
                $config->academic_year_id,
                $config->semester_id
            );

        if (
            $current
            &&
            $current->isLocked()
        ) {
            throw ValidationException::withMessages([
                'semester' => 'Semester ini sudah dikunci pada versi '
                    .$current->version
                    .'.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Seluruh nilai harus current.
        |--------------------------------------------------------------------------
        */

        $assessmentService =
            app(
                AssessmentService::class
            );

        $attendanceStatus =
            $assessmentService
                ->attendanceSyncStatus(
                    $config
                );

        $finalStatus =
            $assessmentService
                ->finalGradeSyncStatus(
                    $config
                );

        if (
            (
                $attendanceStatus[
                    'is_stale'
                ]
                ?? false
            )
            ||
            (
                $finalStatus[
                    'is_stale'
                ]
                ?? false
            )
        ) {
            throw ValidationException::withMessages([
                'semester' => 'Semester belum dapat dikunci karena masih '
                    .'terdapat nilai yang belum sinkron. '
                    .'Jalankan Sinkronisasi Penilaian terlebih dahulu.',
            ]);
        }

        $config->loadMissing([
            'academicYear',
            'semester',
            'items.factor',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Siswa periode
        |--------------------------------------------------------------------------
        */

        $students =
            Student::query()
                ->with([
                    'enrollments' => function ($query) use (
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
                    fn ($query) => $query->where(
                        'academic_year_id',
                        $config
                            ->academic_year_id
                    )
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
        | Defensive validation
        |--------------------------------------------------------------------------
        */

        if (
            $finalGrades->count()
            !== $students->count()
        ) {
            throw ValidationException::withMessages([
                'semester' => 'Jumlah nilai akhir tidak sesuai dengan '
                    .'jumlah siswa aktif pada periode ini.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Student Score
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
                    fn ($items) => $items->keyBy(
                        'assessment_factor_id'
                    )
                );

        $configSignature =
            $assessmentService
                ->configurationSignature(
                    $config
                );

        $attendanceVersion =
            app(
                AttendanceWeightService::class
            )->version();

        return DB::transaction(
            function () use (
                $school,
                $config,
                $students,
                $finalGrades,
                $scores,
                $configSignature,
                $attendanceVersion
            ): SemesterClosure {

                /*
                |--------------------------------------------------------------------------
                | Version berikutnya
                |--------------------------------------------------------------------------
                */

                $lastVersion =
                    SemesterClosure::query()
                        ->where(
                            'academic_year_id',
                            $config
                                ->academic_year_id
                        )
                        ->where(
                            'semester_id',
                            $config
                                ->semester_id
                        )
                        ->max(
                            'version'
                        );

                $nextVersion =
                    ((int) $lastVersion)
                    + 1;

                /*
                |--------------------------------------------------------------------------
                | Snapshot konfigurasi faktor
                |--------------------------------------------------------------------------
                */

                $factorConfiguration =
                    $config
                        ->items
                        ->sortBy(
                            'assessment_factor_id'
                        )
                        ->values()
                        ->map(
                            fn ($item): array => [
                                'assessment_factor_id' => (int) $item
                                    ->assessment_factor_id,

                                'name' => $item
                                    ->factor
                                    ?->name,

                                'source_type' => $item
                                    ->factor
                                    ?->source_type,

                                'weight' => (float) $item
                                    ->weight,
                            ]
                        )
                        ->all();

                $closure =
                    SemesterClosure::query()
                        ->create([
                            'academic_year_id' => $config
                                ->academic_year_id,

                            'semester_id' => $config
                                ->semester_id,

                            'assessment_config_id' => $config->id,

                            'version' => $nextVersion,

                            'status' => 'locked',

                            'config_signature' => $configSignature,

                            'attendance_source_version' => $attendanceVersion,

                            'snapshot_count' => 0,

                            'snapshot_checksum' => null,

                            'metadata' => [
                                'school' => [
                                    'id' => $school->id,

                                    'name' => $school->name,
                                ],

                                'academic_year' => [
                                    'id' => $config
                                        ->academic_year_id,

                                    'name' => $config
                                        ->academicYear
                                        ?->name,
                                ],

                                'semester' => [
                                    'id' => $config
                                        ->semester_id,

                                    'name' => $config
                                        ->semester
                                        ?->name,
                                ],

                                'factors' => $factorConfiguration,
                            ],

                            'locked_by' => auth()->id(),

                            'locked_at' => now(),
                        ]);

                $recordHashes = [];

                foreach (
                    $students as $student
                ) {
                    $final =
                        $finalGrades->get(
                            $student->id
                        );

                    if (! $final) {
                        throw ValidationException::withMessages([
                            'semester' => "Nilai akhir {$student->name} tidak ditemukan.",
                        ]);
                    }

                    $enrollment =
                        $student
                            ->enrollments
                            ->first();

                    /*
                    |--------------------------------------------------------------------------
                    | Breakdown faktor
                    |--------------------------------------------------------------------------
                    */

                    $factorScores = [];

                    foreach (
                        $config->items as $item
                    ) {
                        $studentScore =
                            $scores
                                ->get(
                                    $student->id,
                                    collect()
                                )
                                ->get(
                                    $item
                                        ->assessment_factor_id
                                );

                        $factorScores[] = [
                            'assessment_factor_id' => (int) $item
                                ->assessment_factor_id,

                            'name' => $item
                                ->factor
                                ?->name,

                            'source_type' => $item
                                ->factor
                                ?->source_type,

                            'weight' => (float) $item
                                ->weight,

                            'score' => $studentScore
                                    ? (float) $studentScore
                                        ->score
                                    : null,
                        ];
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Data canonical untuk hash
                    |--------------------------------------------------------------------------
                    */

                    $payload = [
                        'closure_version' => $nextVersion,

                        'student_id' => (int) $student->id,

                        'student_nis' => $student->nis,

                        'student_name' => $student->name,

                        'classroom_id' => $enrollment
                            ?->classroom_id,

                        'classroom_name' => $enrollment
                            ?->classroom
                            ?->name,

                        'final_score' => number_format(
                            (float) $final
                                ->final_score,
                            2,
                            '.',
                            ''
                        ),

                        'letter_grade' => $final
                            ->letter_grade,

                        'description' => $final
                            ->description,

                        'factor_scores' => $factorScores,

                        'config_signature' => $configSignature,

                        'attendance_source_version' => $attendanceVersion,
                    ];

                    $recordHash =
                        hash(
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

                    SemesterGradeSnapshot::query()
                        ->create([
                            'semester_closure_id' => $closure->id,

                            'student_id' => $student->id,

                            'student_nis' => $student->nis,

                            'student_name' => $student->name,

                            'classroom_id' => $enrollment
                                ?->classroom_id,

                            'classroom_name' => $enrollment
                                ?->classroom
                                ?->name,

                            'final_score' => $final
                                ->final_score,

                            'letter_grade' => $final
                                ->letter_grade,

                            'description' => $final
                                ->description,

                            'factor_scores' => $factorScores,

                            'config_signature' => $configSignature,

                            'attendance_source_version' => $attendanceVersion,

                            'source_calculated_at' => $final
                                ->calculated_at,

                            'record_hash' => $recordHash,

                            'created_at' => now(),
                        ]);

                    $recordHashes[] =
                        $recordHash;
                }

                /*
                |--------------------------------------------------------------------------
                | Checksum seluruh snapshot
                |--------------------------------------------------------------------------
                */

                sort(
                    $recordHashes
                );

                $checksum =
                    hash(
                        'sha256',
                        implode(
                            '|',
                            $recordHashes
                        )
                    );

                $closure->update([
                    'snapshot_count' => count(
                        $recordHashes
                    ),

                    'snapshot_checksum' => $checksum,
                ]);

                /*
                |--------------------------------------------------------------------------
                | Audit
                |--------------------------------------------------------------------------
                */

                app(
                    AssessmentAuditService::class
                )
                    ->record(
                        action: 'semester.locked',

                        subject: $closure,

                        description: 'Semester dikunci dan snapshot nilai resmi dibuat.',

                        newValues: [
                            'version' => $closure
                                ->version,

                            'snapshot_count' => $closure
                                ->snapshot_count,

                            'snapshot_checksum' => $closure
                                ->snapshot_checksum,
                        ],

                        metadata: [
                            'academic_year_id' => $config
                                ->academic_year_id,

                            'semester_id' => $config
                                ->semester_id,

                            'assessment_config_id' => $config->id,

                            'config_signature' => $configSignature,
                        ],

                        module: 'semester_closure'
                    );

                return $closure->fresh([
                    'snapshots',
                ]);
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Buka Kembali Semester
    |--------------------------------------------------------------------------
    */

    public function reopen(
        SemesterClosure $closure,
        string $reason
    ): SemesterClosure {
        $reason =
            trim(
                $reason
            );

        if (
            mb_strlen(
                $reason
            ) < 5
        ) {
            throw ValidationException::withMessages([
                'reopenReason' => 'Alasan membuka semester minimal 5 karakter.',
            ]);
        }

        $latest =
            $this->currentClosure(
                $closure->academic_year_id,
                $closure->semester_id
            );

        if (
            ! $latest
            ||
            $latest->id
                !== $closure->id
        ) {
            throw ValidationException::withMessages([
                'reopenReason' => 'Hanya versi semester terbaru yang dapat dibuka kembali.',
            ]);
        }

        if (
            ! $closure->isLocked()
        ) {
            throw ValidationException::withMessages([
                'reopenReason' => 'Semester tersebut sudah dalam keadaan terbuka.',
            ]);
        }

        return DB::transaction(
            function () use (
                $closure,
                $reason
            ): SemesterClosure {

                $closure->update([
                    'status' => 'reopened',

                    'reopened_by' => auth()->id(),

                    'reopened_at' => now(),

                    'reopen_reason' => $reason,
                ]);

                /*
                |--------------------------------------------------------------------------
                | Snapshot tidak dihapus.
                |--------------------------------------------------------------------------
                */

                app(
                    AssessmentAuditService::class
                )
                    ->record(
                        action: 'semester.reopened',

                        subject: $closure,

                        description: 'Semester dibuka kembali untuk koreksi.',

                        oldValues: [
                            'status' => 'locked',
                        ],

                        newValues: [
                            'status' => 'reopened',

                            'reason' => $reason,
                        ],

                        metadata: [
                            'version' => $closure
                                ->version,

                            'snapshot_checksum' => $closure
                                ->snapshot_checksum,
                        ],

                        module: 'semester_closure'
                    );

                return $closure->fresh();
            }
        );
    }
}
