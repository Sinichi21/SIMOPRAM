<?php

use App\Livewire\Activities\Index as ActivitiesIndex;
use App\Livewire\Assessments\Activities\Index as AssessmentsIndex;
use App\Livewire\Attendances\Index as AttendancesIndex;
use App\Models\Activity;
use App\Models\ActivityAssessment;
use App\Models\AssessmentFactor;
use App\Models\Coach;
use App\Models\School;
use App\Models\User;
use App\Support\SchoolContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('activity attendance and assessment lists default to assignments and allow assignment filters', function () {
    $school = School::factory()->create();
    app(SchoolContext::class)->set($school);
    $user = User::factory()->create([
        'system_role' => 'coach',
        'is_active' => true,
    ]);
    $this->actingAs($user);

    $ownCoach = Coach::query()->create([
        'user_id' => $user->id,
        'name' => 'Pembina Saya',
        'is_active' => true,
    ]);
    $otherCoach = Coach::query()->create([
        'name' => 'Pembina Lain',
        'is_active' => true,
    ]);

    $ownActivity = Activity::factory()->create([
        'school_id' => $school->id,
        'title' => 'Kegiatan Saya',
    ]);
    $otherActivity = Activity::factory()->create([
        'school_id' => $school->id,
        'title' => 'Kegiatan Pembina Lain',
    ]);
    $ownActivity->coaches()->attach($ownCoach->id, ['school_id' => $school->id, 'role' => 'coach']);
    $otherActivity->coaches()->attach($otherCoach->id, ['school_id' => $school->id, 'role' => 'coach']);

    $factor = AssessmentFactor::query()->create([
        'name' => 'Keterampilan',
        'code' => 'ASSIGNMENT-FILTER',
        'source_type' => 'manual',
        'is_active' => true,
    ]);
    foreach ([[$ownActivity, 'Penilaian Saya'], [$otherActivity, 'Penilaian Pembina Lain']] as [$activity, $title]) {
        ActivityAssessment::query()->create([
            'activity_id' => $activity->id,
            'assessment_factor_id' => $factor->id,
            'title' => $title,
            'mode' => 'individual',
            'status' => 'draft',
            'created_by' => $user->id,
        ]);
    }

    Livewire::test(ActivitiesIndex::class)
        ->assertSet('assignmentFilter', 'mine')
        ->assertSee('Kegiatan Saya')
        ->assertDontSee('Kegiatan Pembina Lain')
        ->set('assignmentFilter', 'all')
        ->assertSee('Kegiatan Pembina Lain')
        ->set('assignmentFilter', 'coach')
        ->set('filterCoachId', (string) $otherCoach->id)
        ->assertSee('Kegiatan Pembina Lain')
        ->assertDontSee('Kegiatan Saya');

    Livewire::test(AttendancesIndex::class)
        ->assertSet('assignmentFilter', 'mine')
        ->assertSee('Kegiatan Saya')
        ->assertDontSee('Kegiatan Pembina Lain')
        ->set('assignmentFilter', 'all')
        ->assertSee('Kegiatan Pembina Lain')
        ->set('assignmentFilter', 'coach')
        ->set('filterCoachId', (string) $otherCoach->id)
        ->assertSee('Kegiatan Pembina Lain')
        ->assertDontSee('Kegiatan Saya');

    Livewire::test(AssessmentsIndex::class)
        ->assertSet('assignmentFilter', 'mine')
        ->assertSee('Penilaian Saya')
        ->assertDontSee('Penilaian Pembina Lain')
        ->set('assignmentFilter', 'all')
        ->assertSee('Penilaian Pembina Lain')
        ->set('assignmentFilter', 'coach')
        ->set('filterCoachId', (string) $otherCoach->id)
        ->assertSee('Penilaian Pembina Lain')
        ->assertDontSee('Penilaian Saya');
});

test('admins default to all activity assignments', function () {
    $school = School::factory()->create();
    app(SchoolContext::class)->set($school);
    $admin = User::factory()->create([
        'system_role' => 'super_admin',
        'is_active' => true,
    ]);
    $this->actingAs($admin);

    Livewire::test(ActivitiesIndex::class)->assertSet('assignmentFilter', 'all');
    Livewire::test(AttendancesIndex::class)->assertSet('assignmentFilter', 'all');
    Livewire::test(AssessmentsIndex::class)->assertSet('assignmentFilter', 'all');
});
