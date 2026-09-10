<?php

use App\Livewire\UserApprovals\Lifecycle;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Models\UserTransfer;
use App\Support\SchoolContext;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']));
});

test('student without account transfers with preserved biodata and history in either direction', function (bool $incoming) {
    $source = School::factory()->create();
    $target = School::factory()->create();
    app(SchoolContext::class)->set($source);
    $student = Student::factory()->create(['school_id' => $source->id, 'nisn' => $incoming ? '0012345678' : null]);
    $sourceYear = AcademicYear::factory()->create(['school_id' => $source->id]);
    $sourceClass = Classroom::query()->create(['name' => 'Asal', 'grade' => 7, 'is_active' => true]);
    $oldEnrollment = StudentEnrollment::query()->create(['student_id' => $student->id, 'academic_year_id' => $sourceYear->id, 'classroom_id' => $sourceClass->id, 'status' => 'active']);
    app(SchoolContext::class)->set($target);
    $year = AcademicYear::factory()->create(['school_id' => $target->id]);
    $classroom = Classroom::query()->create(['name' => 'Tujuan', 'grade' => 8, 'is_active' => true]);
    $usersBefore = User::query()->count();
    if ($incoming) {
        Livewire::test(Lifecycle::class)->set('sourceSchoolId', $source->id)->set('nisn', $student->nisn)
            ->set('incomingYearId', $year->id)->set('incomingClassroomId', $classroom->id)
            ->set('incomingReason', 'Pindah mengikuti keluarga')->call('requestIncoming')->assertHasNoErrors();
    } else {
        app(SchoolContext::class)->set($source);
        Livewire::test(Lifecycle::class)->set('studentId', $student->id)->set('studentTargetSchoolId', $target->id)
            ->set('studentTransferReason', 'Pindah mengikuti keluarga')->call('requestStudent')->assertHasNoErrors();
    }
    $transfer = UserTransfer::query()->sole();
    expect($transfer->user_id)->toBeNull();
    expect($student->fresh()->status)->toBe('active');
    Livewire::test(Lifecycle::class)->assertSee($student->name)->call('resolve', $transfer->id, 'accepted')->assertNotFound();
    app(SchoolContext::class)->set($incoming ? $source : $target);
    Livewire::test(Lifecycle::class)->set('yearId', $year->id)->set('classroomId', $classroom->id)
        ->call('resolve', $transfer->id, 'accepted')->assertHasNoErrors();
    expect($student->fresh()->status)->toBe('transferred');
    expect($oldEnrollment->fresh()->status)->toBe('transferred');
    $this->assertDatabaseHas('students', ['id' => $transfer->fresh()->target_student_id, 'school_id' => $target->id, 'name' => $student->name, 'user_id' => null, 'status' => 'active']);
    expect(Student::withoutGlobalScope('school')->findOrFail($transfer->fresh()->target_student_id)->birth_date->toDateString())->toBe($student->birth_date->toDateString());
    $this->assertDatabaseHas('student_enrollments', ['student_id' => $transfer->fresh()->target_student_id, 'academic_year_id' => $year->id, 'classroom_id' => $classroom->id]);
    $this->assertDatabaseCount('users', $usersBefore);
    $this->assertDatabaseCount('school_user_memberships', 0);
    $this->assertDatabaseCount('students', 2);
    Livewire::test(Lifecycle::class)->call('resolve', $transfer->id, 'accepted')->assertStatus(409);
})->with([false, true]);

