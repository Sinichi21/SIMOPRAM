<?php

use App\Livewire\Assessments\Activities\Index;
use App\Models\AcademicYear;
use App\Models\Activity;
use App\Models\ActivityAssessment;
use App\Models\AssessmentFactor;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function createActivityAssessmentContext(): array
{
    $user = User::factory()->create([
        'system_role' => 'super_admin',
        'is_active' => true,
    ]);
    $school = School::factory()->create();

    test()
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
    $activity = Activity::query()->create([
        'academic_year_id' => $academicYear->id,
        'created_by' => $user->id,
        'title' => 'Perkemahan Pengujian',
        'start_at' => '2026-09-10 08:00:00',
        'end_at' => '2026-09-10 16:00:00',
        'status' => 'published',
    ]);

    return compact('user', 'school', 'activity');
}

test('published activity assessment score page can be opened', function () {
    ['user' => $user, 'activity' => $activity] = createActivityAssessmentContext();
    $factor = AssessmentFactor::query()->create([
        'name' => 'Keterampilan',
        'code' => 'SKILL',
        'source_type' => 'manual',
        'is_active' => true,
    ]);
    $assessment = ActivityAssessment::query()->create([
        'activity_id' => $activity->id,
        'assessment_factor_id' => $factor->id,
        'title' => 'Praktik Tali-Temali',
        'mode' => 'individual',
        'status' => 'published',
        'created_by' => $user->id,
    ]);

    $response = $this->get(route('activity-assessments.score', $assessment));

    $response
        ->assertOk()
        ->assertSee('Praktik Tali-Temali')
        ->assertSee('Input Nilai Kegiatan');
});

test('attendance factor cannot be used for an activity assessment', function () {
    ['activity' => $activity] = createActivityAssessmentContext();
    $factor = AssessmentFactor::query()->create([
        'name' => 'Kehadiran',
        'code' => 'ATTENDANCE',
        'source_type' => 'attendance',
        'is_active' => true,
    ]);

    expect(fn () => Livewire::test(Index::class)
        ->set('activityId', $activity->id)
        ->set('assessmentFactorId', $factor->id)
        ->set('title', 'Form Tidak Valid')
        ->call('create'))
        ->toThrow(ModelNotFoundException::class);

    expect(ActivityAssessment::query()->count())->toBe(0);
});
