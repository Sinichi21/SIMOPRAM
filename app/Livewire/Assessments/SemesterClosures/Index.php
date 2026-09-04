<?php

namespace App\Livewire\Assessments\SemesterClosures;

use App\Models\AcademicYear;
use App\Models\AssessmentConfig;
use App\Models\Semester;
use App\Models\SemesterClosure;
use App\Services\AssessmentService;
use App\Services\SemesterClosureService;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Index extends Component
{
    public ?int $academicYearId = null;

    public ?int $semesterId = null;

    public ?int $reopenClosureId = null;

    public string $reopenReason = '';

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

        if (
            ! $this->academicYearId
        ) {
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

    public function updatedAcademicYearId(): void
    {
        $this->semesterId =
            null;

        $this->cancelReopen();

        if (
            ! $this->academicYearId
        ) {
            return;
        }

        $this->semesterId =
            Semester::query()
                ->where(
                    'academic_year_id',
                    $this->academicYearId
                )
                ->where(
                    'is_active',
                    true
                )
                ->value(
                    'id'
                );
    }

    public function updatedSemesterId(): void
    {
        $this->cancelReopen();
    }

    protected function activeConfig(): ?AssessmentConfig
    {
        if (
            ! $this->academicYearId
            ||
            ! $this->semesterId
        ) {
            return null;
        }

        return AssessmentConfig::query()
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

    public function lockSemester(
        SemesterClosureService $service
    ): void {
        abort_unless(
            auth()
                ->user()
                ->can(
                    'semester_closures.manage'
                ),
            403
        );

        $config =
            $this->activeConfig();

        if (! $config) {
            throw ValidationException::withMessages([
                'semester' => 'Konfigurasi penilaian aktif tidak ditemukan '
                    .'untuk periode yang dipilih.',
            ]);
        }

        $closure =
            $service->lock(
                $config
            );

        session()->flash(
            'status',
            'Semester berhasil dikunci sebagai versi '
            .$closure->version
            .'. '
            .number_format(
                $closure->snapshot_count
            )
            .' snapshot nilai resmi dibuat.'
        );
    }

    public function startReopen(
        int $closureId
    ): void {
        abort_unless(
            auth()
                ->user()
                ->can(
                    'semester_closures.manage'
                ),
            403
        );

        $closure =
            SemesterClosure::query()
                ->findOrFail(
                    $closureId
                );

        $this->reopenClosureId =
            $closure->id;

        $this->reopenReason =
            '';

        $this->resetValidation();
    }

    public function cancelReopen(): void
    {
        $this->reopenClosureId =
            null;

        $this->reopenReason =
            '';

        $this->resetValidation();
    }

    public function reopenSemester(
        SemesterClosureService $service
    ): void {
        abort_unless(
            auth()
                ->user()
                ->can(
                    'semester_closures.manage'
                ),
            403
        );

        if (
            ! $this->reopenClosureId
        ) {
            return;
        }

        $this->validate([
            'reopenReason' => [
                'required',
                'string',
                'min:5',
                'max:2000',
            ],
        ]);

        $closure =
            SemesterClosure::query()
                ->findOrFail(
                    $this->reopenClosureId
                );

        $service->reopen(
            $closure,
            $this->reopenReason
        );

        $this->cancelReopen();

        session()->flash(
            'status',
            'Semester berhasil dibuka kembali. '
            .'Snapshot versi sebelumnya tetap disimpan sebagai histori.'
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

        $config =
            $this->activeConfig();

        $syncStatus =
            null;

        if ($config) {
            $assessmentService =
                app(
                    AssessmentService::class
                );

            $syncStatus = [
                'attendance' => $assessmentService
                    ->attendanceSyncStatus(
                        $config
                    ),

                'final' => $assessmentService
                    ->finalGradeSyncStatus(
                        $config
                    ),
            ];
        }

        $history =
            collect();

        if (
            $this->academicYearId
            &&
            $this->semesterId
        ) {
            $history =
                SemesterClosure::query()
                    ->with([
                        'locker',
                        'reopener',
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
                        'version'
                    )
                    ->get();
        }

        $currentClosure =
            $history->first();

        $isLocked =
            $currentClosure
                ?->isLocked()
                ?? false;

        $canLock =
            $config
            &&
            ! $isLocked
            &&
            ! (
                $syncStatus[
                    'attendance'
                ][
                    'is_stale'
                ]
                ?? true
            )
            &&
            ! (
                $syncStatus[
                    'final'
                ][
                    'is_stale'
                ]
                ?? true
            );

        return view(
            'livewire.assessments.semester-closures.index',
            [
                'academicYears' => $academicYears,

                'semesters' => $semesters,

                'config' => $config,

                'syncStatus' => $syncStatus,

                'history' => $history,

                'currentClosure' => $currentClosure,

                'isLocked' => $isLocked,

                'canLock' => $canLock,
            ]
        );
    }
}
