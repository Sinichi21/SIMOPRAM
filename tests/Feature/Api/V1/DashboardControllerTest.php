<?php

use App\Models\Activity;
use App\Models\Coach;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Support\SchoolContext;

test('super admin can view global dashboard without a selected school', function () {
    $activeSchool = School::factory()->create();
    School::factory()->inactive()->create();
    $user = User::factory()->create(['is_active' => true, 'system_role' => 'super_admin']);
    $token = $user->createToken('Android')->plainTextToken;

    $this->withToken($token)->getJson('/api/v1/dashboard/global')
        ->assertOk()
        ->assertJsonPath('data.mode', 'global')
        ->assertJsonPath('data.school', null)
        ->assertJsonPath('data.dashboard.totalSchools', 2)
        ->assertJsonPath('data.dashboard.activeSchools', 1)
        ->assertJsonPath('data.dashboard.schoolMonitoring.0.school.id', $activeSchool->id);
});

test('global dashboard returns unauthorized when no token is provided', function () {
    $this->getJson('/api/v1/dashboard/global')->assertUnauthorized();
});

test('global dashboard forbids scout admins', function () {
    $user = User::factory()->create(['is_active' => true, 'system_role' => 'scout_admin']);
    $token = $user->createToken('Android')->plainTextToken;

    $this->withToken($token)->getJson('/api/v1/dashboard/global')->assertForbidden();
});

test('school dashboard still requires a selected school', function () {
    $user = User::factory()->create(['is_active' => true, 'system_role' => 'super_admin']);
    $token = $user->createToken('Android')->plainTextToken;

    $this->withToken($token)->getJson('/api/v1/dashboard')->assertUnprocessable();
});

test('school dashboard returns metrics scoped to the selected school', function () {
    $selectedSchool = School::factory()->create();
    $otherSchool = School::factory()->create();
    Student::factory()->create(['school_id' => $selectedSchool->id, 'status' => 'active']);
    Student::factory()->create(['school_id' => $selectedSchool->id, 'status' => 'inactive']);
    Student::factory()->create(['school_id' => $otherSchool->id, 'status' => 'active']);

    $schoolContext = app(SchoolContext::class);
    $schoolContext->set($selectedSchool);
    Coach::query()->create(['name' => 'Pembina Aktif Dashboard', 'is_active' => true]);
    Coach::query()->create(['name' => 'Pembina Nonaktif Dashboard', 'is_active' => false]);
    $schoolContext->clear();
    $schoolContext->set($otherSchool);
    Coach::query()->create(['name' => 'Pembina Sekolah Lain', 'is_active' => true]);
    $schoolContext->clear();

    $selectedActivity = Activity::factory()->create([
        'school_id' => $selectedSchool->id,
        'approval_status' => 'approved',
    ]);
    Activity::factory()->create([
        'school_id' => $otherSchool->id,
        'approval_status' => 'approved',
    ]);
    $user = User::factory()->create(['is_active' => true, 'system_role' => 'super_admin']);
    $token = $user->createToken('Android')->plainTextToken;

    $this->withToken($token)->withHeader('X-School-Id', (string) $selectedSchool->id)
        ->getJson('/api/v1/dashboard')
        ->assertOk()
        ->assertJsonPath('data.school.id', $selectedSchool->id)
        ->assertJsonPath('data.summary.students', 2)
        ->assertJsonPath('data.summary.active_students', 1)
        ->assertJsonPath('data.summary.coaches', 2)
        ->assertJsonPath('data.summary.active_coaches', 1)
        ->assertJsonPath('data.summary.activities', 1)
        ->assertJsonPath('data.summary.upcoming_activities', 1)
        ->assertJsonPath('data.upcoming_activities.0.id', $selectedActivity->id);
});
