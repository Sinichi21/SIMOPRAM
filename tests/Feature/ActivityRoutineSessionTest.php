<?php

use App\Livewire\Activities\Index;
use App\Models\AcademicYear;
use App\Models\Activity;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\School;
use App\Models\Semester;
use App\Models\User;
use App\Support\SchoolContext;
use Livewire\Livewire;

test('regular activities require and store a routine session number', function () {
    $user = User::factory()->create([
        'system_role' => 'super_admin',
        'is_active' => true,
    ]);
    $school = School::factory()->create();
    app(SchoolContext::class)->set($school);
    $academicYear = AcademicYear::factory()->create([
        'school_id' => $school->id,
        'name' => '2026/2027',
        'start_date' => '2026-07-01',
        'end_date' => '2027-06-30',
        'is_active' => true,
    ]);
    $semester = Semester::query()->create([
        'academic_year_id' => $academicYear->id,
        'name' => 'Semester Ganjil',
        'semester_number' => 1,
        'start_date' => '2026-07-01',
        'end_date' => '2026-12-31',
        'is_active' => true,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(Index::class)
        ->set('academic_year_id', $academicYear->id)
        ->set('semester_id', $semester->id)
        ->set('title', 'Latihan Rutin Sesi Dua')
        ->set('activity_type', 'regular')
        ->set('routine_session_no', null)
        ->set('start_at', '2026-09-11T15:00')
        ->set('end_at', '2026-09-11T16:00')
        ->call('save')
        ->assertHasErrors(['routine_session_no']);

    $component
        ->set('routine_session_no', 2)
        ->call('save')
        ->assertHasNoErrors();

    expect(Activity::query()
        ->where('title', 'Latihan Rutin Sesi Dua')
        ->value('routine_session_no'))->toBe(2);
});

test('special activities do not keep a routine session number', function () {
    $user = User::factory()->create([
        'system_role' => 'super_admin',
        'is_active' => true,
    ]);
    $school = School::factory()->create();
    app(SchoolContext::class)->set($school);
    $academicYear = AcademicYear::factory()->create([
        'school_id' => $school->id,
        'is_active' => true,
    ]);
    $semester = Semester::query()->create([
        'academic_year_id' => $academicYear->id,
        'name' => 'Semester Ganjil',
        'semester_number' => 1,
        'start_date' => '2026-07-01',
        'end_date' => '2026-12-31',
        'is_active' => true,
    ]);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->set('academic_year_id', $academicYear->id)
        ->set('semester_id', $semester->id)
        ->set('title', 'Perkemahan Khusus')
        ->set('activity_type', 'regular')
        ->set('routine_session_no', 2)
        ->set('activity_type', 'camp')
        ->assertSet('routine_session_no', null)
        ->set('start_at', '2026-09-20T08:00')
        ->set('end_at', '2026-09-20T16:00')
        ->call('save')
        ->assertHasNoErrors();

    expect(Activity::query()
        ->where('title', 'Perkemahan Khusus')
        ->value('routine_session_no'))->toBeNull();
});


test('changing an attended activity session requires confirmation and retains attendance', function () {
    $user = User::factory()->create(['system_role' => 'super_admin', 'is_active' => true]);
    $school = School::factory()->create();
    app(SchoolContext::class)->set($school);
    $year = AcademicYear::factory()->create([
        'school_id' => $school->id, 'is_active' => true,
    ]);
    $semester = Semester::query()->create([
        'school_id' => $school->id, 'academic_year_id' => $year->id,
        'name' => 'Semester Ganjil', 'semester_number' => 1,
        'start_date' => '2026-07-01', 'end_date' => '2026-12-31', 'is_active' => true,
    ]);
    $activity = Activity::factory()->create([
        'school_id' => $school->id, 'academic_year_id' => $year->id,
        'semester_id' => $semester->id, 'created_by' => $user->id,
        'activity_type' => 'regular', 'routine_session_no' => 1,
        'start_at' => '2026-09-04 12:00:00',
        'end_at' => '2026-09-04 13:30:00', 'status' => 'completed',
    ]);
    $session = AttendanceSession::query()->create([
        'activity_id' => $activity->id, 'created_by' => $user->id,
        'name' => 'Absensi', 'participant_scope' => 'all',
        'open_at' => '2026-09-04 12:00:00', 'close_at' => '2026-09-04 13:30:00',
        'is_active' => true,
    ]);
    $student = \App\Models\Student::factory()->create(['school_id' => $school->id]);
    $attendance = Attendance::query()->create([
        'attendance_session_id' => $session->id,
        'activity_id' => $activity->id, 'student_id' => $student->id,
        'status' => 'present', 'source' => 'manual',
    ]);

    $this->actingAs($user);
    Livewire::test(Index::class)
        ->call('edit', $activity->id)
        ->set('routine_session_no', 2)
        ->call('save')
        ->assertHasErrors(['routine_session_no'])
        ->assertSet('editingId', $activity->id)
        ->set('confirmSessionChange', true)
        ->call('save')
        ->assertHasNoErrors();

    expect($activity->fresh()->routine_session_no)->toBe(2)
        ->and($attendance->fresh()->id)->toBe($attendance->id)
        ->and($attendance->fresh()->status)->toBe('present')
        ->and($attendance->fresh()->attendance_session_id)->toBe($session->id);
});
