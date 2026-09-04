<?php

use App\Livewire\Attendances\Index;
use App\Livewire\Attendances\Manage;
use App\Models\AcademicYear;
use App\Models\Activity;
use App\Models\AssessmentConfig;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\AttendanceSessionParticipant;
use App\Models\Classroom;
use App\Models\School;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Services\AssessmentService;
use App\Support\SchoolContext;
use Livewire\Livewire;

test('attendance activities can be filtered by academic year semester and status', function () {
    $user = User::factory()->create([
        'system_role' => 'super_admin',
        'is_active' => true,
    ]);
    $school = School::factory()->create();

    $this->actingAs($user)
        ->withSession(['active_school_id' => $school->id])
        ->get(route('dashboard'))
        ->assertOk();

    $firstAcademicYear = AcademicYear::factory()->create([
        'school_id' => $school->id,
        'name' => '2025/2026',
        'start_date' => '2025-07-01',
        'end_date' => '2026-06-30',
    ]);
    $secondAcademicYear = AcademicYear::factory()->create([
        'school_id' => $school->id,
        'name' => '2026/2027',
        'start_date' => '2026-07-01',
        'end_date' => '2027-06-30',
    ]);
    $firstSemester = Semester::query()->create([
        'school_id' => $school->id,
        'academic_year_id' => $firstAcademicYear->id,
        'name' => 'Semester Ganjil',
        'semester_number' => 1,
        'start_date' => '2025-07-01',
        'end_date' => '2025-12-31',
    ]);
    $secondSemester = Semester::query()->create([
        'school_id' => $school->id,
        'academic_year_id' => $secondAcademicYear->id,
        'name' => 'Semester Genap',
        'semester_number' => 2,
        'start_date' => '2027-01-01',
        'end_date' => '2027-06-30',
    ]);

    Activity::factory()->create([
        'school_id' => $school->id,
        'academic_year_id' => $firstAcademicYear->id,
        'semester_id' => $firstSemester->id,
        'created_by' => $user->id,
        'title' => 'Latihan Lama',
        'status' => 'completed',
    ]);
    Activity::factory()->create([
        'school_id' => $school->id,
        'academic_year_id' => $secondAcademicYear->id,
        'semester_id' => $secondSemester->id,
        'created_by' => $user->id,
        'title' => 'Latihan Aktif',
        'status' => 'published',
    ]);
    Activity::factory()->create([
        'school_id' => $school->id,
        'academic_year_id' => $secondAcademicYear->id,
        'semester_id' => $secondSemester->id,
        'created_by' => $user->id,
        'title' => 'Latihan Draft',
        'status' => 'draft',
    ]);

    Livewire::test(Index::class)
        ->set('filterAcademicYearId', (string) $secondAcademicYear->id)
        ->assertDontSee('Latihan Lama')
        ->assertSee('Latihan Aktif')
        ->assertSee('Latihan Draft')
        ->set('filterSemesterId', (string) $secondSemester->id)
        ->set('filterStatus', 'published')
        ->assertSee('Latihan Aktif')
        ->assertDontSee('Latihan Draft')
        ->set('filterAcademicYearId', (string) $firstAcademicYear->id)
        ->assertSet('filterSemesterId', '');
});

test('attendance session participants can be filtered by classroom', function () {
    $user = User::factory()->create([
        'system_role' => 'super_admin',
        'is_active' => true,
    ]);
    $school = School::factory()->create();

    $this->actingAs($user)
        ->withSession(['active_school_id' => $school->id])
        ->get(route('dashboard'))
        ->assertOk();

    $academicYear = AcademicYear::factory()->create([
        'school_id' => $school->id,
    ]);
    $activity = Activity::factory()->create([
        'school_id' => $school->id,
        'academic_year_id' => $academicYear->id,
        'created_by' => $user->id,
    ]);
    $session = AttendanceSession::query()->create([
        'activity_id' => $activity->id,
        'created_by' => $user->id,
        'name' => 'Absensi Latihan',
        'open_at' => now()->subHour(),
        'close_at' => now()->addHour(),
    ]);
    $firstClassroom = Classroom::query()->create([
        'name' => 'Kelas 7A',
        'is_active' => true,
    ]);
    $secondClassroom = Classroom::query()->create([
        'name' => 'Kelas 7B',
        'is_active' => true,
    ]);
    $firstStudent = Student::query()->create([
        'nis' => '1001',
        'name' => 'Andi Kelas Tujuh A',
    ]);
    $secondStudent = Student::query()->create([
        'nis' => '1002',
        'name' => 'Budi Kelas Tujuh B',
    ]);

    foreach ([
        [$firstStudent, $firstClassroom],
        [$secondStudent, $secondClassroom],
    ] as [$student, $classroom]) {
        StudentEnrollment::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'classroom_id' => $classroom->id,
            'status' => 'active',
        ]);
        AttendanceSessionParticipant::query()->create([
            'attendance_session_id' => $session->id,
            'student_id' => $student->id,
        ]);
    }

    Livewire::test(Manage::class, ['activityId' => $activity->id])
        ->call('selectSession', $session->id)
        ->assertSee('Kelas 7A')
        ->assertSee('Kelas 7B')
        ->set('participantClassroomId', (string) $firstClassroom->id)
        ->assertSee('Andi Kelas Tujuh A')
        ->assertDontSee('Budi Kelas Tujuh B');
});

