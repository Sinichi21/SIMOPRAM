<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AssessmentConfig;
use App\Models\AssessmentConfigItem;
use App\Models\AssessmentFactor;
use App\Models\Classroom;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentScore;
use App\Models\User;
use App\Services\AssessmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AssessmentSynchronizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this
            ->get(
                route(
                    'assessment-sync.index'
                )
            )
            ->assertRedirect(
                route('login')
            );
    }

    public function test_global_super_admin_cannot_open_sync_without_school(): void
    {
        $user =
            User::factory()
                ->create([
                    'system_role' => 'super_admin',

                    'is_active' => true,
                ]);

        $this
            ->actingAs($user)
            ->get(
                route(
                    'assessment-sync.index'
                )
            )
            ->assertStatus(
                409
            );
    }

    public function test_super_admin_can_open_sync_with_active_school_context(): void
    {
        $user =
            User::factory()
                ->create([
                    'system_role' => 'super_admin',

                    'is_active' => true,
                ]);

        $school =
            School::factory()
                ->create([
                    'is_active' => true,
                ]);

        $this
            ->actingAs($user)
            ->withSession([
                'active_school_id' => $school->id,
            ])
            ->get(
                route(
                    'assessment-sync.index'
                )
            )
            ->assertOk();
    }

    public function test_all_scores_can_be_synchronized(): void
    {
        $user = User::factory()->create([
            'system_role' => 'super_admin',
            'is_active' => true,
        ]);
        $school = School::factory()->create();

        $this
            ->actingAs($user)
            ->withSession([
                'active_school_id' => $school->id,
            ])
            ->get(route('dashboard'))
            ->assertOk();

        $academicYear = AcademicYear::query()->create([
            'name' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'is_active' => true,
        ]);
        $classroom = Classroom::query()->create([
            'name' => 'Kelas 7A',
            'grade' => 7,
            'is_active' => true,
        ]);
        $student = Student::query()->create([
            'nis' => 'SYNC-001',
            'name' => 'Siswa Sinkronisasi',
            'status' => 'active',
        ]);
        DB::table('student_enrollments')->insert([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'classroom_id' => $classroom->id,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $config = AssessmentConfig::query()->create([
            'academic_year_id' => $academicYear->id,
            'name' => 'Nilai Akhir',
            'is_active' => true,
        ]);
        $factor = AssessmentFactor::query()->create([
            'name' => 'Keterampilan',
            'code' => 'SKILL',
            'source_type' => 'manual',
            'is_active' => true,
        ]);
        AssessmentConfigItem::query()->create([
            'assessment_config_id' => $config->id,
            'assessment_factor_id' => $factor->id,
            'weight' => 50,
            'sort_order' => 1,
        ]);
        $attendanceFactor = AssessmentFactor::query()->create([
            'name' => 'Kehadiran',
            'code' => 'ATTENDANCE',
            'source_type' => 'attendance',
            'is_active' => true,
        ]);
        AssessmentConfigItem::query()->create([
            'assessment_config_id' => $config->id,
            'assessment_factor_id' => $attendanceFactor->id,
            'weight' => 50,
            'sort_order' => 2,
        ]);
        StudentScore::query()->create([
            'assessment_config_id' => $config->id,
            'student_id' => $student->id,
            'assessment_factor_id' => $factor->id,
            'score' => 80,
            'source' => 'manual',
            'entered_by' => $user->id,
        ]);

        $result = app(AssessmentService::class)->syncAllScores($config);

        expect($result)->toBe([
            'attendance_scores' => 1,
            'final_grades' => 1,
        ]);
        $this->assertDatabaseHas('student_scores', [
            'assessment_config_id' => $config->id,
            'student_id' => $student->id,
            'assessment_factor_id' => $attendanceFactor->id,
            'source' => 'attendance',
            'source_version' => 1,
            'score' => 0,
        ]);
        $this->assertDatabaseHas('final_grades', [
            'assessment_config_id' => $config->id,
            'student_id' => $student->id,
            'final_score' => 40,
        ]);
    }
}
