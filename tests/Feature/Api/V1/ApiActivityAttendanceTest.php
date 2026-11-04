<?php

use App\Models\Activity;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

test('authorized user can list activities only within the selected school', function () {
    $school = School::factory()->create();
    $activity = Activity::factory()->create(['school_id' => $school->id, 'approval_status' => 'approved']);
    $user = User::factory()->create(['is_active' => true, 'system_role' => 'super_admin']);
    setPermissionsTeamId($school->id);
    $user->givePermissionTo(Permission::findOrCreate('activities.view', 'web'));
    setPermissionsTeamId(null);
    $token = $user->createToken('Android')->plainTextToken;

    $this->withToken($token)->withHeader('X-School-Id', (string) $school->id)
        ->getJson('/api/v1/activities')
        ->assertOk()
        ->assertJsonPath('data.0.id', $activity->id)
        ->assertJsonPath('data.0.title', $activity->title);
});

test('activity endpoints require school context and permission', function () {
    $user = User::factory()->create(['is_active' => true]);
    $token = $user->createToken('Android')->plainTextToken;
    $school = School::factory()->create();

    $this->withToken($token)->getJson('/api/v1/activities')->assertUnprocessable();
    $user->schoolMemberships()->create(['school_id' => $school->id, 'is_active' => true, 'joined_at' => today()]);
    $this->withToken($token)->withHeader('X-School-Id', (string) $school->id)
        ->getJson('/api/v1/activities')->assertForbidden();
});

test('mobile activity and attendance lists include web-visible non-approved activities within the selected school', function () {
    $school = School::factory()->create();
    $activity = Activity::factory()->create([
        'school_id' => $school->id,
        'approval_status' => 'pending',
        'status' => 'draft',
    ]);
    $otherSchool = School::factory()->create();
    Activity::factory()->create([
        'school_id' => $otherSchool->id,
        'approval_status' => 'pending',
        'status' => 'draft',
    ]);
    $user = User::factory()->create(['is_active' => true, 'system_role' => 'super_admin']);
    setPermissionsTeamId($school->id);
    $user->givePermissionTo(Permission::findOrCreate('activities.view', 'web'));
    $user->givePermissionTo(Permission::findOrCreate('attendance_sessions.view', 'web'));
    setPermissionsTeamId(null);
    $token = $user->createToken('Android')->plainTextToken;

    $this->withToken($token)->withHeader('X-School-Id', (string) $school->id)
        ->getJson('/api/v1/activities')
        ->assertOk()
        ->assertJsonFragment(['id' => $activity->id, 'title' => $activity->title]);

    $this->withToken($token)->withHeader('X-School-Id', (string) $school->id)
        ->getJson('/api/v1/attendance/activities')
        ->assertOk()
        ->assertJsonFragment(['id' => $activity->id, 'title' => $activity->title]);
});
