<?php

use App\Models\Activity;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\AttendanceSessionParticipant;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Support\SchoolContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

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
    app(SchoolContext::class)->set($school);
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
    app(SchoolContext::class)->clear();

    $user = User::factory()->create([
        'is_active' => true,
        'system_role' => 'school_admin',
    ]);
    $user->schoolMemberships()->create([
        'school_id' => $school->id,
        'is_active' => true,
        'joined_at' => today(),
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
        'system_role' => 'school_admin',
    ]);
    $user->schoolMemberships()->create([
        'school_id' => $school->id,
        'is_active' => true,
        'joined_at' => today(),
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
        'system_role' => 'school_admin',
    ]);
    $user->schoolMemberships()->create([
        'school_id' => $school->id,
        'is_active' => true,
        'joined_at' => today(),
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

test('manual attendance mark is persisted and returned on the next participants request', function () {
    $school = School::factory()->create();
    app(SchoolContext::class)->set($school);
    $activity = Activity::factory()->create([
        'school_id' => $school->id,
        'approval_status' => 'approved',
    ]);
    $student = Student::factory()->create([
        'school_id' => $school->id,
        'status' => 'active',
    ]);
    $session = AttendanceSession::query()->create([
        'activity_id' => $activity->id,
        'name' => 'Sesi catat dan muat ulang',
        'participant_scope' => 'all',
        'open_at' => '2026-10-26 08:00:00',
        'late_after' => '2026-10-26 08:15:00',
        'close_at' => '2026-10-26 09:00:00',
        'allow_manual' => true,
        'allow_self_checkin' => false,
        'is_active' => true,
    ]);
    $session->participants()->create(['student_id' => $student->id]);
    app(SchoolContext::class)->clear();

    $user = User::factory()->create([
        'is_active' => true,
        'system_role' => 'school_admin',
    ]);
    $user->schoolMemberships()->create([
        'school_id' => $school->id,
        'is_active' => true,
        'joined_at' => today(),
    ]);
    $token = attendanceSessionApiToken($user, $school->id, 'attendance_sessions.view', 'attendance.manual');

    $this->withToken($token)
        ->withHeader('X-School-Id', (string) $school->id)
        ->patchJson('/api/v1/attendance-sessions/'.$session->id.'/participants/'.$student->id, [
            'status' => 'late',
            'notes' => 'Tiba terlambat',
        ])
        ->assertCreated()
        ->assertJsonPath('data.status', 'late')
        ->assertJsonPath('data.source', 'manual');

    $this->withToken($token)
        ->withHeader('X-School-Id', (string) $school->id)
        ->getJson('/api/v1/attendance-sessions/'.$session->id.'/participants')
        ->assertOk()
        ->assertJsonPath('data.0.attendance.status', 'late')
        ->assertJsonPath('data.0.attendance.source', 'manual');

    $this->assertDatabaseHas('attendances', [
        'school_id' => $school->id,
        'attendance_session_id' => $session->id,
        'student_id' => $student->id,
        'status' => 'late',
        'notes' => 'Tiba terlambat',
    ]);
});

test('user without manual attendance permission cannot create an attendance record', function () {
    $school = School::factory()->create();
    app(SchoolContext::class)->set($school);
    $activity = Activity::factory()->create([
        'school_id' => $school->id,
        'approval_status' => 'approved',
    ]);
    $student = Student::factory()->create([
        'school_id' => $school->id,
        'status' => 'active',
    ]);
    $session = AttendanceSession::query()->create([
        'activity_id' => $activity->id,
        'name' => 'Sesi hanya-lihat',
        'participant_scope' => 'all',
        'open_at' => '2026-10-26 08:00:00',
        'late_after' => '2026-10-26 08:15:00',
        'close_at' => '2026-10-26 09:00:00',
        'allow_manual' => true,
        'allow_self_checkin' => false,
        'is_active' => true,
    ]);
    $session->participants()->create(['student_id' => $student->id]);
    app(SchoolContext::class)->clear();

    $user = User::factory()->create([
        'is_active' => true,
        'system_role' => 'school_admin',
    ]);
    $user->schoolMemberships()->create([
        'school_id' => $school->id,
        'is_active' => true,
        'joined_at' => today(),
    ]);
    $token = attendanceSessionApiToken($user, $school->id, 'attendance_sessions.view');

    $this->withToken($token)
        ->withHeader('X-School-Id', (string) $school->id)
        ->patchJson('/api/v1/attendance-sessions/'.$session->id.'/participants/'.$student->id, [
            'status' => 'present',
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('attendances', [
        'school_id' => $school->id,
        'attendance_session_id' => $session->id,
        'student_id' => $student->id,
    ]);
});

test('session management permission allows editing and toggling a session without losing its participant snapshot', function () {
    $school = School::factory()->create();
    app(SchoolContext::class)->set($school);
    $activity = Activity::factory()->create([
        'school_id' => $school->id,
        'approval_status' => 'approved',
    ]);
    $student = Student::factory()->create([
        'school_id' => $school->id,
        'status' => 'active',
    ]);
    $session = AttendanceSession::query()->create([
        'activity_id' => $activity->id,
        'name' => 'Nama sesi awal',
        'participant_scope' => 'all',
        'open_at' => '2026-10-26 08:00:00',
        'late_after' => '2026-10-26 08:15:00',
        'close_at' => '2026-10-26 09:00:00',
        'allow_manual' => true,
        'allow_self_checkin' => false,
        'is_active' => true,
    ]);
    app(SchoolContext::class)->clear();

    $user = User::factory()->create([
        'is_active' => true,
        'system_role' => 'school_admin',
    ]);
    $user->schoolMemberships()->create([
        'school_id' => $school->id,
        'is_active' => true,
        'joined_at' => today(),
    ]);
    $token = attendanceSessionApiToken($user, $school->id, 'attendance_sessions.view', 'attendance_sessions.manage');

    $this->withToken($token)
        ->withHeader('X-School-Id', (string) $school->id)
        ->patchJson('/api/v1/attendance-sessions/'.$session->id.'/toggle')
        ->assertOk()
        ->assertJsonPath('data.is_active', false);

    $this->withToken($token)
        ->withHeader('X-School-Id', (string) $school->id)
        ->patchJson('/api/v1/attendance-sessions/'.$session->id, [
            'name' => 'Nama sesi diperbarui',
            'participant_scope' => 'all',
            'open_at' => '2026-10-26T08:00:00',
            'late_after' => '2026-10-26T08:15:00',
            'close_at' => '2026-10-26T09:30:00',
            'allow_manual' => true,
            'allow_self_checkin' => false,
            'radius_m' => 100,
            'max_accuracy_m' => 100,
            'is_active' => false,
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Nama sesi diperbarui')
        ->assertJsonPath('data.is_active', false);

    $this->withToken($token)
        ->withHeader('X-School-Id', (string) $school->id)
        ->patchJson('/api/v1/attendance-sessions/'.$session->id.'/toggle')
        ->assertOk()
        ->assertJsonPath('data.is_active', true);

    $this->assertDatabaseHas('attendance_session_participants', [
        'school_id' => $school->id,
        'attendance_session_id' => $session->id,
        'student_id' => $student->id,
    ]);
});
