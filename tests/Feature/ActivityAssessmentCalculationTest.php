<?php

namespace Tests\Feature;

use App\Models\ActivityAssessment;
use App\Models\ActivityAssessmentCriterion;
use App\Models\ActivityAssessmentTarget;
use App\Models\AssessmentConfig;
use App\Models\AssessmentConfigItem;
use App\Models\School;
use App\Models\ScoutLevel;
use App\Models\ScoutUnit;
use App\Models\Student;
use App\Models\StudentScore;
use App\Services\ActivityAssessmentService;
use App\Support\SchoolContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ActivityAssessmentCalculationTest extends TestCase
{
    use RefreshDatabase;

    /*
    |--------------------------------------------------------------------------
    | Helper School Context
    |--------------------------------------------------------------------------
    */

    protected function activateSchool(
        School $school
    ): void {
        $context =
            app(
                SchoolContext::class
            );

        $context->clear();

        $context->set(
            $school
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Buat Criterion
    |--------------------------------------------------------------------------
    */

    protected function criterion(
        ActivityAssessment $assessment,
        string $name,
        float $weight,
        float $maxScore = 100
    ): ActivityAssessmentCriterion {
        return $assessment
            ->criteria()
            ->create([
                'name' => $name,

                'max_score' => $maxScore,

                'weight' => $weight,

                'sort_order' => (
                    (int) $assessment
                        ->criteria()
                        ->max(
                            'sort_order'
                        )
                ) + 1,
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Bobot total harus 100
    |--------------------------------------------------------------------------
    */

    public function test_assessment_cannot_be_published_when_criteria_weight_is_not_100(): void
    {
        $school =
            School::factory()
                ->create([
                    'is_active' => true,
                ]);

        $this->activateSchool(
            $school
        );

        $assessment =
            ActivityAssessment::factory()
                ->create([
                    'school_id' => $school->id,

                    'status' => 'draft',
                ]);

        $this->criterion(
            $assessment,
            'Ketepatan',
            40
        );

        $this->criterion(
            $assessment,
            'Kerapian',
            30
        );

        $this->expectException(
            ValidationException::class
        );

        app(
            ActivityAssessmentService::class
        )->validateForPublish(
            $assessment
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Bobot tepat 100 diterima
    |--------------------------------------------------------------------------
    */

    public function test_assessment_accepts_criteria_when_total_weight_is_exactly_100(): void
    {
        $school =
            School::factory()
                ->create([
                    'is_active' => true,
                ]);

        $this->activateSchool(
            $school
        );

        $assessment =
            ActivityAssessment::factory()
                ->create([
                    'school_id' => $school->id,

                    'status' => 'draft',
                ]);

        $this->criterion(
            $assessment,
            'Ketepatan',
            35
        );

        $this->criterion(
            $assessment,
            'Kerapian',
            20
        );

        $this->criterion(
            $assessment,
            'Kecepatan',
            20
        );

        $this->criterion(
            $assessment,
            'Pemahaman',
            15
        );

        $this->criterion(
            $assessment,
            'Keselamatan',
            10
        );

        app(
            ActivityAssessmentService::class
        )->validateForPublish(
            $assessment
        );

        $this->assertSame(
            100.0,
            (float) $assessment
                ->criteria()
                ->sum(
                    'weight'
                )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Normalisasi 0-100
    |--------------------------------------------------------------------------
    */

    public function test_target_score_is_normalized_using_criterion_weights(): void
    {
        $school =
            School::factory()
                ->create([
                    'is_active' => true,
                ]);

        $this->activateSchool(
            $school
        );

        $assessment =
            ActivityAssessment::factory()
                ->published()
                ->create([
                    'school_id' => $school->id,

                    'mode' => 'individual',
                ]);

        $criterionA =
            $this->criterion(
                $assessment,
                'Ketepatan Simpul',
                35
            );

        $criterionB =
            $this->criterion(
                $assessment,
                'Kerapian',
                20
            );

        $criterionC =
            $this->criterion(
                $assessment,
                'Kecepatan',
                20
            );

        $criterionD =
            $this->criterion(
                $assessment,
                'Pemahaman Fungsi',
                15
            );

        $criterionE =
            $this->criterion(
                $assessment,
                'Keselamatan',
                10
            );

        /*
        |--------------------------------------------------------------------------
        | Kita hanya menguji engine perhitungan target.
        |
        | student_id tidak dibutuhkan pada test matematis ini.
        |--------------------------------------------------------------------------
        */

        $target =
            ActivityAssessmentTarget::query()
                ->create([
                    'activity_assessment_id' => $assessment->id,

                    'total_score' => 0,

                    'normalized_score' => 0,
                ]);

        app(
            ActivityAssessmentService::class
        )->saveTargetScores(
            $target,
            [
                $criterionA->id => 90,

                $criterionB->id => 80,

                $criterionC->id => 70,

                $criterionD->id => 90,

                $criterionE->id => 100,
            ]
        );

        $target->refresh();

        /*
        |--------------------------------------------------------------------------
        | Manual:
        |
        | 90 × 35% = 31.50
        | 80 × 20% = 16.00
        | 70 × 20% = 14.00
        | 90 × 15% = 13.50
        | 100 ×10% = 10.00
        |
        | Total = 85.00
        |--------------------------------------------------------------------------
        */

        $this->assertEqualsWithDelta(
            85.00,
            (float) $target
                ->normalized_score,
            0.001
        );

        $this->assertNotNull(
            $target->assessed_at
        );

        $this->assertDatabaseCount(
            'activity_assessment_scores',
            5
        );
    }

    /*
    |--------------------------------------------------------------------------
    | max_score tidak harus 100
    |--------------------------------------------------------------------------
    */

    public function test_normalization_supports_custom_maximum_scores(): void
    {
        $school =
            School::factory()
                ->create([
                    'is_active' => true,
                ]);

        $this->activateSchool(
            $school
        );

        $assessment =
            ActivityAssessment::factory()
                ->published()
                ->create([
                    'school_id' => $school->id,
                ]);

        $criterionA =
            $this->criterion(
                $assessment,
                'Praktik',
                50,
                10
            );

        $criterionB =
            $this->criterion(
                $assessment,
                'Teori',
                50,
                20
            );

        $target =
            ActivityAssessmentTarget::query()
                ->create([
                    'activity_assessment_id' => $assessment->id,

                    'total_score' => 0,

                    'normalized_score' => 0,
                ]);

        /*
        |--------------------------------------------------------------------------
        | Praktik:
        | 8 / 10 × 50 = 40
        |
        | Teori:
        | 16 / 20 × 50 = 40
        |
        | Total:
        | 80
        |--------------------------------------------------------------------------
        */

        app(
            ActivityAssessmentService::class
        )->saveTargetScores(
            $target,
            [
                $criterionA->id => 8,

                $criterionB->id => 16,
            ]
        );

        $target->refresh();

        $this->assertEqualsWithDelta(
            80.00,
            (float) $target
                ->normalized_score,
            0.001
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Score tidak boleh lebih besar max_score
    |--------------------------------------------------------------------------
    */

    public function test_score_cannot_exceed_criterion_max_score(): void
    {
        $school =
            School::factory()
                ->create([
                    'is_active' => true,
                ]);

        $this->activateSchool(
            $school
        );

        $assessment =
            ActivityAssessment::factory()
                ->published()
                ->create([
                    'school_id' => $school->id,
                ]);

        $criterion =
            $this->criterion(
                $assessment,
                'Praktik',
                100,
                10
            );

        $target =
            ActivityAssessmentTarget::query()
                ->create([
                    'activity_assessment_id' => $assessment->id,

                    'total_score' => 0,

                    'normalized_score' => 0,
                ]);

        $this->expectException(
            ValidationException::class
        );

        app(
            ActivityAssessmentService::class
        )->saveTargetScores(
            $target,
            [
                $criterion->id => 11,
            ]
        );
    }

    public function test_published_activity_scores_are_averaged_into_student_scores(): void
    {
        $school =
            School::factory()
                ->create([
                    'is_active' => true,
                ]);

        $this->activateSchool(
            $school
        );

        $assessment =
            ActivityAssessment::factory()
                ->published()
                ->individual()
                ->create([
                    'school_id' => $school->id,
                ]);

        $config =
            AssessmentConfig::query()
                ->create([
                    'academic_year_id' => $assessment->activity->academic_year_id,

                    'semester_id' => $assessment->activity->semester_id,

                    'name' => 'Rekap kegiatan',

                    'is_active' => true,
                ]);

        AssessmentConfigItem::query()
            ->create([
                'assessment_config_id' => $config->id,

                'assessment_factor_id' => $assessment->assessment_factor_id,

                'weight' => 100,

                'sort_order' => 1,
            ]);

        $student =
            Student::query()
                ->create([
                    'nis' => 'ACTIVITY-SCORE-001',

                    'name' => 'Siswa Penilaian Kegiatan',

                    'status' => 'active',
                ]);

        $assessment->targets()
            ->create([
                'student_id' => $student->id,

                'total_score' => 75,

                'normalized_score' => 75,

                'assessed_at' => now(),
            ]);

        $updated =
            app(
                ActivityAssessmentService::class
            )->syncToStudentScores(
                $assessment
            );

        $studentScore =
            StudentScore::query()
                ->where('assessment_config_id', $config->id)
                ->where('student_id', $student->id)
                ->where('assessment_factor_id', $assessment->assessment_factor_id)
                ->firstOrFail();

        expect($updated)->toBe(1);
        expect((float) $studentScore->score)->toBe(75.0);
        expect($studentScore->source)->toBe('activity_assessment');
    }

    public function test_team_targets_only_include_units_from_the_activity_scout_levels(): void
    {
        $school = School::factory()->create([
            'is_active' => true,
        ]);
        $this->activateSchool($school);
        $siaga = ScoutLevel::query()->create([
            'code' => 'SIAGA-TARGET',
            'name' => 'Siaga Target',
            'sort_order' => 1,
        ]);
        $penggalang = ScoutLevel::query()->create([
            'code' => 'PENGGALANG-TARGET',
            'name' => 'Penggalang Target',
            'sort_order' => 2,
        ]);
        $assessment = ActivityAssessment::factory()
            ->team()
            ->create([
                'school_id' => $school->id,
            ]);
        $assessment->activity->scoutLevels()->attach($penggalang);
        $siagaUnit = ScoutUnit::query()->create([
            'scout_level_id' => $siaga->id,
            'academic_year_id' => $assessment->activity->academic_year_id,
            'name' => 'Barung Merah',
            'unit_type' => 'barung',
            'is_active' => true,
        ]);
        $penggalangUnit = ScoutUnit::query()->create([
            'scout_level_id' => $penggalang->id,
            'academic_year_id' => $assessment->activity->academic_year_id,
            'name' => 'Regu Elang',
            'unit_type' => 'regu',
            'is_active' => true,
        ]);

        $created = app(ActivityAssessmentService::class)
            ->prepareTargets($assessment);

        expect($created)->toBe(1);
        expect($assessment->targets()->pluck('scout_unit_id')->all())
            ->toBe([$penggalangUnit->id]);
        expect($assessment->targets()->where('scout_unit_id', $siagaUnit->id)->exists())
            ->toBeFalse();
    }
}
