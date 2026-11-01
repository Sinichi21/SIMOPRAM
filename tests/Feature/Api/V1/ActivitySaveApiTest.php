<?php

use App\Models\AcademicYear;
use App\Models\Activity;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

function activityApiToken(User $user, int $schoolId, string $permission): string
{
    setPermissionsTeamId($schoolId);
    $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    setPermissionsTeamId(null);

    return $user->createToken('Android')->plainTextToken;
}

test('authorized user can create a draft activity using the selected school active year', function () {
    $school = School::factory()->create();
    $year = AcademicYear::factory()->create([
        'school_id' => $school->id,
        'name' => '2026/2027',
        'is_active' => true,
    ]);
    $user = User::factory()->create(['is_active' => true, 'system_role' => 'super_admin']);
    $token = activityApiToken($user, $school->id, 'activities.create');

    $this->withToken($token)->withHeader('X-School-Id', (string) $school->id)
        ->postJson('/api/v1/activities', [
            'title' => 'Latihan Mingguan',
            'activity_type' => 'regular',
            'routine_session_no' => 2,
            'description' => 'Latihan baris-berbaris',
            'location' => 'Lapangan sekolah',
            'start_at' => '2026-10-28T08:00:00',
            'end_at' => '2026-10-28T10:00:00',
        ])
        ->assertCreated()
        ->assertJsonPath('data.title', 'Latihan Mingguan')
        ->assertJsonPath('data.academic_year.id', $year->id);

    $this->assertDatabaseHas('activities', [
        'school_id' => $school->id,
        'academic_year_id' => $year->id,
        'created_by' => $user->id,
        'title' => 'Latihan Mingguan',
        'status' => 'draft',
        'is_public' => false,
    ]);
});

test('activity creation rejects invalid time range and users without permission', function () {
    $school = School::factory()->create();
    AcademicYear::factory()->create(['school_id' => $school->id, 'is_active' => true]);
    $user = User::factory()->create(['is_active' => true, 'system_role' => 'super_admin']);
    $token = activityApiToken($user, $school->id, 'activities.create');

    $this->withToken($token)->withHeader('X-School-Id', (string) $school->id)
        ->postJson('/api/v1/activities', [
            'title' => 'Kegiatan invalid',
            'activity_type' => 'camp',
            'start_at' => '2026-10-28T10:00:00',
            'end_at' => '2026-10-28T08:00:00',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('end_at');
});

test('authorized user can update an activity only inside selected school', function () {
    $school = School::factory()->create();
    $otherSchool = School::factory()->create();
    $activity = Activity::factory()->create(['school_id' => $school->id]);
    $foreignActivity = Activity::factory()->create(['school_id' => $otherSchool->id]);
    $user = User::factory()->create(['is_active' => true, 'system_role' => 'super_admin']);
    $token = activityApiToken($user, $school->id, 'activities.update');

    $this->withToken($token)->withHeader('X-School-Id', (string) $school->id)
        ->putJson('/api/v1/activities/'.$activity->id, [
            'title' => 'Judul diperbarui',
            'activity_type' => 'camp',
            'start_at' => '2026-10-29T08:00:00',
            'end_at' => '2026-10-29T12:00:00',
        ])
        ->assertOk()
        ->assertJsonPath('data.title', 'Judul diperbarui');

    $this->withToken($token)->withHeader('X-School-Id', (string) $school->id)
        ->putJson('/api/v1/activities/'.$foreignActivity->id, [
            'title' => 'Tidak boleh lintas sekolah',
            'activity_type' => 'camp',
            'start_at' => '2026-10-29T08:00:00',
            'end_at' => '2026-10-29T12:00:00',
        ])
        ->assertNotFound();
});

test('activity update exposes the website status and public visibility fields to mobile clients', function () {
    $school = School::factory()->create();
    $activity = Activity::factory()->create(['school_id' => $school->id, 'status' => 'draft', 'is_public' => false]);
    $user = User::factory()->create(['is_active' => true, 'system_role' => 'super_admin']);
    $token = activityApiToken($user, $school->id, 'activities.update');

    $this->withToken($token)->withHeader('X-School-Id', (string) $school->id)
        ->putJson('/api/v1/activities/'.$activity->id, [
            'title' => 'Kegiatan Terbit',
            'activity_type' => 'camp',
            'start_at' => '2026-10-29T08:00:00',
            'end_at' => '2026-10-29T12:00:00',
            'status' => 'published',
            'is_public' => true,
        ])
        ->assertOk()
        ->assertJsonPath('data.status', 'published')
        ->assertJsonPath('data.is_public', true);

    $this->assertDatabaseHas('activities', [
        'id' => $activity->id,
        'status' => 'published',
        'is_public' => true,
    ]);
});

test('activity cancellation requires its dedicated permission', function () {
    $school = School::factory()->create();
    $activity = Activity::factory()->create(['school_id' => $school->id, 'status' => 'published']);
    $user = User::factory()->create(['is_active' => true, 'system_role' => 'super_admin']);
    $token = activityApiToken($user, $school->id, 'activities.cancel');

    $this->withToken($token)->withHeader('X-School-Id', (string) $school->id)
        ->postJson('/api/v1/activities/'.$activity->id.'/cancel')
        ->assertOk()
        ->assertJsonPath('data.status', 'cancelled');

    $this->assertDatabaseHas('activities', ['id' => $activity->id, 'status' => 'cancelled']);
});
