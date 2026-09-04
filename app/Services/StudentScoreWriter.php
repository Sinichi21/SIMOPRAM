<?php

namespace App\Services;

use App\Models\StudentScore;
use App\Support\SchoolContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentScoreWriter
{
    public const SOURCE_MANUAL =
        'manual';

    public const SOURCE_ATTENDANCE =
        'attendance';

    public const SOURCE_ACTIVITY_ASSESSMENT =
        'activity_assessment';

    /*
    |--------------------------------------------------------------------------
    | Simpan Nilai Manual
    |--------------------------------------------------------------------------
    |
    | student_scores tetap memakai SATU row canonical untuk:
    |
    | assessment_config_id + student_id + assessment_factor_id
    |
    | Nilai manual sengaja menggantikan nilai otomatis pada row canonical dan
    | menjadi override sampai operator memilih kembali ke nilai otomatis.
    |--------------------------------------------------------------------------
    */

    public function writeManual(
        int $assessmentConfigId,
        int $studentId,
        int $assessmentFactorId,
        float $score,
        ?int $enteredBy = null,
        ?string $notes = null
    ): StudentScore {
        $this->assertSchoolContext();

        $this->validateScore(
            $score
        );

        return DB::transaction(
            function () use (
                $assessmentConfigId,
                $studentId,
                $assessmentFactorId,
                $score,
                $enteredBy,
                $notes
            ): StudentScore {
                $studentScore =
                    $this->findCanonicalForUpdate(
                        $assessmentConfigId,
                        $studentId,
                        $assessmentFactorId
                    )
                    ?? new StudentScore;

                $studentScore->fill([
                    'assessment_config_id' => $assessmentConfigId,

                    'student_id' => $studentId,

                    'assessment_factor_id' => $assessmentFactorId,

                    'score' => round(
                        $score,
                        2
                    ),

                    'source' => self::SOURCE_MANUAL,

                    'source_version' => null,

                    'source_synced_at' => null,

                    'entered_by' => $enteredBy,

                    'notes' => $notes,
                ]);

                $studentScore->save();

                return $studentScore->fresh();
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Simpan Nilai Otomatis
    |--------------------------------------------------------------------------
    |
    | ATURAN TERPENTING:
    |
    | Jika row canonical saat ini source=manual, sinkronisasi otomatis TIDAK
    | BOLEH menimpa score tersebut.
    |
    | Return:
    | [
    |     'score'          => StudentScore,
    |     'written'        => bool,
    |     'skipped_manual' => bool,
    |     'created'        => bool,
    | ]
    |--------------------------------------------------------------------------
    */

    public function writeAutomatic(
        int $assessmentConfigId,
        int $studentId,
        int $assessmentFactorId,
        float $score,
        string $source,
        ?int $sourceVersion = null,
        ?string $notes = null
    ): array {
        $this->assertSchoolContext();

        $source =
            trim(
                $source
            );

        if (
            $source === ''
            ||
            $source === self::SOURCE_MANUAL
        ) {
            throw ValidationException::withMessages([
                'source' => 'writeAutomatic() hanya menerima source otomatis.',
            ]);
        }

        $this->validateScore(
            $score
        );

        return DB::transaction(
            function () use (
                $assessmentConfigId,
                $studentId,
                $assessmentFactorId,
                $score,
                $source,
                $sourceVersion,
                $notes
            ): array {
                $studentScore =
                    $this->findCanonicalForUpdate(
                        $assessmentConfigId,
                        $studentId,
                        $assessmentFactorId
                    );

                /*
                |--------------------------------------------------------------------------
                | Manual override selalu menang.
                |--------------------------------------------------------------------------
                */

                if (
                    $studentScore
                    &&
                    $studentScore->source
                        === self::SOURCE_MANUAL
                ) {
                    return [
                        'score' => $studentScore,

                        'written' => false,

                        'skipped_manual' => true,

                        'created' => false,
                    ];
                }

                $created =
                    $studentScore === null;

                $studentScore ??=
                    new StudentScore;

                $studentScore->fill([
                    'assessment_config_id' => $assessmentConfigId,

                    'student_id' => $studentId,

                    'assessment_factor_id' => $assessmentFactorId,

                    'score' => round(
                        $score,
                        2
                    ),

                    'source' => $source,

                    'source_version' => $sourceVersion,

                    'source_synced_at' => now(),

                    /*
                    |--------------------------------------------------------------------------
                    | entered_by digunakan untuk input manual.
                    |--------------------------------------------------------------------------
                    */

                    'entered_by' => null,

                    'notes' => $notes,
                ]);

                $studentScore->save();

                return [
                    'score' => $studentScore->fresh(),

                    'written' => true,

                    'skipped_manual' => false,

                    'created' => $created,
                ];
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Hapus Hanya Nilai Otomatis dari Source Tertentu
    |--------------------------------------------------------------------------
    |
    | Digunakan ketika hasil rekap otomatis sudah tidak mempunyai sumber data.
    | Row manual tidak pernah dihapus oleh method ini.
    |--------------------------------------------------------------------------
    */

    public function deleteAutomatic(
        int $assessmentConfigId,
        int $studentId,
        int $assessmentFactorId,
        string $source
    ): bool {
        $this->assertSchoolContext();

        $source =
            trim(
                $source
            );

        if (
            $source === ''
            ||
            $source === self::SOURCE_MANUAL
        ) {
            throw ValidationException::withMessages([
                'source' => 'deleteAutomatic() membutuhkan source otomatis.',
            ]);
        }

        return DB::transaction(
            function () use (
                $assessmentConfigId,
                $studentId,
                $assessmentFactorId,
                $source
            ): bool {
                $studentScore =
                    $this->findCanonicalForUpdate(
                        $assessmentConfigId,
                        $studentId,
                        $assessmentFactorId
                    );

                if (! $studentScore) {
                    return false;
                }

                /*
                |--------------------------------------------------------------------------
                | Source lain atau manual tidak boleh disentuh.
                |--------------------------------------------------------------------------
                */

                if (
                    $studentScore->source
                        !== $source
                ) {
                    return false;
                }

                return (bool)
                    $studentScore->delete();
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Lepas Manual Override
    |--------------------------------------------------------------------------
    |
    | Method ini hanya menghapus row manual canonical. Setelah itu caller harus
    | menjalankan sinkronisasi source otomatis yang sesuai agar nilai kembali
    | terbentuk dari attendance/activity assessment.
    |--------------------------------------------------------------------------
    */

    public function removeManualOverride(
        int $assessmentConfigId,
        int $studentId,
        int $assessmentFactorId
    ): bool {
        $this->assertSchoolContext();

        return DB::transaction(
            function () use (
                $assessmentConfigId,
                $studentId,
                $assessmentFactorId
            ): bool {
                $studentScore =
                    $this->findCanonicalForUpdate(
                        $assessmentConfigId,
                        $studentId,
                        $assessmentFactorId
                    );

                if (
                    ! $studentScore
                    ||
                    $studentScore->source
                        !== self::SOURCE_MANUAL
                ) {
                    return false;
                }

                return (bool)
                    $studentScore->delete();
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Status Manual Override
    |--------------------------------------------------------------------------
    */

    public function hasManualOverride(
        int $assessmentConfigId,
        int $studentId,
        int $assessmentFactorId
    ): bool {
        $this->assertSchoolContext();

        return StudentScore::query()
            ->where(
                'assessment_config_id',
                $assessmentConfigId
            )
            ->where(
                'student_id',
                $studentId
            )
            ->where(
                'assessment_factor_id',
                $assessmentFactorId
            )
            ->where(
                'source',
                self::SOURCE_MANUAL
            )
            ->exists();
    }

    /*
    |--------------------------------------------------------------------------
    | Canonical Row Lock
    |--------------------------------------------------------------------------
    */

    protected function findCanonicalForUpdate(
        int $assessmentConfigId,
        int $studentId,
        int $assessmentFactorId
    ): ?StudentScore {
        return StudentScore::query()
            ->where(
                'assessment_config_id',
                $assessmentConfigId
            )
            ->where(
                'student_id',
                $studentId
            )
            ->where(
                'assessment_factor_id',
                $assessmentFactorId
            )
            ->lockForUpdate()
            ->first();
    }

    protected function validateScore(
        float $score
    ): void {
        if (
            ! is_finite(
                $score
            )
            ||
            $score < 0
            ||
            $score > 100
        ) {
            throw ValidationException::withMessages([
                'score' => 'Nilai harus berada pada rentang 0 sampai 100.',
            ]);
        }
    }

    protected function assertSchoolContext(): void
    {
        abort_unless(
            app(
                SchoolContext::class
            )->hasSchool(),
            409,
            'Pilih sekolah aktif terlebih dahulu.'
        );
    }
}