test('active attendance sessions for the same activity cannot overlap', function () {
    $user = User::factory()->create([
        'system_role' => 'super_admin',
        'is_active' => true,
    ]);
    $school = School::factory()->create();

    $this->actingAs($user)
        ->withSession(['active_school_id' => $school->id])
        ->get(route('dashboard'))
        ->assertOk();

    $academicYear = AcademicYear::factory()->create([
        'school_id' => $school->id,
    ]);
    $activity = Activity::factory()->create([
        'school_id' => $school->id,
        'academic_year_id' => $academicYear->id,
        'created_by' => $user->id,
        'start_at' => '2026-09-10 08:00:00',
        'end_at' => '2026-09-10 16:00:00',
    ]);

    $morningSession = AttendanceSession::query()->create([
        'activity_id' => $activity->id,
        'created_by' => $user->id,
        'name' => 'Absensi Pagi',
        'open_at' => '2026-09-10 08:00:00',
        'close_at' => '2026-09-10 09:00:00',
        'is_active' => true,
    ]);

    Livewire::test(Manage::class, ['activityId' => $activity->id])
        ->set('name', 'Absensi Bentrok')
        ->set('open_at', '2026-09-10T08:30')
        ->set('late_after', '2026-09-10T08:45')
        ->set('close_at', '2026-09-10T09:30')
        ->call('saveSession')
        ->assertHasErrors(['open_at']);

    Livewire::test(Manage::class, ['activityId' => $activity->id])
        ->set('name', 'Absensi Siang')
        ->set('open_at', '2026-09-10T12:00')
        ->set('late_after', '2026-09-10T12:15')
        ->set('close_at', '2026-09-10T13:00')
        ->call('saveSession')
        ->assertHasNoErrors();

    expect($activity->attendanceSessions()->count())->toBe(2);

    Livewire::test(Manage::class, ['activityId' => $activity->id])
        ->assertSee('Nonaktifkan')
        ->call('toggleSession', $morningSession->id)
        ->assertHasNoErrors();

    expect($morningSession->refresh()->is_active)->toBeFalse();
});

test('inactive attendance sessions are excluded from attendance scores', function () {
    $school = School::factory()->create();
    app(SchoolContext::class)->set($school);
    $academicYear = AcademicYear::factory()->create([
        'school_id' => $school->id,
    ]);
    $semester = Semester::query()->create([
        'school_id' => $school->id,
        'academic_year_id' => $academicYear->id,
        'name' => 'Semester Ganjil',
        'semester_number' => 1,
        'start_date' => '2026-07-01',
        'end_date' => '2026-12-31',
    ]);
    $activity = Activity::factory()->create([
        'school_id' => $school->id,
        'academic_year_id' => $academicYear->id,
        'semester_id' => $semester->id,
    ]);
    $config = AssessmentConfig::query()->create([
        'school_id' => $school->id,
        'academic_year_id' => $academicYear->id,
        'semester_id' => $semester->id,
        'name' => 'Penilaian Kehadiran',
        'is_active' => true,
    ]);
    $student = Student::query()->create([
        'school_id' => $school->id,
        'nis' => 'ATTENDANCE-SCORE-001',
        'name' => 'Siswa Uji Kehadiran',
        'status' => 'active',
    ]);

    $activeSession = AttendanceSession::query()->create([
        'activity_id' => $activity->id,
        'name' => 'Sesi Benar',
        'open_at' => now()->subHours(2),
        'close_at' => now()->subHour(),
        'is_active' => true,
    ]);
    $inactiveSession = AttendanceSession::query()->create([
        'activity_id' => $activity->id,
        'name' => 'Sesi Salah',
        'open_at' => now()->subHours(2),
        'close_at' => now()->subHour(),
        'is_active' => false,
    ]);

    foreach ([$activeSession, $inactiveSession] as $session) {
        AttendanceSessionParticipant::query()->create([
            'attendance_session_id' => $session->id,
            'student_id' => $student->id,
        ]);
    }

    Attendance::query()->create([
        'attendance_session_id' => $activeSession->id,
        'activity_id' => $activity->id,
        'student_id' => $student->id,
        'status' => 'present',
        'source' => 'manual',
    ]);
    Attendance::query()->create([
        'attendance_session_id' => $inactiveSession->id,
        'activity_id' => $activity->id,
        'student_id' => $student->id,
        'status' => 'absent',
        'source' => 'manual',
    ]);

    expect(app(AssessmentService::class)->attendanceScore($config, $student))
        ->toBe(100.0);
});
