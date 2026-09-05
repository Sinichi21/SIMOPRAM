<?php

use App\Livewire\Attendances\Manage;
use App\Models\AcademicYear;
use App\Models\Activity;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Classroom;
use App\Models\School;
use App\Models\ScoutLevel;
use App\Models\ScoutUnit;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentScoutLevel;
use App\Models\User;
use App\Support\SchoolContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

/** @return array{activity: Activity, classroom: Classroom, unit: ScoutUnit, allowed: ScoutLevel, excluded: ScoutLevel} */
function attendanceScopeContext(): array
{
    $user = User::factory()->create(['system_role' => 'super_admin', 'is_active' => true]);
    test()->actingAs($user);
    $school = School::factory()->create();
    app(SchoolContext::class)->set($school);
    session(['active_school_id' => $school->id]);
    $year = AcademicYear::factory()->create([
        'school_id' => $school->id, 'name' => '2026/2027',
        'start_date' => '2026-07-01', 'end_date' => '2027-06-30',
    ]);
    $activity = Activity::factory()->create([
        'school_id' => $school->id, 'academic_year_id' => $year->id, 'created_by' => $user->id,
        'start_at' => '2026-09-10 08:00:00', 'end_at' => '2026-09-10 12:00:00',
    ]);
    $allowed = ScoutLevel::query()->create(['code' => 'PENGGALANG', 'name' => 'Penggalang', 'sort_order' => 2]);
    $excluded = ScoutLevel::query()->create(['code' => 'SIAGA', 'name' => 'Siaga', 'sort_order' => 1]);
    $activity->scoutLevels()->attach($allowed);
    $classroom = Classroom::query()->create(['name' => 'Kelas Pilihan', 'grade' => 7, 'is_active' => true]);
    $unit = ScoutUnit::query()->create([
        'academic_year_id' => $year->id, 'scout_level_id' => $allowed->id,
        'name' => 'Regu Pilihan', 'unit_type' => 'regu', 'is_active' => true,
    ]);

    return compact('activity', 'classroom', 'unit', 'allowed', 'excluded');
}

function attendanceScopeStudent(Activity $activity, Classroom $classroom, ScoutUnit $unit, string $nis, ?ScoutLevel $level, array $history = []): Student
{
    $student = Student::query()->create(['nis' => $nis, 'name' => 'Peserta '.$nis, 'status' => 'active']);
    StudentEnrollment::query()->create([
        'student_id' => $student->id, 'academic_year_id' => $activity->academic_year_id,
        'classroom_id' => $classroom->id, 'status' => 'active',
    ]);
    $unit->memberships()->create(['student_id' => $student->id, 'position' => 'member', 'joined_at' => '2026-07-01']);
    if ($level) {
        StudentScoutLevel::query()->create(array_merge([
            'student_id' => $student->id, 'scout_level_id' => $level->id,
            'academic_year_id' => $activity->academic_year_id, 'is_active' => true,
        ], $history));
    }

    return $student;
}

test('session participant scopes always respect active scout levels for the activity year', function (string $scope) {
    ['activity' => $activity, 'classroom' => $classroom, 'unit' => $unit, 'allowed' => $allowed, 'excluded' => $excluded] = attendanceScopeContext();
    $included = attendanceScopeStudent($activity, $classroom, $unit, 'INCLUDED', $allowed);
    attendanceScopeStudent($activity, $classroom, $unit, 'OTHER-LEVEL', $excluded);
    attendanceScopeStudent($activity, $classroom, $unit, 'OLD-HISTORY', $allowed, ['is_active' => false]);
    attendanceScopeStudent($activity, $classroom, $unit, 'NO-LEVEL', null);
    $inactive = attendanceScopeStudent($activity, $classroom, $unit, 'INACTIVE', $allowed);
    $inactive->update(['status' => 'inactive']);
    $otherYear = AcademicYear::factory()->create([
        'school_id' => $activity->school_id, 'name' => '2025/2026',
        'start_date' => '2025-07-01', 'end_date' => '2026-06-30',
    ]);
    attendanceScopeStudent($activity, $classroom, $unit, 'OTHER-YEAR', $allowed, ['academic_year_id' => $otherYear->id]);
    $outside = Student::query()->create(['nis' => 'OUTSIDE-GROUP', 'name' => 'Peserta di luar kelas dan regu', 'status' => 'active']);
    StudentScoutLevel::query()->create([
        'student_id' => $outside->id, 'scout_level_id' => $allowed->id,
        'academic_year_id' => $activity->academic_year_id, 'is_active' => true,
    ]);

    Livewire::test(Manage::class, ['activityId' => $activity->id])
        ->set('participant_scope', $scope)
        ->set('participant_scope_id', match ($scope) {
            'classroom' => $classroom->id, 'scout_unit' => $unit->id,
            'scout_level' => $allowed->id, 'all' => null,
        })
        ->call('saveSession')->assertHasNoErrors();

    $session = $activity->attendanceSessions()->sole();
    $expected = in_array($scope, ['all', 'scout_level'], true) ? [$included->id, $outside->id] : [$included->id];
    expect($session->participants()->orderBy('student_id')->pluck('student_id')->all())->toBe($expected);
})->with(['all', 'classroom', 'scout_unit', 'scout_level']);

