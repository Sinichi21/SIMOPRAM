<?php

namespace App\Livewire\Settings;

use App\Models\AssessmentConfig;
use App\Models\AttendanceScoreSetting;
use App\Services\AssessmentService;
use App\Services\AttendanceWeightService;
use Livewire\Component;

class AttendanceScoring extends Component
{
    public float $presentWeight = 100;

    public float $lateWeight = 75;

    public float $sickWeight = 75;

    public float $excusedWeight = 75;

    public float $absentWeight = 0;

    public int $currentVersion = 1;

    public int $staleScoreCount = 0;

    public int $staleFinalGradeCount = 0;

    public bool $hasStaleFinalGrades = false;

    public bool $hasStaleScores = false;

    public function mount(): void
    {
        $this->loadCurrentWeights();

        $this->refreshSyncStatus();
    }

    protected function loadCurrentWeights(): void
    {
        $weightService =
            app(
                AttendanceWeightService::class
            );

        $weights =
            $weightService
                ->percentages();

        $this->presentWeight =
            (float) $weights[
                'present'
            ];

        $this->lateWeight =
            (float) $weights[
                'late'
            ];

        $this->sickWeight =
            (float) $weights[
                'sick'
            ];

        $this->excusedWeight =
            (float) $weights[
                'excused'
            ];

        $this->absentWeight =
            (float) $weights[
                'absent'
            ];

        $this->currentVersion =
            $weightService
                ->version();
    }

    public function save(
        AttendanceWeightService $weightService
    ): void {
        abort_unless(
            auth()->user()->can(
                'attendance_score_settings.manage'
            ),
            403
        );

        $validated =
            $this->validate([
                'presentWeight' => [
                    'required',
                    'numeric',
                    'min:0',
                    'max:100',
                ],

                'lateWeight' => [
                    'required',
                    'numeric',
                    'min:0',
                    'max:100',
                ],

                'sickWeight' => [
                    'required',
                    'numeric',
                    'min:0',
                    'max:100',
                ],

                'excusedWeight' => [
                    'required',
                    'numeric',
                    'min:0',
                    'max:100',
                ],

                'absentWeight' => [
                    'required',
                    'numeric',
                    'min:0',
                    'max:100',
                ],
            ]);

        $previousVersion =
            $weightService
                ->version();

        $setting =
            $weightService
                ->savePercentages(
                    [
                        'present' => $validated[
                                'presentWeight'
                            ],

                        'late' => $validated[
                                'lateWeight'
                            ],

                        'sick' => $validated[
                                'sickWeight'
                            ],

                        'excused' => $validated[
                                'excusedWeight'
                            ],

                        'absent' => $validated[
                                'absentWeight'
                            ],
                    ],
                    auth()->id()
                );

        $this->currentVersion =
            (int) $setting
                ->version;

        $this->refreshSyncStatus();

        if (
            $this->currentVersion
            > $previousVersion
        ) {
            session()->flash(
                'status',
                'Bobot kehadiran berhasil diubah. Nilai otomatis lama perlu dihitung ulang.'
            );

            return;
        }

        session()->flash(
            'status',
            'Pengaturan bobot kehadiran berhasil disimpan.'
        );
    }

    public function resetDefaults(): void
    {
        abort_unless(
            auth()->user()->can(
                'attendance_score_settings.manage'
            ),
            403
        );

        $defaults =
            AttendanceScoreSetting::defaultWeights();

        $this->presentWeight =
            $defaults['present'];

        $this->lateWeight =
            $defaults['late'];

        $this->sickWeight =
            $defaults['sick'];

        $this->excusedWeight =
            $defaults['excused'];

        $this->absentWeight =
            $defaults['absent'];

        session()->flash(
            'status',
            'Nilai default telah dimuat. Klik Simpan Pengaturan untuk menerapkannya.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Hitung ulang nilai kehadiran
    |--------------------------------------------------------------------------
    */

    protected function refreshSyncStatus(): void
    {
        $assessmentService =
            app(
                AssessmentService::class
            );

        $configs =
            AssessmentConfig::query()
                ->where(
                    'is_active',
                    true
                )
                ->with([
                    'items.factor',
                ])
                ->get();

        $staleAttendance =
            0;

        $staleFinalGrades =
            0;

        foreach (
            $configs as $config
        ) {

            $attendanceStatus =
                $assessmentService
                    ->attendanceSyncStatus(
                        $config
                    );

            $finalGradeStatus =
                $assessmentService
                    ->finalGradeSyncStatus(
                        $config
                    );

            $staleAttendance +=
                $attendanceStatus[
                    'stale_count'
                ];

            $staleFinalGrades +=
                $finalGradeStatus[
                    'stale_count'
                ];
        }

        $this->staleScoreCount =
            $staleAttendance;

        $this->hasStaleScores =
            $staleAttendance > 0;

        $this->staleFinalGradeCount =
            $staleFinalGrades;

        $this->hasStaleFinalGrades =
            $staleFinalGrades > 0;

        $this->currentVersion =
            app(
                AttendanceWeightService::class
            )->version();
    }

    public function render()
    {
        return view(
            'livewire.settings.attendance-scoring'
        );
    }
}
