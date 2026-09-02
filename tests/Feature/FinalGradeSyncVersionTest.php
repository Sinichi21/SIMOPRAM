<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AssessmentConfig;
use App\Models\Classroom;
use App\Models\FinalGrade;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\AssessmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FinalGradeSyncVersionTest extends TestCase
{
    use RefreshDatabase;

    public function test_final_grade_is_stale_when_attendance_weight_version_changes(): void
    {
        $user = User::factory()->create([
            'system_role' => 'super_admin',
            'is_active' => true,
        ]);
        $school = School::factory()->create();

        $this
            ->actingAs($user)
            ->withSession(['active_school_id' => $school->id])
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
        DB::table('attendance_score_settings')->insert([
            'school_id' => $school->id,
            'present_weight' => 100,
            'late_weight' => 75,
            'sick_weight' => 75,
            'excused_weight' => 75,
            'absent_weight' => 0,
            'version' => 2,
            'updated_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $finalGrade = FinalGrade::query()->create([
            'assessment_config_id' => $config->id,
            'student_id' => $student->id,
            'final_score' => 80,
            'attendance_source_version' => 1,
            'calculated_at' => now(),
            'calculated_by' => $user->id,
        ]);

        $staleStatus = app(AssessmentService::class)
            ->finalGradeSyncStatus($config);

        expect($staleStatus)->toMatchArray([
            'version' => 2,
            'expected_count' => 1,
            'current_count' => 0,
            'stale_count' => 1,
            'is_stale' => true,
        ]);

        $finalGrade->update(['attendance_source_version' => 2]);

        $currentStatus = app(AssessmentService::class)
            ->finalGradeSyncStatus($config);

        expect($currentStatus)->toMatchArray([
            'version' => 2,
            'expected_count' => 1,
            'current_count' => 1,
            'stale_count' => 0,
            'is_stale' => false,
        ]);
    }
}
