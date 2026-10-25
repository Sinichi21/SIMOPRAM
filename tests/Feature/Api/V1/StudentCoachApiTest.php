<?php

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Coach;
use App\Models\School;
use App\Models\ScoutLevel;
use App\Models\Student;
use App\Models\User;
use App\Support\SchoolContext;

test('student list is scoped to the selected school and returns placement data', function () {
    $school = School::factory()->create();
    $otherSchool = School::factory()->create();
    $student = Student::factory()->create([
        'school_id' => $school->id,
        'name' => 'Roster Student',
        'status' => 'active',
    ]);
    Student::factory()->create([
        'school_id' => $school->id,
        'name' => 'Roster Inactive',
        'status' => 'inactive',
    ]);
    Student::factory()->create(['school_id' => $otherSchool->id]);
    $user = User::factory()->create(['is_active' => true, 'system_role' => 'super_admin']);
    $token = $user->createToken('Android')->plainTextToken;

    $this->withToken($token)->withHeader('X-School-Id', (string) $school->id)
        ->getJson('/api/v1/students?search=Roster&status=active')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $student->id)
        ->assertJsonPath('data.0.name', $student->name);
});

test('student creation saves school placement and ignores client tenant identifiers', function () {
    $school = School::factory()->create();
    $otherSchool = School::factory()->create();
    $academicYear = AcademicYear::factory()->create([
        'school_id' => $school->id,
        'name' => '2026/2027 API',
        'is_active' => true,
    ]);
    $otherAcademicYear = AcademicYear::factory()->create(['school_id' => $otherSchool->id]);
    $schoolContext = app(SchoolContext::class);
    $schoolContext->set($school);
    $classroom = Classroom::query()->create(['name' => 'API-7A', 'grade' => 7, 'is_active' => true]);
    $schoolContext->clear();
    $scoutLevel = ScoutLevel::query()->create([
        'code' => 'API-PENGGALANG',
        'name' => 'Penggalang API',
        'sort_order' => 1,
    ]);
    $user = User::factory()->create(['is_active' => true, 'system_role' => 'super_admin']);
    $token = $user->createToken('Android')->plainTextToken;

    $this->withToken($token)->withHeader('X-School-Id', (string) $school->id)
        ->postJson('/api/v1/students', [
            'nis' => 'API-001',
            'nisn' => 'API-NISN-001',
            'name' => 'Siswa API',
            'gender' => 'L',
            'status' => 'active',
            'academic_year_id' => $academicYear->id,
            'classroom_id' => $classroom->id,
            'scout_level_id' => $scoutLevel->id,
            'school_id' => $otherSchool->id,
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Siswa API')
        ->assertJsonPath('data.current_enrollment.academic_year.id', $academicYear->id)
        ->assertJsonPath('data.current_enrollment.classroom.id', $classroom->id)
        ->assertJsonPath('data.current_scout_level.id', $scoutLevel->id);

    $this->assertDatabaseHas('students', [
        'school_id' => $school->id,
        'nis' => 'API-001',
        'name' => 'Siswa API',
    ]);
    $this->assertDatabaseHas('student_enrollments', [
        'school_id' => $school->id,
        'academic_year_id' => $academicYear->id,
        'classroom_id' => $classroom->id,
    ]);
    $this->assertDatabaseMissing('student_enrollments', [
        'academic_year_id' => $otherAcademicYear->id,
        'classroom_id' => $classroom->id,
    ]);
});

test('student creation rejects placement from another school', function () {
    $school = School::factory()->create();
    $otherSchool = School::factory()->create();
    $otherAcademicYear = AcademicYear::factory()->create(['school_id' => $otherSchool->id]);
    $schoolContext = app(SchoolContext::class);
    $schoolContext->set($school);
    $classroom = Classroom::query()->create(['name' => 'API-7B', 'grade' => 7, 'is_active' => true]);
    $schoolContext->clear();
    $scoutLevel = ScoutLevel::query()->create([
        'code' => 'API-PENEGAK',
        'name' => 'Penegak API',
        'sort_order' => 2,
    ]);
    $user = User::factory()->create(['is_active' => true, 'system_role' => 'super_admin']);
    $token = $user->createToken('Android')->plainTextToken;

    $this->withToken($token)->withHeader('X-School-Id', (string) $school->id)
        ->postJson('/api/v1/students', [
            'nis' => 'API-002',
            'name' => 'Siswa Invalid',
            'gender' => 'P',
            'status' => 'active',
            'academic_year_id' => $otherAcademicYear->id,
            'classroom_id' => $classroom->id,
            'scout_level_id' => $scoutLevel->id,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('academic_year_id');

    $this->assertDatabaseMissing('students', ['nis' => 'API-002']);
});

test('student updates synchronize the linked account membership', function () {
    $school = School::factory()->create();
    $academicYear = AcademicYear::factory()->create(['school_id' => $school->id, 'is_active' => true]);
    $schoolContext = app(SchoolContext::class);
    $schoolContext->set($school);
    $classroom = Classroom::query()->create(['name' => 'API-8A', 'grade' => 8, 'is_active' => true]);
    $schoolContext->clear();
    $scoutLevel = ScoutLevel::query()->create([
        'code' => 'API-UPDATE',
        'name' => 'Penggalang Update',
        'sort_order' => 3,
    ]);
    $studentAccount = User::factory()->create(['system_role' => 'student', 'is_active' => true]);
    $student = Student::factory()->create(['school_id' => $school->id, 'user_id' => $studentAccount->id]);
    $studentAccount->schoolMemberships()->create([
        'school_id' => $school->id,
        'is_active' => true,
        'joined_at' => today(),
    ]);
    $admin = User::factory()->create(['is_active' => true, 'system_role' => 'super_admin']);
    $token = $admin->createToken('Android')->plainTextToken;

    $this->withToken($token)->withHeader('X-School-Id', (string) $school->id)
        ->putJson('/api/v1/students/'.$student->id, [
            'nis' => $student->nis,
            'nisn' => $student->nisn,
            'name' => 'Siswa Nonaktif',
            'gender' => $student->gender,
            'status' => 'inactive',
            'academic_year_id' => $academicYear->id,
            'classroom_id' => $classroom->id,
            'scout_level_id' => $scoutLevel->id,
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Siswa Nonaktif')
        ->assertJsonPath('data.status', 'inactive');

    $this->assertDatabaseHas('school_user_memberships', [
        'school_id' => $school->id,
        'user_id' => $studentAccount->id,
        'is_active' => false,
    ]);
});

test('student details do not reveal another school records', function () {
    $school = School::factory()->create();
    $otherSchool = School::factory()->create();
    $foreignStudent = Student::factory()->create(['school_id' => $otherSchool->id]);
    $user = User::factory()->create(['is_active' => true, 'system_role' => 'super_admin']);
    $token = $user->createToken('Android')->plainTextToken;

    $this->withToken($token)->withHeader('X-School-Id', (string) $school->id)
        ->getJson('/api/v1/students/'.$foreignStudent->id)
        ->assertNotFound();
});

test('coaches can be created only in the selected school', function () {
    $school = School::factory()->create();
    $otherSchool = School::factory()->create();
    $user = User::factory()->create(['is_active' => true, 'system_role' => 'super_admin']);
    $token = $user->createToken('Android')->plainTextToken;

    $this->withToken($token)->withHeader('X-School-Id', (string) $school->id)
        ->postJson('/api/v1/coaches', [
            'nip' => 'COACH-001',
            'name' => 'Pembina API',
            'gender' => 'L',
            'position' => 'Pembina',
            'school_id' => $otherSchool->id,
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Pembina API');

    $this->assertDatabaseHas('coaches', [
        'school_id' => $school->id,
        'nip' => 'COACH-001',
    ]);
});

test('coach list is scoped to the selected school and supports search', function () {
    $school = School::factory()->create();
    $otherSchool = School::factory()->create();
    $schoolContext = app(SchoolContext::class);
    $schoolContext->set($school);
    $coach = Coach::query()->create(['nip' => 'COACH-LOCAL', 'name' => 'Pembina Lokal']);
    $schoolContext->clear();
    $schoolContext->set($otherSchool);
    Coach::query()->create(['nip' => 'COACH-FOREIGN', 'name' => 'Pembina Asing']);
    $schoolContext->clear();
    $user = User::factory()->create(['is_active' => true, 'system_role' => 'super_admin']);
    $token = $user->createToken('Android')->plainTextToken;

    $this->withToken($token)->withHeader('X-School-Id', (string) $school->id)
        ->getJson('/api/v1/coaches?search=Lokal')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $coach->id);
});

test('coach updates are limited to the selected school', function () {
    $school = School::factory()->create();
    $schoolContext = app(SchoolContext::class);
    $schoolContext->set($school);
    $coach = Coach::query()->create(['nip' => 'COACH-001', 'name' => 'Pembina Lama']);
    $schoolContext->clear();
    $user = User::factory()->create(['is_active' => true, 'system_role' => 'super_admin']);
    $token = $user->createToken('Android')->plainTextToken;

    $this->withToken($token)->withHeader('X-School-Id', (string) $school->id)
        ->putJson('/api/v1/coaches/'.$coach->id, [
            'nip' => 'COACH-001',
            'name' => 'Pembina Diperbarui',
            'gender' => 'L',
            'position' => 'Ketua Pembina',
            'certificate_number' => 'CERT-001',
            'is_active' => false,
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Pembina Diperbarui')
        ->assertJsonPath('data.is_active', false);
});

test('coach details do not reveal another school records', function () {
    $school = School::factory()->create();
    $otherSchool = School::factory()->create();
    $schoolContext = app(SchoolContext::class);
    $schoolContext->set($otherSchool);
    $foreignCoach = Coach::query()->create(['nip' => 'COACH-FOREIGN', 'name' => 'Pembina Asing']);
    $schoolContext->clear();
    $user = User::factory()->create(['is_active' => true, 'system_role' => 'super_admin']);
    $token = $user->createToken('Android')->plainTextToken;

    $this->withToken($token)->withHeader('X-School-Id', (string) $school->id)
        ->getJson('/api/v1/coaches/'.$foreignCoach->id)
        ->assertNotFound();
});

test('users without school data permissions cannot access the student API', function () {
    $school = School::factory()->create();
    $user = User::factory()->create(['is_active' => true]);
    $user->schoolMemberships()->create([
        'school_id' => $school->id,
        'is_active' => true,
        'joined_at' => today(),
    ]);
    $token = $user->createToken('Android')->plainTextToken;

    $this->withToken($token)->withHeader('X-School-Id', (string) $school->id)
        ->getJson('/api/v1/students')
        ->assertForbidden();
});

test('users without school data permissions cannot access the coach API', function () {
    $school = School::factory()->create();
    $user = User::factory()->create(['is_active' => true]);
    $user->schoolMemberships()->create([
        'school_id' => $school->id,
        'is_active' => true,
        'joined_at' => today(),
    ]);
    $token = $user->createToken('Android')->plainTextToken;

    $this->withToken($token)->withHeader('X-School-Id', (string) $school->id)
        ->getJson('/api/v1/coaches')
        ->assertForbidden();
});
