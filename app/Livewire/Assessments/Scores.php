<?php

namespace App\Livewire\Assessments;

use App\Models\AssessmentConfig;
use App\Models\FinalGrade;
use App\Models\Student;
use App\Models\StudentScore;
use App\Services\AssessmentService;
use Livewire\Component;

class Scores extends Component
{
    public ?int $configId = null;

    public string $search = '';

    public array $scores = [];


    public function mount(): void
    {
        $config =
            AssessmentConfig::query()
                ->where(
                    'is_active',
                    true
                )
                ->latest()
                ->first();

        $this->configId =
            $config?->id;

        $this->loadScores();
    }


    public function updatedConfigId(): void
    {
        $this->loadScores();
    }


    protected function loadScores(): void
    {
        $this->scores = [];

        if (! $this->configId) {
            return;
        }


        $existingScores =
            StudentScore::query()
                ->where(
                    'assessment_config_id',
                    $this->configId
                )
                ->get();


        foreach (
            $existingScores
            as $score
        ) {
            $this->scores[
                $score->student_id
            ][
                $score->assessment_factor_id
            ] =
                (float)
                $score->score;
        }
    }


    public function refreshAttendanceScores(
        AssessmentService $service
    ): void {
        abort_unless(
            auth()->user()->can(
                'assessments.calculate'
            ),
            403
        );


        $config =
            AssessmentConfig::query()
                ->with('items.factor')
                ->findOrFail(
                    $this->configId
                );


        $students =
            Student::query()
                ->where(
                    'status',
                    'active'
                )
                ->get();


        foreach ($students as $student) {
            $service
                ->syncAutomaticScores(
                    $config,
                    $student
                );
        }


        $this->loadScores();


        session()->flash(
            'success',
            'Nilai kehadiran berhasil diperbarui dari data absensi.'
        );
    }


    public function saveStudent(
        int $studentId,
        AssessmentService $service
    ): void {
        abort_unless(
            auth()->user()->can(
                'assessments.scores.manage'
            ),
            403
        );


        $config =
            AssessmentConfig::query()
                ->with('items.factor')
                ->findOrFail(
                    $this->configId
                );


        $student =
            Student::query()
                ->findOrFail(
                    $studentId
                );


        foreach (
            $config->items
            as $item
        ) {

            if (
                $item->factor
                    ->source_type !==
                'manual'
            ) {
                continue;
            }


            $value =
                $this->scores[
                    $student->id
                ][
                    $item
                        ->assessment_factor_id
                ]
                ?? null;


            if (
                $value === null
                ||
                $value === ''
            ) {
                $this->addError(
                    "scores.{$student->id}.{$item->assessment_factor_id}",
                    'Nilai wajib diisi.'
                );

                return;
            }


            $service->saveManualScore(
                $config,
                $student,
                $item
                    ->assessment_factor_id,
                (float) $value
            );
        }


        $service->syncAutomaticScores(
            $config,
            $student
        );


        session()->flash(
            'success',
            "Nilai {$student->name} berhasil disimpan."
        );


        $this->loadScores();
    }


    public function calculateStudent(
        int $studentId,
        AssessmentService $service
    ): void {
        abort_unless(
            auth()->user()->can(
                'assessments.calculate'
            ),
            403
        );


        $config =
            AssessmentConfig::query()
                ->with('items.factor')
                ->findOrFail(
                    $this->configId
                );


        $student =
            Student::query()
                ->findOrFail(
                    $studentId
                );


        $service->calculateFinalGrade(
            $config,
            $student
        );


        session()->flash(
            'success',
            "Nilai akhir {$student->name} berhasil dihitung."
        );
    }


    public function calculateAll(
        AssessmentService $service
    ): void {
        abort_unless(
            auth()->user()->can(
                'assessments.calculate'
            ),
            403
        );


        $config =
            AssessmentConfig::query()
                ->with('items.factor')
                ->findOrFail(
                    $this->configId
                );


        $students =
            Student::query()
                ->where(
                    'status',
                    'active'
                )
                ->get();


        $success = 0;
        $failed = 0;


        foreach ($students as $student) {
            try {
                $service
                    ->calculateFinalGrade(
                        $config,
                        $student
                    );

                $success++;
            } catch (\Throwable) {
                $failed++;
            }
        }


        session()->flash(
            'success',
            "Perhitungan selesai. Berhasil: {$success}, belum lengkap: {$failed}."
        );
    }


    public function render()
    {
        $configs =
            AssessmentConfig::query()
                ->with([
                    'academicYear',
                    'semester',
                ])
                ->orderByDesc(
                    'is_active'
                )
                ->latest()
                ->get();


        $selectedConfig =
            $this->configId
                ? AssessmentConfig::query()
                    ->with([
                        'items.factor',
                    ])
                    ->find(
                        $this->configId
                    )
                : null;


        $students =
            Student::query()
                ->where(
                    'status',
                    'active'
                )
                ->when(
                    $selectedConfig,
                    fn ($query) =>
                        $query->whereHas(
                            'enrollments',
                            fn ($enrollment) =>
                                $enrollment->where(
                                    'academic_year_id',
                                    $selectedConfig
                                        ->academic_year_id
                                )
                        )
                )
                ->when(
                    $this->search,
                    function ($query): void {
                        $search =
                            '%'
                            . trim($this->search)
                            . '%';

                        $query->where(
                            function ($query) use ($search): void {
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


        $finalGrades =
            $this->configId
                ? FinalGrade::query()
                    ->where(
                        'assessment_config_id',
                        $this->configId
                    )
                    ->get()
                    ->keyBy(
                        'student_id'
                    )
                : collect();


        return view(
            'livewire.assessments.scores',
            compact(
                'configs',
                'selectedConfig',
                'students',
                'finalGrades'
            )
        );
    }
}