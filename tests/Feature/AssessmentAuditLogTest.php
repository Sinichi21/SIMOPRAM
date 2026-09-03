<?php

namespace Tests\Feature;

use App\Models\AssessmentAuditLog;
use App\Models\School;
use App\Models\User;
use App\Services\AssessmentAuditService;
use App\Support\SchoolContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class AssessmentAuditLogTest extends TestCase
{
    use RefreshDatabase;

    /*
    |--------------------------------------------------------------------------
    | Helper
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
    | Audit membutuhkan School Context
    |--------------------------------------------------------------------------
    */

    public function test_audit_cannot_be_recorded_without_school_context(): void
    {
        app(
            SchoolContext::class
        )->clear();

        try {
            app(
                AssessmentAuditService::class
            )->record(
                action: 'test.action',

                description: 'Testing audit tanpa sekolah.',

                module: 'test'
            );

            $this->fail(
                'Audit seharusnya tidak dapat dibuat tanpa School Context.'
            );
        } catch (
            HttpException $exception
        ) {
            $this->assertSame(
                409,
                $exception->getStatusCode()
            );
        }

        $this->assertDatabaseCount(
            'assessment_audit_logs',
            0
        );
    }

    /*
    |--------------------------------------------------------------------------
    | school_id otomatis dari context
    |--------------------------------------------------------------------------
    */

    public function test_audit_automatically_uses_current_school(): void
    {
        $school =
            School::factory()
                ->create([
                    'is_active' => true,
                ]);

        $this->activateSchool(
            $school
        );

        $log =
            app(
                AssessmentAuditService::class
            )->record(
                action: 'assessment.test',

                description: 'Audit tenant test.',

                module: 'assessment'
            );

        $this->assertSame(
            $school->id,
            $log->school_id
        );

        $this->assertDatabaseHas(
            'assessment_audit_logs',
            [
                'id' => $log->id,

                'school_id' => $school->id,

                'action' => 'assessment.test',

                'module' => 'assessment',
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | User pelaku tercatat
    |--------------------------------------------------------------------------
    */

    public function test_authenticated_user_is_recorded_as_audit_actor(): void
    {
        $school =
            School::factory()
                ->create([
                    'is_active' => true,
                ]);

        $user =
            User::factory()
                ->create([
                    'is_active' => true,
                ]);

        $this->activateSchool(
            $school
        );

        $this->actingAs(
            $user
        );

        $log =
            app(
                AssessmentAuditService::class
            )->record(
                action: 'activity_score.updated',

                description: 'Nilai kegiatan diperbarui.',

                module: 'activity_score'
            );

        $this->assertSame(
            $user->id,
            $log->user_id
        );

        $this->assertDatabaseHas(
            'assessment_audit_logs',
            [
                'id' => $log->id,

                'school_id' => $school->id,

                'user_id' => $user->id,

                'action' => 'activity_score.updated',
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | JSON snapshot tersimpan
    |--------------------------------------------------------------------------
    */

    public function test_audit_stores_old_new_and_metadata_values(): void
    {
        $school =
            School::factory()
                ->create([
                    'is_active' => true,
                ]);

        $this->activateSchool(
            $school
        );

        $log =
            app(
                AssessmentAuditService::class
            )->record(
                action: 'attendance_weight.updated',

                description: 'Bobot kehadiran diperbarui.',

                oldValues: [
                    'late' => 75,

                    'version' => 1,
                ],

                newValues: [
                    'late' => 60,

                    'version' => 2,
                ],

                metadata: [
                    'source' => 'settings',
                ],

                module: 'attendance'
            );

        $log->refresh();

        $this->assertSame(
            [
                'late' => 75,

                'version' => 1,
            ],
            $log->old_values
        );

        $this->assertSame(
            [
                'late' => 60,

                'version' => 2,
            ],
            $log->new_values
        );

        $this->assertSame(
            [
                'source' => 'settings',
            ],
            $log->metadata
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Tenant Isolation
    |--------------------------------------------------------------------------
    */

    public function test_school_cannot_read_another_school_audit_logs(): void
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
        | Sekolah A
        |--------------------------------------------------------------------------
        */

        $this->activateSchool(
            $schoolA
        );

        $logA =
            app(
                AssessmentAuditService::class
            )->record(
                action: 'school_a.test',

                description: 'Audit Sekolah A.',

                module: 'test'
            );

        /*
        |--------------------------------------------------------------------------
        | Sekolah B
        |--------------------------------------------------------------------------
        */

        $this->activateSchool(
            $schoolB
        );

        $logB =
            app(
                AssessmentAuditService::class
            )->record(
                action: 'school_b.test',

                description: 'Audit Sekolah B.',

                module: 'test'
            );

        /*
        |--------------------------------------------------------------------------
        | Raw database memang mempunyai dua record.
        |--------------------------------------------------------------------------
        */

        $this->assertSame(
            2,
            DB::table(
                'assessment_audit_logs'
            )->count()
        );

        /*
        |--------------------------------------------------------------------------
        | Tetapi model tenant pada context B hanya melihat B.
        |--------------------------------------------------------------------------
        */

        $visibleToB =
            AssessmentAuditLog::query()
                ->get();

        $this->assertCount(
            1,
            $visibleToB
        );

        $this->assertSame(
            $logB->id,
            $visibleToB
                ->first()
                ->id
        );

        $this->assertFalse(
            $visibleToB
                ->contains(
                    'id',
                    $logA->id
                )
        );

        /*
        |--------------------------------------------------------------------------
        | Kembali ke A.
        |--------------------------------------------------------------------------
        */

        $this->activateSchool(
            $schoolA
        );

        $visibleToA =
            AssessmentAuditLog::query()
                ->get();

        $this->assertCount(
            1,
            $visibleToA
        );

        $this->assertSame(
            $logA->id,
            $visibleToA
                ->first()
                ->id
        );

        $this->assertFalse(
            $visibleToA
                ->contains(
                    'id',
                    $logB->id
                )
        );
    }
}
