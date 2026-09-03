<?php

namespace Tests\Feature;

use App\Models\ActivityAssessment;
use App\Models\School;
use App\Support\SchoolContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ActivityAssessmentTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_activity_assessments_are_isolated_between_schools(): void
    {
        $schoolA =
            School::factory()
                ->create([
                    'is_active' => true,
                ]);

        $schoolB =
            School::factory()
                ->create([
                    'is_active' => true,
                ]);

        /*
        |--------------------------------------------------------------------------
        | School A
        |--------------------------------------------------------------------------
        */

        $this->activateSchool(
            $schoolA
        );

        $assessmentA =
            ActivityAssessment::factory()
                ->create([
                    'school_id' => $schoolA->id,

                    'title' => 'Penilaian Sekolah A',
                ]);

        /*
        |--------------------------------------------------------------------------
        | School B
        |--------------------------------------------------------------------------
        */

        $this->activateSchool(
            $schoolB
        );

        $assessmentB =
            ActivityAssessment::factory()
                ->create([
                    'school_id' => $schoolB->id,

                    'title' => 'Penilaian Sekolah B',
                ]);

        /*
        |--------------------------------------------------------------------------
        | Raw database = 2
        |--------------------------------------------------------------------------
        */

        $this->assertSame(
            2,
            DB::table(
                'activity_assessments'
            )->count()
        );

        /*
        |--------------------------------------------------------------------------
        | Context B hanya dapat melihat B
        |--------------------------------------------------------------------------
        */

        $visibleToB =
            ActivityAssessment::query()
                ->get();

        $this->assertCount(
            1,
            $visibleToB
        );

        $this->assertTrue(
            $visibleToB
                ->contains(
                    'id',
                    $assessmentB->id
                )
        );

        $this->assertFalse(
            $visibleToB
                ->contains(
                    'id',
                    $assessmentA->id
                )
        );

        /*
        |--------------------------------------------------------------------------
        | Context A hanya dapat melihat A
        |--------------------------------------------------------------------------
        */

        $this->activateSchool(
            $schoolA
        );

        $visibleToA =
            ActivityAssessment::query()
                ->get();

        $this->assertCount(
            1,
            $visibleToA
        );

        $this->assertTrue(
            $visibleToA
                ->contains(
                    'id',
                    $assessmentA->id
                )
        );

        $this->assertFalse(
            $visibleToA
                ->contains(
                    'id',
                    $assessmentB->id
                )
        );
    }

    public function test_find_or_fail_cannot_find_assessment_from_another_school(): void
    {
        $schoolA =
            School::factory()
                ->create([
                    'is_active' => true,
                ]);

        $schoolB =
            School::factory()
                ->create([
                    'is_active' => true,
                ]);

        /*
        |--------------------------------------------------------------------------
        | Buat form School A
        |--------------------------------------------------------------------------
        */

        $this->activateSchool(
            $schoolA
        );

        $assessmentA =
            ActivityAssessment::factory()
                ->create([
                    'school_id' => $schoolA->id,
                ]);

        /*
        |--------------------------------------------------------------------------
        | Switch B
        |--------------------------------------------------------------------------
        */

        $this->activateSchool(
            $schoolB
        );

        $this->assertNull(
            ActivityAssessment::query()
                ->find(
                    $assessmentA->id
                )
        );
    }
}
