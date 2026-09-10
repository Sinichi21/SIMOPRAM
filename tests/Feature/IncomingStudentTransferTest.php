<?php

use App\Livewire\UserApprovals\Lifecycle;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\School;
use App\Models\SchoolUserMembership;
use App\Models\Student;
use App\Models\User;
use App\Models\UserTransfer;
use App\Support\SchoolContext;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function incomingTransferData(): array
{
    $source = School::factory()->create();
    $target = School::factory()->create();
    $studentUser = User::factory()->create(['system_role' => 'student', 'is_active' => true]);
    SchoolUserMembership::query()->create(['school_id' => $source->id, 'user_id' => $studentUser->id]);
    $student = Student::factory()->create(['school_id' => $source->id, 'user_id' => $studentUser->id, 'nisn' => '0012345678']);
    $admins = [];
    foreach ([$source, $target] as $school) {
        $admin = User::factory()->create(['system_role' => 'school_admin']);
        SchoolUserMembership::query()->create(['school_id' => $school->id, 'user_id' => $admin->id]);
        setPermissionsTeamId($school->id);
        $admin->assignRole('school_admin');
        $admins[] = $admin;
    }
    app(SchoolContext::class)->set($target);
    $year = AcademicYear::factory()->create(['school_id' => $target->id]);
    $classroom = Classroom::query()->create(['name' => 'Kelas Penerima', 'grade' => 8, 'is_active' => true]);

    return compact('source', 'target', 'studentUser', 'student', 'admins', 'year', 'classroom');
}

function submitIncomingTransfer(array $data): void
{
    Livewire::test(Lifecycle::class)
        ->set('sourceSchoolId', $data['source']->id)->set('nisn', '0012345678')
        ->set('incomingYearId', $data['year']->id)->set('incomingClassroomId', $data['classroom']->id)
        ->set('incomingReason', 'Siswa mengajukan pindah ke sekolah kami')
        ->call('requestIncoming')->assertHasNoErrors();
}

test('destination proposes student transfer and source approval applies the stored destination placement', function () {
    $data = incomingTransferData();
    $this->actingAs($data['admins'][1])->withSession(['active_school_id' => $data['target']->id]);

    submitIncomingTransfer($data);

    $transfer = UserTransfer::query()->sole();
    expect($transfer->initiated_by_destination)->toBeTrue();
    $this->assertDatabaseHas('school_user_memberships', ['school_id' => $data['source']->id, 'user_id' => $data['studentUser']->id, 'is_active' => true]);
    $this->assertDatabaseMissing('school_user_memberships', ['school_id' => $data['target']->id, 'user_id' => $data['studentUser']->id]);
    Livewire::test(Lifecycle::class)->call('resolve', $transfer->id, 'accepted')->assertNotFound();

    app(SchoolContext::class)->set($data['source']);
    setPermissionsTeamId($data['source']->id);
    $this->actingAs($data['admins'][0])->withSession(['active_school_id' => $data['source']->id]);
    Livewire::test(Lifecycle::class)->assertSee('Kelas Penerima')
        ->set('yearId', 99999)->set('classroomId', 99999)
        ->call('resolve', $transfer->id, 'accepted')->assertHasNoErrors();

    expect(app(SchoolContext::class)->id())->toBe($data['source']->id);
    $this->assertDatabaseHas('user_transfers', ['id' => $transfer->id, 'status' => 'accepted', 'resolved_by' => $data['admins'][0]->id]);
    $this->assertDatabaseHas('school_user_memberships', ['school_id' => $data['source']->id, 'user_id' => $data['studentUser']->id, 'is_active' => false]);
    $this->assertDatabaseHas('school_user_memberships', ['school_id' => $data['target']->id, 'user_id' => $data['studentUser']->id, 'is_active' => true]);
    $this->assertDatabaseHas('student_enrollments', ['school_id' => $data['target']->id, 'academic_year_id' => $data['year']->id, 'classroom_id' => $data['classroom']->id]);
    $this->assertDatabaseHas('students', ['id' => $data['student']->id, 'status' => 'transferred']);
});

test('requester can cancel and source can reject without changing membership', function (string $decision) {
    $data = incomingTransferData();
    $this->actingAs($data['admins'][1]);
    submitIncomingTransfer($data);
    $transfer = UserTransfer::query()->sole();

    if ($decision === 'rejected') {
        app(SchoolContext::class)->set($data['source']);
        setPermissionsTeamId($data['source']->id);
        $this->actingAs($data['admins'][0]);
    }
    Livewire::test(Lifecycle::class)->call('resolve', $transfer->id, $decision)->assertHasNoErrors();

    expect($transfer->fresh()->status)->toBe($decision);
    $this->assertDatabaseHas('school_user_memberships', ['school_id' => $data['source']->id, 'user_id' => $data['studentUser']->id, 'is_active' => true]);
    $this->assertDatabaseMissing('school_user_memberships', ['school_id' => $data['target']->id, 'user_id' => $data['studentUser']->id]);
})->with(['cancelled', 'rejected']);

test('incoming request rejects unknown NISN and duplicate pending request', function () {
    $data = incomingTransferData();
    $this->actingAs($data['admins'][1]);

    Livewire::test(Lifecycle::class)->set('sourceSchoolId', $data['source']->id)
        ->set('nisn', 'not-found')->set('incomingYearId', $data['year']->id)->set('incomingClassroomId', $data['classroom']->id)
        ->set('incomingReason', 'Usulan perpindahan siswa')->call('requestIncoming')->assertHasErrors('nisn');
    $this->assertDatabaseCount('user_transfers', 0);
    submitIncomingTransfer($data);
    Livewire::test(Lifecycle::class)->set('sourceSchoolId', $data['source']->id)
        ->set('nisn', '0012345678')->set('incomingYearId', $data['year']->id)->set('incomingClassroomId', $data['classroom']->id)
        ->set('incomingReason', 'Usulan perpindahan siswa')->call('requestIncoming')->assertHasErrors('transfer');
    $this->assertDatabaseCount('user_transfers', 1);
});

test('unrelated school cannot approve incoming proposal', function () {
    $data = incomingTransferData();
    $this->actingAs($data['admins'][1]);
    submitIncomingTransfer($data);
    $transfer = UserTransfer::query()->sole();
    app(SchoolContext::class)->set(School::factory()->create());
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']));

    Livewire::test(Lifecycle::class)->call('resolve', $transfer->id, 'accepted')->assertNotFound();

    expect($transfer->fresh()->status)->toBe('pending');
});

test('failed incoming approval preserves membership and restores source school context', function () {
    $data = incomingTransferData();
    $this->actingAs($data['admins'][1]);
    submitIncomingTransfer($data);
    $transfer = UserTransfer::query()->sole();
    Student::factory()->create(['school_id' => $data['target']->id, 'nisn' => '0012345678']);
    app(SchoolContext::class)->set($data['source']);
    setPermissionsTeamId($data['source']->id);
    $this->actingAs($data['admins'][0]);

    Livewire::test(Lifecycle::class)->call('resolve', $transfer->id, 'accepted')->assertHasErrors('transfer');

    expect(app(SchoolContext::class)->id())->toBe($data['source']->id);
    expect($transfer->fresh()->status)->toBe('pending');
    $this->assertDatabaseHas('school_user_memberships', ['school_id' => $data['source']->id, 'user_id' => $data['studentUser']->id, 'is_active' => true]);
    $this->assertDatabaseMissing('school_user_memberships', ['school_id' => $data['target']->id, 'user_id' => $data['studentUser']->id]);
});