test('session selectors list classrooms units and allowed levels and clear old selections', function () {
    ['activity' => $activity, 'classroom' => $classroom, 'unit' => $unit, 'allowed' => $allowed, 'excluded' => $excluded] = attendanceScopeContext();
    ScoutUnit::query()->create([
        'academic_year_id' => $activity->academic_year_id, 'scout_level_id' => $excluded->id,
        'name' => 'Barung Tidak Sesuai', 'unit_type' => 'barung', 'is_active' => true,
    ]);

    Livewire::test(Manage::class, ['activityId' => $activity->id])
        ->set('participant_scope', 'classroom')->assertSee($classroom->name)
        ->set('participant_scope_id', $classroom->id)
        ->set('participant_scope', 'scout_unit')->assertSet('participant_scope_id', null)
        ->assertSee($unit->name)->assertDontSee('Barung Tidak Sesuai')
        ->set('participant_scope_id', $unit->id)
        ->set('participant_scope', 'scout_level')->assertSet('participant_scope_id', null)
        ->assertSee($allowed->name)->assertDontSee($excluded->name)
        ->set('participant_scope_id', $allowed->id)
        ->set('participant_scope', 'all')->assertSet('participant_scope_id', null);
});

test('a scout level outside the activity is rejected without leaving an empty session', function () {
    ['activity' => $activity, 'excluded' => $excluded] = attendanceScopeContext();

    Livewire::test(Manage::class, ['activityId' => $activity->id])
        ->set('participant_scope', 'scout_level')->set('participant_scope_id', $excluded->id)
        ->call('saveSession')->assertHasErrors('participant_scope_id');

    $this->assertDatabaseCount('attendance_sessions', 0);
});

test('a classroom from another school is rejected without leaving a session', function () {
    ['activity' => $activity] = attendanceScopeContext();
    $otherSchool = School::factory()->create();
    $classroom = new Classroom(['name' => 'Kelas Sekolah Lain', 'grade' => 7, 'is_active' => true]);
    $classroom->school_id = $otherSchool->id;
    $classroom->save();
    $component = Livewire::test(Manage::class, ['activityId' => $activity->id])
        ->set('participant_scope', 'classroom')->set('participant_scope_id', $classroom->id);

    expect(fn () => $component->call('saveSession'))->toThrow(ModelNotFoundException::class);

    $this->assertDatabaseCount('attendance_sessions', 0);
});

test('all active participants are retained when an activity has no scout level restriction', function () {
    ['activity' => $activity, 'classroom' => $classroom, 'unit' => $unit, 'allowed' => $allowed, 'excluded' => $excluded] = attendanceScopeContext();
    $activity->scoutLevels()->detach();
    $first = attendanceScopeStudent($activity, $classroom, $unit, 'FIRST', $allowed);
    $second = attendanceScopeStudent($activity, $classroom, $unit, 'SECOND', $excluded);

    Livewire::test(Manage::class, ['activityId' => $activity->id])->call('saveSession')->assertHasNoErrors();

    expect($activity->attendanceSessions()->sole()->participants()->orderBy('student_id')->pluck('student_id')->all())
        ->toBe([$first->id, $second->id]);
});

test('unrecorded session participants are refreshed when saving or activating the session', function (string $action) {
    ['activity' => $activity, 'classroom' => $classroom, 'unit' => $unit, 'allowed' => $allowed, 'excluded' => $excluded] = attendanceScopeContext();
    $included = attendanceScopeStudent($activity, $classroom, $unit, 'INCLUDED', $allowed);
    $outside = attendanceScopeStudent($activity, $classroom, $unit, 'OUTSIDE', $excluded);
    $session = AttendanceSession::query()->create([
        'activity_id' => $activity->id, 'name' => 'Sesi Lama', 'participant_scope' => 'all',
        'open_at' => $activity->start_at, 'close_at' => $activity->end_at, 'is_active' => false,
    ]);
    $session->participants()->create(['student_id' => $outside->id]);
    $component = Livewire::test(Manage::class, ['activityId' => $activity->id]);

    if ($action === 'save') {
        $component->call('editSession', $session->id)->call('saveSession')->assertHasNoErrors();
    } else {
        $component->call('toggleSession', $session->id)->assertHasNoErrors();
        expect($session->fresh()->is_active)->toBeTrue();
    }

    expect($session->participants()->pluck('student_id')->all())->toBe([$included->id]);
})->with(['save', 'activate']);

test('changing participant scope after attendance has been recorded preserves the session and attendance', function () {
    ['activity' => $activity, 'classroom' => $classroom, 'unit' => $unit, 'allowed' => $allowed] = attendanceScopeContext();
    $student = attendanceScopeStudent($activity, $classroom, $unit, 'RECORDED', $allowed);
    $session = AttendanceSession::query()->create([
        'activity_id' => $activity->id, 'name' => 'Sesi Berjalan', 'participant_scope' => 'all',
        'open_at' => $activity->start_at, 'close_at' => $activity->end_at,
    ]);
    $session->participants()->create(['student_id' => $student->id]);
    $attendance = Attendance::query()->create([
        'activity_id' => $activity->id, 'attendance_session_id' => $session->id,
        'student_id' => $student->id, 'status' => 'present', 'source' => 'manual',
    ]);

    Livewire::test(Manage::class, ['activityId' => $activity->id])
        ->call('editSession', $session->id)->set('participant_scope', 'classroom')
        ->set('participant_scope_id', $classroom->id)->call('saveSession')->assertHasErrors('participant_scope');

    expect($session->fresh()->participant_scope)->toBe('all');
    expect($session->participants()->pluck('student_id')->all())->toBe([$student->id]);
    $this->assertModelExists($attendance);
});
