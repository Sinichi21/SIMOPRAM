<?php

namespace App\Livewire\Assessments\Synchronization;

use App\Models\AcademicYear;
use App\Models\AssessmentConfig;
use App\Models\Semester;
use App\Services\AssessmentService;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Index extends Component
{
    public ?int $academicYearId = null;

    public ?int $semesterId = null;

    public bool $onlyStale = false;

    /*
    |--------------------------------------------------------------------------
    | Mount
    |--------------------------------------------------------------------------
    */

    public function mount(): void
    {
        $academicYear =
            AcademicYear::query()
                ->where(
                    'is_active',
                    true
                )
                ->first();

        $this->academicYearId =
            $academicYear?->id;

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
    | Ambil Config
    |--------------------------------------------------------------------------
    */

    protected function configs()
    {
        if (
            ! $this->academicYearId
            ||
            ! $this->semesterId
        ) {
            return collect();
        }

        return AssessmentConfig::query()
            ->with([
                'academicYear',
                'semester',
                'items.factor',
            ])
            ->where(
                'academic_year_id',
                $this->academicYearId
            )
            ->where(
                'semester_id',
                $this->semesterId
            )
            ->orderByDesc(
                'is_active'
            )
            ->orderByDesc(
                'id'
            )
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Status Satu Config
    |--------------------------------------------------------------------------
    */

    protected function statusForConfig(
        AssessmentConfig $config,
        AssessmentService $service
    ): array {
        $attendance =
            $service
                ->attendanceSyncStatus(
                    $config
                );

        $final =
            $service
                ->finalGradeSyncStatus(
                    $config
                );

        $isStale =
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
            );

        /*
        |--------------------------------------------------------------------------
        | Daftar Penyebab
        |--------------------------------------------------------------------------
        */

        $reasons = [];

        $attendanceStale =
            (int) (
                $attendance[
                    'stale_count'
                ]
                ?? 0
            );

        if (
            $attendanceStale > 0
        ) {
            $reasons[] =
                number_format(
                    $attendanceStale
                )
                .' nilai kehadiran belum sinkron.';
        }

        $missingFinal =
            (int) (
                $final[
                    'missing_final_count'
                ]
                ?? 0
            );

        if (
            $missingFinal > 0
        ) {
            $reasons[] =
                number_format(
                    $missingFinal
                )
                .' siswa belum memiliki nilai akhir.';
        }

        $attendanceVersionStale =
            (int) (
                $final[
                    'attendance_version_stale_count'
                ]
                ?? 0
            );

        if (
            $attendanceVersionStale > 0
        ) {
            $reasons[] =
                number_format(
                    $attendanceVersionStale
                )
                .' nilai akhir masih menggunakan '
                .'versi bobot kehadiran lama.';
        }

        $scoreChanged =
            (int) (
                $final[
                    'score_changed_count'
                ]
                ?? 0
            );

        if (
            $scoreChanged > 0
        ) {
            $reasons[] =
                number_format(
                    $scoreChanged
                )
                .' nilai akhir belum mengikuti '
                .'perubahan nilai faktor.';
        }

        if (
            $isStale
            &&
            count(
                $reasons
            ) === 0
        ) {
            $reasons[] =
                'Terdapat data penilaian yang perlu dihitung ulang.';
        }

        return [
            'attendance' => $attendance,

            'final' => $final,

            'is_stale' => $isStale,

            'reasons' => $reasons,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Sinkronisasi Satu Config
    |--------------------------------------------------------------------------
    */

    public function synchronize(
        int $configId,
        AssessmentService $service
    ): void {
        abort_unless(
            auth()
                ->user()
                ->can(
                    'assessment_sync.manage'
                ),
            403
        );

        $config =
            AssessmentConfig::query()
                ->with([
                    'items.factor',
                ])
                ->findOrFail(
                    $configId
                );

        /*
        |--------------------------------------------------------------------------
        | Jangan mengubah config periode lain dari filter yang sedang dibuka.
        |--------------------------------------------------------------------------
        */

        if (
            (int) $config
                ->academic_year_id
            !==
            (int) $this
                ->academicYearId
            ||
            (int) $config
                ->semester_id
            !==
            (int) $this
                ->semesterId
        ) {
            throw ValidationException::withMessages([
                'sync' => 'Konfigurasi penilaian tidak sesuai '
                    .'dengan periode yang sedang dipilih.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Config nonaktif tidak dihitung ulang otomatis.
        |--------------------------------------------------------------------------
        */

        if (
            ! $config->is_active
        ) {
            throw ValidationException::withMessages([
                'sync' => 'Konfigurasi penilaian tidak aktif. '
                    .'Aktifkan konfigurasi terlebih dahulu '
                    .'sebelum melakukan sinkronisasi.',
            ]);
        }

        $before =
            $this->statusForConfig(
                $config,
                $service
            );

        if (
            ! $before[
                'is_stale'
            ]
        ) {
            session()->flash(
                'status',
                'Konfigurasi tersebut sudah sinkron.'
            );

            return;
        }

        $result =
            $service
                ->syncAllScores(
                    $config
                );

        $attendanceUpdated =
            (int) (
                $result[
                    'attendance_scores'
                ]
                ?? 0
            );

        $finalUpdated =
            (int) (
                $result[
                    'final_grades'
                ]
                ?? 0
            );

        session()->flash(
            'status',
            'Sinkronisasi selesai. '
            .number_format(
                $attendanceUpdated
            )
            .' nilai kehadiran dan '
            .number_format(
                $finalUpdated
            )
            .' nilai akhir diperbarui.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Sinkronisasi Semua Config Aktif
    |--------------------------------------------------------------------------
    */

    public function synchronizeAll(
        AssessmentService $service
    ): void {
        abort_unless(
            auth()
                ->user()
                ->can(
                    'assessment_sync.manage'
                ),
            403
        );

        $configs =
            $this
                ->configs()
                ->where(
                    'is_active',
                    true
                );

        if (
            $configs->isEmpty()
        ) {
            throw ValidationException::withMessages([
                'sync' => 'Tidak ada konfigurasi penilaian aktif '
                    .'pada periode yang dipilih.',
            ]);
        }

        $syncedConfigs = 0;

        $attendanceUpdated = 0;

        $finalUpdated = 0;

        foreach (
            $configs as $config
        ) {
            $status =
                $this->statusForConfig(
                    $config,
                    $service
                );

            if (
                ! $status[
                    'is_stale'
                ]
            ) {
                continue;
            }

            $result =
                $service
                    ->syncAllScores(
                        $config
                    );

            $attendanceUpdated +=
                (int) (
                    $result[
                        'attendance_scores'
                    ]
                    ?? 0
                );

            $finalUpdated +=
                (int) (
                    $result[
                        'final_grades'
                    ]
                    ?? 0
                );

            $syncedConfigs++;
        }

        if (
            $syncedConfigs === 0
        ) {
            session()->flash(
                'status',
                'Semua konfigurasi pada periode ini sudah sinkron.'
            );

            return;
        }

        session()->flash(
            'status',
            'Sinkronisasi selesai untuk '
            .number_format(
                $syncedConfigs
            )
            .' konfigurasi. '
            .number_format(
                $attendanceUpdated
            )
            .' nilai kehadiran dan '
            .number_format(
                $finalUpdated
            )
            .' nilai akhir diperbarui.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Render
    |--------------------------------------------------------------------------
    */

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

        $service =
            app(
                AssessmentService::class
            );

        $configs =
            $this
                ->configs()
                ->map(
                    function (
                        AssessmentConfig $config
                    ) use (
                        $service
                    ): AssessmentConfig {
                        $config->setAttribute(
                            'sync_status',
                            $this->statusForConfig(
                                $config,
                                $service
                            )
                        );

                        return $config;
                    }
                );

        if (
            $this->onlyStale
        ) {
            $configs =
                $configs
                    ->filter(
                        fn (
                            AssessmentConfig $config
                        ): bool => (
                            $config
                                ->getAttribute(
                                    'sync_status'
                                )[
                                    'is_stale'
                                ]
                            ?? false
                        )
                    )
                    ->values();
        }

        $totalConfigs =
            $configs->count();

        $staleConfigs =
            $configs
                ->filter(
                    fn (
                        AssessmentConfig $config
                    ): bool => (
                        $config
                            ->getAttribute(
                                'sync_status'
                            )[
                                'is_stale'
                            ]
                        ?? false
                    )
                )
                ->count();

        $staleAttendance =
            $configs
                ->sum(
                    fn (
                        AssessmentConfig $config
                    ): int => (int) (
                        $config
                            ->getAttribute(
                                'sync_status'
                            )[
                                'attendance'
                            ][
                                'stale_count'
                            ]
                        ?? 0
                    )
                );

        $staleFinal =
            $configs
                ->sum(
                    fn (
                        AssessmentConfig $config
                    ): int => (int) (
                        $config
                            ->getAttribute(
                                'sync_status'
                            )[
                                'final'
                            ][
                                'stale_count'
                            ]
                        ?? 0
                    )
                );

        return view(
            'livewire.assessments.synchronization.index',
            [
                'academicYears' => $academicYears,

                'semesters' => $semesters,

                'configs' => $configs,

                'totalConfigs' => $totalConfigs,

                'staleConfigs' => $staleConfigs,

                'staleAttendance' => $staleAttendance,

                'staleFinal' => $staleFinal,
            ]
        );
    }
}
