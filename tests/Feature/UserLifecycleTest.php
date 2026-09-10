<?php

use App\Livewire\UserApprovals\Lifecycle;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Coach;
use App\Models\School;
use App\Models\SchoolUserMembership;
use App\Models\Student;
use App\Models\User;
use App\Services\DocumentSignatoryService;
use App\Services\UserLifecycleService;
use App\Support\SchoolContext;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function lifecycleMember(School $school, string $role): User
{
    $user = User::factory()->create(['system_role' => $role, 'is_active' => true, 'approval_status' => 'approved']);
    SchoolUserMembership::query()->create(['school_id' => $school->id, 'user_id' => $user->id, 'is_active' => true]);
    setPermissionsTeamId($school->id);
    $user->assignRole($role);

    return $user;
}

test('school inactivation preserves login and reactivation restores membership', function () {
    $school = School::factory()->create();
    app(SchoolContext::class)->set($school);
    $principal = lifecycleMember($school, 'principal');
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']))->withSession(['active_school_id' => $school->id]);

    Livewire::test(Lifecycle::class)->set('userId', $principal->id)->set('action', 'retired')->call('apply')->assertHasNoErrors();

    expect($principal->fresh()->is_active)->toBeTrue();
    $this->assertDatabaseHas('school_user_memberships', ['user_id' => $principal->id, 'is_active' => false, 'exit_reason' => 'retired']);
    Livewire::test(Lifecycle::class)->set('userId', $principal->id)->set('action', 'active')->call('apply')->assertHasNoErrors();
    $this->assertDatabaseHas('school_user_memberships', ['user_id' => $principal->id, 'is_active' => true, 'left_at' => null]);
});

test('total inactivation invalidates existing login and can be reversed', function () {
    $school = School::factory()->create();
    app(SchoolContext::class)->set($school);
    $user = lifecycleMember($school, 'principal');
    $admin = User::factory()->create(['system_role' => 'super_admin']);
    $this->actingAs($admin)->withSession(['active_school_id' => $school->id]);
    app(UserLifecycleService::class)->account($user->id, false);

    $this->actingAs($user->fresh())->get(route('dashboard'))->assertRedirect(route('login'));
    $this->assertGuest();
    $this->actingAs($admin);
    app(SchoolContext::class)->set($school);
    app(UserLifecycleService::class)->account($user->id, true);
    expect($user->fresh()->is_active)->toBeTrue();
});

test('transfer preserves original history and activates destination only after acceptance', function (string $role) {
    $source = School::factory()->create();
    $target = School::factory()->create();
    app(SchoolContext::class)->set($source);
    $user = lifecycleMember($source, $role);
    $student = $role === 'student' ? Student::factory()->create(['school_id' => $source->id, 'user_id' => $user->id]) : null;
    if ($role === 'coach') {
        Coach::query()->create(['user_id' => $user->id, 'name' => $user->name, 'is_active' => true]);
    }
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']))->withSession(['active_school_id' => $source->id]);
    $transfer = app(UserLifecycleService::class)->requestTransfer($user->id, $target->id, 'Pindah mengikuti keluarga');
    $this->assertDatabaseHas('school_user_memberships', ['user_id' => $user->id, 'school_id' => $source->id, 'is_active' => true]);
    $this->assertDatabaseMissing('school_user_memberships', ['user_id' => $user->id, 'school_id' => $target->id]);
    app(SchoolContext::class)->set($target);
    setPermissionsTeamId($target->id);
    $this->withSession(['active_school_id' => $target->id]);
    $year = AcademicYear::factory()->create(['school_id' => $target->id]);
    $class = Classroom::query()->create(['name' => 'VII A', 'grade' => 7, 'is_active' => true]);

    Livewire::test(Lifecycle::class)->set('yearId', $year->id)->set('classroomId', $class->id)
        ->call('resolve', $transfer->id, 'accepted')->assertHasNoErrors();

    $this->assertDatabaseHas('user_transfers', ['id' => $transfer->id, 'status' => 'accepted']);
    $this->assertDatabaseHas('school_user_memberships', ['user_id' => $user->id, 'school_id' => $source->id, 'is_active' => false]);
    $this->assertDatabaseHas('school_user_memberships', ['user_id' => $user->id, 'school_id' => $target->id, 'is_active' => true]);
    expect($user->fresh()->is_active)->toBeTrue();
    if ($student) {
        $this->assertDatabaseHas('students', ['id' => $student->id, 'school_id' => $source->id, 'status' => 'transferred']);
        $this->assertDatabaseHas('students', ['school_id' => $target->id, 'user_id' => $user->id, 'nisn' => $student->nisn]);
        $this->assertDatabaseHas('student_enrollments', ['school_id' => $target->id, 'academic_year_id' => $year->id, 'classroom_id' => $class->id]);
    }
    Livewire::test(Lifecycle::class)->call('resolve', $transfer->id, 'accepted')->assertStatus(409);
})->with(['student', 'coach', 'school_admin', 'principal']);

