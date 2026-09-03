<?php

namespace Tests\Feature;

use App\Models\ActivityAssessment;
use App\Models\ActivityAssessmentCriterion;
use App\Models\ActivityAssessmentTarget;
use App\Models\School;
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
}
