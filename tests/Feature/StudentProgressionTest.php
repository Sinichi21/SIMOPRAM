<?php

use App\Livewire\Students\Progression;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\School;
use App\Models\SchoolUserMembership;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Services\StudentProgressionService;
use App\Support\SchoolContext;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function progressionData(int $grade = 7): array
{
    $school = School::factory()->create(['level' => 'SMP']);
    app(SchoolContext::class)->set($school);
    $year = AcademicYear::factory()->create(['school_id' => $school->id, 'name' => '2025/2026', 'start_date' => '2025-07-01', 'end_date' => '2026-06-30']);
    $next = AcademicYear::factory()->create(['school_id' => $school->id, 'name' => '2026/2027', 'start_date' => '2026-07-01', 'end_date' => '2027-06-30']);
    $class = Classroom::query()->create(['name' => 'Kelas Asal', 'grade' => $grade, 'is_active' => true]);
    $target = Classroom::query()->create(['name' => 'Kelas Tujuan', 'grade' => $grade + 1, 'is_active' => true]);
    $student = Student::factory()->create(['school_id' => $school->id]);
    $enrollment = StudentEnrollment::query()->create(['student_id' => $student->id, 'academic_year_id' => $year->id, 'classroom_id' => $class->id, 'status' => 'active']);

    return compact('school', 'year', 'next', 'class', 'target', 'student', 'enrollment');
}

test('promotion creates next year enrollment without copying biodata or changing old classroom', function () {
    $data = progressionData();
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']));

    Livewire::test(Progression::class)->set('sourceYearId', $data['year']->id)->set('sourceClassId', $data['class']->id)
        ->call('selectAll')->set('targetYearId', $data['next']->id)->set('targetClassId', $data['target']->id)
        ->call('process')->assertHasNoErrors();

    $this->assertDatabaseCount('students', 1);
    $this->assertDatabaseHas('student_enrollments', ['id' => $data['enrollment']->id, 'classroom_id' => $data['class']->id, 'status' => 'completed']);
    $this->assertDatabaseHas('student_enrollments', ['student_id' => $data['student']->id, 'academic_year_id' => $data['next']->id, 'classroom_id' => $data['target']->id, 'status' => 'active']);
    expect(fn () => app(StudentProgressionService::class)->process($data['year']->id, $data['class']->id, [$data['student']->id], 'promote', $data['next']->id, $data['target']->id))
        ->toThrow(ValidationException::class);
    $this->assertDatabaseCount('student_enrollments', 2);
});

test('graduation marks alumni and closes school membership without blocking login', function () {
    $data = progressionData(9);
    $user = User::factory()->create(['system_role' => 'student', 'is_active' => true]);
    $data['student']->update(['user_id' => $user->id]);
    SchoolUserMembership::query()->create(['school_id' => $data['school']->id, 'user_id' => $user->id]);
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']));

    Livewire::test(Progression::class)->set('sourceYearId', $data['year']->id)->set('sourceClassId', $data['class']->id)
        ->call('selectAll')->set('action', 'graduate')->call('process')->assertHasNoErrors();

    $this->assertDatabaseHas('students', ['id' => $data['student']->id, 'status' => 'graduated']);
    $this->assertDatabaseHas('school_user_memberships', ['user_id' => $user->id, 'is_active' => false, 'exit_reason' => 'graduated']);
    expect($user->fresh()->is_active)->toBeTrue();
});

test('grade seven cannot be graduated as a final grade of junior high school', function () {
    $data = progressionData(7);
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']));

    Livewire::test(Progression::class)->set('sourceYearId', $data['year']->id)->set('sourceClassId', $data['class']->id)
        ->call('selectAll')->set('action', 'graduate')->call('process')->assertHasErrors('action');

    expect($data['student']->fresh()->status)->toBe('active');
});

test('retained students get same grade in new year and unselected students remain unchanged', function () {
    $data = progressionData();
    $other = Student::factory()->create(['school_id' => $data['school']->id]);
    $otherEnrollment = StudentEnrollment::query()->create(['student_id' => $other->id, 'academic_year_id' => $data['year']->id, 'classroom_id' => $data['class']->id, 'status' => 'active']);
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']));

    app(StudentProgressionService::class)->process($data['year']->id, $data['class']->id, [$data['student']->id], 'repeat', $data['next']->id, $data['class']->id);

    $this->assertDatabaseHas('student_enrollments', ['student_id' => $data['student']->id, 'academic_year_id' => $data['next']->id, 'classroom_id' => $data['class']->id]);
    expect($otherEnrollment->fresh()->status)->toBe('active');
});

test('wrong target grade rejects the batch without altering source enrollment', function () {
    $data = progressionData();
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']));

    Livewire::test(Progression::class)->set('sourceYearId', $data['year']->id)->set('sourceClassId', $data['class']->id)
        ->call('selectAll')->set('targetYearId', $data['next']->id)->set('targetClassId', $data['class']->id)
        ->call('process')->assertHasErrors('targetClassId');

    expect($data['enrollment']->fresh()->status)->toBe('active');
    $this->assertDatabaseCount('student_enrollments', 1);
});

test('cross school students cannot be inserted into the progression batch', function () {
    $data = progressionData();
    $other = Student::factory()->create();
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']));

    Livewire::test(Progression::class)->set('sourceYearId', $data['year']->id)->set('sourceClassId', $data['class']->id)
        ->set('studentIds', [$data['student']->id, $other->id])->set('targetYearId', $data['next']->id)->set('targetClassId', $data['target']->id)
        ->call('process')->assertHasErrors('studentIds');

    expect($data['enrollment']->fresh()->status)->toBe('active');
    $this->assertDatabaseCount('student_enrollments', 1);
});

test('final grade students cannot be promoted beyond the school level', function () {
    $data = progressionData(9);
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']));

    Livewire::test(Progression::class)->set('sourceYearId', $data['year']->id)->set('sourceClassId', $data['class']->id)
        ->call('selectAll')->set('targetYearId', $data['next']->id)->set('targetClassId', $data['target']->id)
        ->call('process')->assertHasErrors('action');

    expect($data['enrollment']->fresh()->status)->toBe('active');
});
