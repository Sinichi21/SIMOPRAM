<?php

use App\\Models\\Activity;
use App\\Models\\Attendance;
use App\\Models\\AttendanceSession;
use App\\Models\\AttendanceSessionParticipant;
use App\\Models\\School;
use App\\Models\\Student;
use App\\Models\\User;
use Spatie\\Permission\\Models\\Permission;
use Illuminate\\Foundation\\Testing\\RefreshDatabase;

uses(RefreshDatabase::class);

function attendanceSessionApiToken(User $user, int $schoolId, string ...$permissions): string
{
    setPermissionsTeamId($schoolId);

    foreach ($permissions as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }

    setPermissionsTeamId(null);

    return $user->createToken('Android attendance tests')->plainTextToken;
}

test('participant endpoint returns saved attendance status after a fresh request', function () {
    $school = School::factory()->create();
    $activity = Activity::factory()->create([
        'school_id' => $school->id,
        'approval_status' => 'approved',
    ]);
    $student = Student::factory()->create([
        'school_id' => $school->id,
        'status' => 'active',
    ]);
    $session = AttendanceSession::query()->create([
        'school_id' => $school->id,
        'activity_id' => $activity->id,
        'name' => 'Sesi uji persistensi',
        'participant_scope' => 'all',
        'open_at' => '2026-10-26 08:00:00',
        'late_after' => '2026-10-26 08:15:00',
        'close_at' => '2026-10-26 09:00:00',
        'allow_manual' => true,
        'allow_self_checkin' => false,
        'is_active' => true,
    ]);

    AttendanceSessionParticipant::query()->create([
        'school_id' => $school->id,
        'attendance_session_id' => $session->id,
        'student_id' => $student->id,
    ]);

    Attendance::query()->create([
        'school_id' => $school->id,
        'attendance_session_id' => $session->id,
        'activity_id' => $activity->id,
        'student_id' => $student->id,
        'status' => 'present',
        'source' => 'manual',
        'checked_in_at' => '2026-10-26 08:12:00',
    ]);

    $user = User::factory()->create([
        'is_active' => true,
        'system_role' => 'super_admin',
    ]);
    $token = attendanceSessionApiToken($user, $school->id, 'attendance_sessions.view');

    $this->withToken($token)
        ->withHeader('X-School-Id', (string) $school->id)
        ->getJson('/api/v1/attendance-sessions/'.$session->id.'/participants')
        ->assertOk()
        ->assertJsonPath('data.0.student.id', $student->id)
        ->assertJsonPath('data.0.attendance.status', 'present')
        ->assertJsonPath('data.0.attendance.source', 'manual');

    // A second independent request must return the persisted status too.
    $this->withToken($token)
        ->withHeader('X-School-Id', (string) $school->id)
        ->getJson('/api/v1/attendance-sessions/'.$session->id.'/participants')
        ->assertOk()
        ->assertJsonPath('data.0.attendance.status', 'present');
});

test('user with session management permission can create a session and its participants', function () {
    $school = School::factory()->create();
    $activity = Activity::factory()->create([
        'school_id' => $school->id,
        'approval_status' => 'approved',
    ]);
    $student = Student::factory()->create([
        'school_id' => $school->id,
        'status' => 'active',
    ]);
    $user = User::factory()->create([
        'is_active' => true,
        'system_role' => 'super_admin',
    ]);
    $token = attendanceSessionApiToken($user, $school->id, 'attendance_sessions.view', 'attendance_sessions.manage');

    $this->withToken($token)
        ->withHeader('X-School-Id', (string) $school->id)
        ->postJson('/api/v1/activities/'.$activity->id.'/attendance-sessions', [
            'name' => 'Latihan sore',
            'participant_scope' => 'all',
            'open_at' => '2026-10-26T10:00:00',
            'late_after' => '2026-10-26T10:15:00',
            'close_at' => '2026-10-26T11:00:00',
            'allow_manual' => true,
            'allow_self_checkin' => false,
            'radius_m' => 100,
            'max_accuracy_m' => 100,
            'is_active' => true,
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Latihan sore')
        ->assertJsonPath('data.allow_manual', true);

    $session = AttendanceSession::query()
        ->where('activity_id', $activity->id)
        ->where('name', 'Latihan sore')
        ->firstOrFail();

    $this->assertDatabaseHas('attendance_session_participants', [
        'school_id' => $school->id,
        'attendance_session_id' => $session->id,
        'student_id' => $student->id,
    ]);
});

test('user without session management permission cannot create a session', function () {
    $school = School::factory()->create();
    $activity = Activity::factory()->create([
        'school_id' => $school->id,
        'approval_status' => 'approved',
    ]);
    $user = User::factory()->create([
        'is_active' => true,
        'system_role' => 'super_admin',
    ]);
    $token = $user->createToken('Android read-only test')->plainTextToken;

    $this->withToken($token)
        ->withHeader('X-School-Id', (string) $school->id)
        ->postJson('/api/v1/activities/'.$activity->id.'/attendance-sessions', [
            'name' => 'Sesi tanpa izin',
            'participant_scope' => 'all',
            'open_at' => '2026-10-26T10:00:00',
            'close_at' => '2026-10-26T11:00:00',
            'allow_manual' => true,
            'allow_self_checkin' => false,
            'radius_m' => 100,
            'max_accuracy_m' => 100,
            'is_active' => true,
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('attendance_sessions', [
        'school_id' => $school->id,
        'activity_id' => $activity->id,
        'name' => 'Sesi tanpa izin',
    ]);
});