test('student transfer prevents duplicate pending proposals and cross school selection', function () {
    $source = School::factory()->create();
    $target = School::factory()->create();
    app(SchoolContext::class)->set($source);
    $student = Student::factory()->create(['school_id' => $source->id]);
    Livewire::test(Lifecycle::class)->set('studentId', $student->id)->set('studentTargetSchoolId', $target->id)
        ->set('studentTransferReason', 'Pindah mengikuti keluarga')->call('requestStudent')->assertHasNoErrors();
    Livewire::test(Lifecycle::class)->set('studentId', $student->id)->set('studentTargetSchoolId', $target->id)
        ->set('studentTransferReason', 'Pengajuan yang sama')->call('requestStudent')->assertHasErrors('transfer');
    app(SchoolContext::class)->set($target);
    Livewire::test(Lifecycle::class)->set('studentId', $student->id)->set('studentTargetSchoolId', $source->id)
        ->set('studentTransferReason', 'Pilihan siswa sekolah lain')->call('requestStudent')->assertNotFound();
    $this->assertDatabaseCount('user_transfers', 1);
});

test('rejecting or cancelling an accountless transfer does not create destination students', function (string $decision) {
    $source = School::factory()->create();
    $target = School::factory()->create();
    app(SchoolContext::class)->set($source);
    $student = Student::factory()->create(['school_id' => $source->id]);
    Livewire::test(Lifecycle::class)->set('studentId', $student->id)->set('studentTargetSchoolId', $target->id)
        ->set('studentTransferReason', 'Pindah mengikuti keluarga')->call('requestStudent')->assertHasNoErrors();
    if ($decision === 'rejected') {
        app(SchoolContext::class)->set($target);
    }
    Livewire::test(Lifecycle::class)->call('resolve', UserTransfer::query()->sole()->id, $decision)->assertHasNoErrors();
    expect($student->fresh()->status)->toBe('active');
    $this->assertDatabaseCount('students', 1);
    $this->assertDatabaseHas('user_transfers', ['status' => $decision]);
})->with(['cancelled', 'rejected']);

test('account linked while transfer is pending follows student without another account being created', function () {
    $source = School::factory()->create();
    $target = School::factory()->create();
    app(SchoolContext::class)->set($source);
    $student = Student::factory()->create(['school_id' => $source->id]);
    Livewire::test(Lifecycle::class)->set('studentId', $student->id)->set('studentTargetSchoolId', $target->id)
        ->set('studentTransferReason', 'Pindah mengikuti keluarga')->call('requestStudent')->assertHasNoErrors();
    $account = User::factory()->create(['system_role' => 'student']);
    $student->update(['user_id' => $account->id]);
    app(SchoolContext::class)->set($target);
    $year = AcademicYear::factory()->create(['school_id' => $target->id]);
    $classroom = Classroom::query()->create(['name' => 'Tujuan', 'grade' => 8, 'is_active' => true]);
    Livewire::test(Lifecycle::class)->set('yearId', $year->id)->set('classroomId', $classroom->id)
        ->call('resolve', UserTransfer::query()->sole()->id, 'accepted')->assertHasNoErrors();
    $this->assertDatabaseHas('students', ['school_id' => $target->id, 'user_id' => $account->id]);
    $this->assertDatabaseHas('school_user_memberships', ['school_id' => $target->id, 'user_id' => $account->id, 'is_active' => true]);
    $this->assertDatabaseCount('users', 2);
});

test('student cannot access school transfer management', function () {
    app(SchoolContext::class)->set(School::factory()->create());
    $this->actingAs(User::factory()->create(['system_role' => 'student']));
    Livewire::test(Lifecycle::class)->assertForbidden();
});

