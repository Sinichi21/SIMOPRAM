<?php

namespace App\Services;

use App\Models\AssessmentConfig;
use App\Models\FinalGrade;
use App\Models\GradeScaleConfig;
use App\Models\Student;
use App\Models\StudentScore;
use App\Support\SchoolContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssessmentService
{
    /*
    |--------------------------------------------------------------------------
    | Bobot status kehadiran
    |--------------------------------------------------------------------------
    */

    protected array $attendanceWeights = [
        'present' => 1.00,
        'late' => 0.75,
        'sick' => 0.75,
        'excused' => 0.75,
        'absent' => 0.00,
    ];


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
                    'session.attendances' =>
                        fn ($query) =>
                            $query->where(
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
                        'assessment_config_id' =>
                            $config->id,

                        'student_id' =>
                            $student->id,

                        'assessment_factor_id' =>
                            $factor->id,
                    ],
                    [
                        'score' =>
                            $score,

                        'source' =>
                            'attendance',

                        'entered_by' =>
                            null,
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
                'scores' =>
                    'Nilai harus antara 0 sampai 100.',
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
                'scores' =>
                    'Faktor otomatis tidak dapat diisi manual.',
            ]);
        }


        return StudentScore::query()
            ->updateOrCreate(
                [
                    'assessment_config_id' =>
                        $config->id,

                    'student_id' =>
                        $student->id,

                    'assessment_factor_id' =>
                        $factorId,
                ],
                [
                    'score' =>
                        round(
                            $score,
                            2
                        ),

                    'source' =>
                        'manual',

                    'entered_by' =>
                        auth()->id(),

                    'notes' =>
                        $notes,
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
                    'scores' =>
                        'Nilai '
                        . $item->factor->name
                        . ' untuk '
                        . $student->name
                        . ' belum diisi.',
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
            fn () =>
                FinalGrade::query()
                    ->updateOrCreate(
                        [
                            'assessment_config_id' =>
                                $config->id,

                            'student_id' =>
                                $student->id,
                        ],
                        [
                            'final_score' =>
                                $finalScore,

                            'letter_grade' =>
                                $grade[
                                    'letter_grade'
                                ] ?? null,

                            'description' =>
                                $grade[
                                    'description'
                                ] ?? null,

                            'calculated_at' =>
                                now(),

                            'calculated_by' =>
                                auth()->id(),
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
                'letter_grade' =>
                    null,

                'description' =>
                    null,
            ];
        }


        $scale =
            $config
                ->scales
                ->first(
                    fn ($scale) =>
                        $score >=
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
            'letter_grade' =>
                $scale
                    ?->letter_grade,

            'description' =>
                $scale
                    ?->description,
        ];
    }
}