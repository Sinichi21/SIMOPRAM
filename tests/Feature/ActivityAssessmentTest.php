<?php

use App\Livewire\Assessments\Activities\Index;
use App\Models\AcademicYear;
use App\Models\Activity;
use App\Models\ActivityAssessment;
use App\Models\AssessmentFactor;
use App\Models\School;
use App\Models\ScoutLevel;
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

test('activity assessments can be filtered by scout level while shared activities remain visible', function () {
    ['user' => $user, 'activity' => $sharedActivity] = createActivityAssessmentContext();
    $siaga = ScoutLevel::query()->create([
        'code' => 'SIAGA',
        'name' => 'Siaga',
        'sort_order' => 1,
    ]);
    $penggalang = ScoutLevel::query()->create([
        'code' => 'PENGGALANG',
        'name' => 'Penggalang',
        'sort_order' => 2,
    ]);
    $penggalangActivity = Activity::factory()->create([
        'school_id' => $sharedActivity->school_id,
        'academic_year_id' => $sharedActivity->academic_year_id,
        'created_by' => $user->id,
        'title' => 'Latihan Khusus Penggalang',
    ]);
    $penggalangActivity->scoutLevels()->attach($penggalang);
    $factor = AssessmentFactor::query()->create([
        'name' => 'Keterampilan',
        'code' => 'SKILL-FILTER',
        'source_type' => 'manual',
        'is_active' => true,
    ]);

    foreach ([$sharedActivity, $penggalangActivity] as $activity) {
        ActivityAssessment::query()->create([
            'activity_id' => $activity->id,
            'assessment_factor_id' => $factor->id,
            'title' => 'Nilai '.$activity->title,
            'mode' => 'individual',
            'status' => 'draft',
            'created_by' => $user->id,
        ]);
    }

    Livewire::test(Index::class)
        ->set('scoutLevelId', (string) $siaga->id)
        ->assertSee('Nilai Perkemahan Pengujian')
        ->assertDontSee('Nilai Latihan Khusus Penggalang');

    Livewire::test(Index::class)
        ->set('scoutLevelId', (string) $penggalang->id)
        ->assertSee('Nilai Perkemahan Pengujian')
        ->assertSee('Nilai Latihan Khusus Penggalang');
});