test('student cannot be active in two schools even when active flag is omitted', function () {
    $school = School::factory()->create();
    $user = lifecycleMember($school, 'student');
    $other = School::factory()->create();

    expect(fn () => SchoolUserMembership::query()->create(['school_id' => $other->id, 'user_id' => $user->id]))
        ->toThrow(ValidationException::class);
    $this->assertDatabaseCount('school_user_memberships', 1);
});

test('only destination may accept and rejection does not move the user', function () {
    $source = School::factory()->create();
    $target = School::factory()->create();
    app(SchoolContext::class)->set($source);
    $user = lifecycleMember($source, 'principal');
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']));
    $transfer = app(UserLifecycleService::class)->requestTransfer($user->id, $target->id, 'Pindah sekolah baru');

    Livewire::test(Lifecycle::class)->call('resolve', $transfer->id, 'accepted')->assertNotFound();
    app(SchoolContext::class)->set($target);
    Livewire::test(Lifecycle::class)->call('resolve', $transfer->id, 'rejected')->assertHasNoErrors();
    $this->assertDatabaseHas('school_user_memberships', ['school_id' => $source->id, 'user_id' => $user->id, 'is_active' => true]);
    $this->assertDatabaseMissing('school_user_memberships', ['school_id' => $target->id, 'user_id' => $user->id]);
});

test('school admin cannot disable a shared account globally or act on themselves', function () {
    $school = School::factory()->create();
    app(SchoolContext::class)->set($school);
    $admin = lifecycleMember($school, 'school_admin');
    $coach = lifecycleMember($school, 'coach');
    SchoolUserMembership::query()->create(['school_id' => School::factory()->create()->id, 'user_id' => $coach->id]);
    $this->actingAs($admin);

    Livewire::test(Lifecycle::class)->set('userId', $coach->id)->set('action', 'disable')->call('apply')->assertForbidden();
    Livewire::test(Lifecycle::class)->set('userId', $admin->id)->set('action', 'inactive')->call('apply')->assertForbidden();
    expect($coach->fresh()->is_active)->toBeTrue();
});

test('ordinary user cannot manage lifecycles', function () {
    $this->actingAs(User::factory()->create());
    Livewire::test(Lifecycle::class)->assertForbidden();
});

test('inactive school membership still permits login without restoring school access', function () {
    $school = School::factory()->create();
    app(SchoolContext::class)->set($school);
    $user = lifecycleMember($school, 'principal');
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']));
    app(UserLifecycleService::class)->membership($user->id, false, 'retired');
    auth()->logout();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])->assertSessionHasNoErrors();
    $this->assertAuthenticatedAs($user);
    $this->get(route('dashboard'))->assertOk();
    $this->assertDatabaseHas('school_user_memberships', ['user_id' => $user->id, 'is_active' => false]);
});

test('pending transfer cannot be duplicated and can be cancelled by the source', function () {
    $school = School::factory()->create();
    $target = School::factory()->create();
    app(SchoolContext::class)->set($school);
    $user = lifecycleMember($school, 'principal');
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']));
    $service = app(UserLifecycleService::class);
    $transfer = $service->requestTransfer($user->id, $target->id, 'Pindah sekolah baru');

    expect(fn () => $service->requestTransfer($user->id, $target->id, 'Pindah sekolah baru'))->toThrow(ValidationException::class);
    Livewire::test(Lifecycle::class)->call('resolve', $transfer->id, 'cancelled')->assertHasNoErrors();

    $this->assertDatabaseHas('user_transfers', ['id' => $transfer->id, 'status' => 'cancelled']);
    $this->assertDatabaseHas('school_user_memberships', ['school_id' => $school->id, 'user_id' => $user->id, 'is_active' => true]);
});

test('inactive coach membership cannot be bypassed through a legacy signatory profile', function () {
    $school = School::factory()->create();
    app(SchoolContext::class)->set($school);
    $user = lifecycleMember($school, 'coach');
    Coach::query()->create(['user_id' => $user->id, 'name' => $user->name, 'is_active' => true]);
    SchoolUserMembership::query()->where('user_id', $user->id)->update(['is_active' => false]);

    expect(app(DocumentSignatoryService::class)->usersForSchool($school->id)->modelKeys())->not->toContain($user->id);
});

test('a stale lifecycle form cannot change membership in a newly selected school', function () {
    $school = School::factory()->create();
    $other = School::factory()->create();
    app(SchoolContext::class)->set($school);
    $user = lifecycleMember($school, 'coach');
    SchoolUserMembership::query()->create(['user_id' => $user->id, 'school_id' => $other->id]);
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']));
    $component = Livewire::test(Lifecycle::class)->set('userId', $user->id);
    app(SchoolContext::class)->set($other);

    $component->call('apply')->assertStatus(409);

    $this->assertDatabaseHas('school_user_memberships', ['user_id' => $user->id, 'school_id' => $other->id, 'is_active' => true]);
});