test('return transfers reuse school records across a chain without relying on account or NISN', function (?string $nisn) {
    $schools = School::factory()->count(3)->create();
    app(SchoolContext::class)->set($schools[0]);
    $original = Student::factory()->create(['school_id' => $schools[0]->id, 'nisn' => $nisn]);
    $current = $original;
    $records = [$schools[0]->id => $original->id];
    foreach ([1, 2, 0, 1] as $step => $index) {
        $destination = $schools[$index];
        app(SchoolContext::class)->set($current->school);
        Livewire::test(Lifecycle::class)->set('studentId', $current->id)->set('studentTargetSchoolId', $destination->id)
            ->set('studentTransferReason', 'Pindah mengikuti keluarga')->call('requestStudent')->assertHasNoErrors();
        $transfer = UserTransfer::query()->where('status', 'pending')->sole();
        app(SchoolContext::class)->set($destination);
        $year = AcademicYear::factory()->create(['school_id' => $destination->id, 'name' => 'Tahun kepindahan '.$step]);
        $classroom = Classroom::query()->create(['name' => 'Kelas '.$step, 'grade' => 8, 'is_active' => true]);
        Livewire::test(Lifecycle::class)->set('yearId', $year->id)->set('classroomId', $classroom->id)
            ->call('resolve', $transfer->id, 'accepted')->assertHasNoErrors();
        expect($current->fresh()->status)->toBe('transferred');
        $returnedId = $transfer->fresh()->target_student_id;
        if (isset($records[$destination->id])) {
            expect($returnedId)->toBe($records[$destination->id]);
        }
        $records[$destination->id] = $returnedId;
        $current = Student::query()->findOrFail($returnedId);
        expect($current->status)->toBe('active');
    }
    $this->assertDatabaseCount('students', 3);
    $this->assertDatabaseCount('users', 1);
    $this->assertDatabaseCount('student_enrollments', 4);
    expect(Student::withoutGlobalScope('school')->where('status', 'active')->count())->toBe(1);
})->with([null, '0012345678']);

test('return transfer reuses only closed yearly enrollment and preserves its previous placement', function (string $status) {
    $source = School::factory()->create();
    $destination = School::factory()->create();
    app(SchoolContext::class)->set($destination);
    $original = Student::factory()->create(['school_id' => $destination->id, 'status' => 'transferred', 'nisn' => null]);
    $year = AcademicYear::factory()->create(['school_id' => $destination->id]);
    $classroom = Classroom::query()->create(['name' => 'Kelas lama', 'grade' => 8, 'is_active' => true]);
    $enrollment = StudentEnrollment::query()->create(['student_id' => $original->id, 'academic_year_id' => $year->id, 'classroom_id' => $classroom->id, 'status' => $status]);
    app(SchoolContext::class)->set($source);
    $current = Student::factory()->create(['school_id' => $source->id, 'nisn' => null]);
    UserTransfer::query()->create(['student_id' => $original->id, 'target_student_id' => $current->id, 'from_school_id' => $destination->id, 'to_school_id' => $source->id, 'status' => 'accepted', 'role' => 'student', 'reason' => 'Transfer sebelumnya', 'requested_by' => auth()->id()]);
    Livewire::test(Lifecycle::class)->set('studentId', $current->id)->set('studentTargetSchoolId', $destination->id)
        ->set('studentTransferReason', 'Kembali ke sekolah lama')->call('requestStudent')->assertHasNoErrors();
    $transfer = UserTransfer::query()->where('status', 'pending')->sole();
    app(SchoolContext::class)->set($destination);
    $component = Livewire::test(Lifecycle::class)->set('yearId', $year->id)->set('classroomId', $classroom->id)
        ->call('resolve', $transfer->id, 'accepted');
    if (in_array($status, ['inactive', 'transferred'], true)) {
        $component->assertHasNoErrors();
        expect($transfer->fresh()->status)->toBe('accepted');
        expect($transfer->fresh()->previous_target_enrollment['status'])->toBe($status);
        expect($transfer->fresh()->previous_target_enrollment['id'])->toBe($enrollment->id);
        expect($current->fresh()->status)->toBe('transferred');
        expect($original->fresh()->status)->toBe('active');
        expect($enrollment->fresh()->status)->toBe('active');
    } else {
        $component->assertHasErrors('transfer');
        expect($transfer->fresh()->status)->toBe('pending');
        expect($current->fresh()->status)->toBe('active');
        expect($original->fresh()->status)->toBe('transferred');
        expect($enrollment->fresh()->status)->toBe($status);
    }
    $this->assertDatabaseCount('students', 2);
    $this->assertDatabaseCount('student_enrollments', 1);
})->with(['inactive', 'transferred', 'active', 'graduated']);
