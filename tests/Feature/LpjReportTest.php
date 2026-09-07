<?php

use App\Models\AcademicYear;
use App\Models\Activity;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\AttendanceSessionParticipant;
use App\Models\Classroom;
use App\Models\Journal;
use App\Models\JournalAttachment;
use App\Models\School;
use App\Models\ScoutLevel;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Services\LpjReportService;
use App\Support\SchoolContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $this->user = User::factory()->create(['system_role' => 'super_admin', 'is_active' => true]);
    $this->school = School::factory()->create(['name' => 'SD Negeri Contoh']);
    app(SchoolContext::class)->set($this->school);
    $this->academicYear = AcademicYear::factory()->create([
        'school_id' => $this->school->id, 'name' => '2026/2027', 'start_date' => '2026-07-01', 'end_date' => '2027-06-30',
    ]);
    $this->semester = Semester::query()->create([
        'school_id' => $this->school->id, 'academic_year_id' => $this->academicYear->id, 'name' => 'Semester Ganjil',
        'semester_number' => 1, 'start_date' => '2026-07-01', 'end_date' => '2026-12-31', 'is_active' => true,
    ]);
});

test('monthly LPJ only contains activities from the selected month', function () {
    foreach ([['Latihan Agustus', '2026-08-10'], ['Latihan September', '2026-09-10']] as [$title, $date]) {
        Activity::factory()->create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->academicYear->id, 'semester_id' => $this->semester->id,
            'created_by' => $this->user->id, 'title' => $title, 'start_at' => $date.' 08:00:00', 'status' => 'completed',
        ]);
    }

    $data = app(LpjReportService::class)->build($this->academicYear->id, $this->semester->id, 'monthly', 8);
    $html = view('reports.pdf.lpj', $data)->render();

    expect($data['activities'])->toHaveCount(1)
        ->and($html)->toContain('Latihan Agustus')->not->toContain('Latihan September')->not->toContain('LEMBAR PENGESAHAN');
});

test('semester LPJ has one cover and approval page and includes all semester months', function () {
    foreach ([['Latihan Juli', '2026-07-10'], ['Latihan November', '2026-11-10']] as [$title, $date]) {
        Activity::factory()->create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->academicYear->id, 'semester_id' => $this->semester->id,
            'created_by' => $this->user->id, 'title' => $title, 'start_at' => $date.' 08:00:00', 'status' => 'published',
        ]);
    }

    $data = app(LpjReportService::class)->build($this->academicYear->id, $this->semester->id, 'semester');
    $html = view('reports.pdf.lpj', $data)->render();

    expect($data['activities'])->toHaveCount(2)
        ->and(substr_count($html, 'LEMBAR PENGESAHAN'))->toBe(1)
        ->and($html)->toContain('LAPORAN PERTANGGUNG JAWABAN', 'Latihan Juli', 'Latihan November');
});

test('monthly LPJ rejects a month outside the semester', function () {
    app(LpjReportService::class)->build($this->academicYear->id, $this->semester->id, 'monthly', 2);
})->throws(HttpException::class);

test('routine LPJ groups monthly attendance and documentation by routine session', function () {
    Storage::fake('public');

    $siaga = ScoutLevel::query()->create([
        'code' => 'siaga-lpj',
        'name' => 'Siaga LPJ',
        'sort_order' => 1,
    ]);
    $penggalang = ScoutLevel::query()->create([
        'code' => 'penggalang-lpj',
        'name' => 'Penggalang LPJ',
        'sort_order' => 2,
    ]);

    $classThree = Classroom::query()->create([
        'name' => 'III A',
        'grade' => 3,
        'is_active' => true,
    ]);
    $classFive = Classroom::query()->create([
        'name' => 'V A',
        'grade' => 5,
        'is_active' => true,
    ]);

    $siagaStudent = Student::factory()->create([
        'school_id' => $this->school->id,
        'name' => 'Peserta Siaga',
    ]);
    $penggalangStudent = Student::factory()->create([
        'school_id' => $this->school->id,
        'name' => 'Peserta Penggalang',
    ]);

    foreach ([
        [$siagaStudent, $classThree],
        [$penggalangStudent, $classFive],
    ] as [$student, $classroom]) {
        StudentEnrollment::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $this->academicYear->id,
            'classroom_id' => $classroom->id,
            'status' => 'active',
        ]);
    }

    $activities = [];

    foreach ([
        [1, $siaga, $siagaStudent, '2026-09-04 14:00:00', 'present', 'PBB Dasar'],
        [2, $penggalang, $penggalangStudent, '2026-09-04 15:00:00', 'absent', 'Tali Temali'],
        [1, $siaga, $siagaStudent, '2026-09-11 14:00:00', 'excused', 'Permainan Siaga'],
        [2, $penggalang, $penggalangStudent, '2026-09-11 15:00:00', 'present', 'Semaphore'],
    ] as [$sessionNo, $scoutLevel, $student, $startAt, $attendanceStatus, $material]) {
        $start = CarbonImmutable::parse($startAt);
        $activity = Activity::factory()->create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'semester_id' => $this->semester->id,
            'created_by' => $this->user->id,
            'title' => $material,
            'activity_type' => 'regular',
            'routine_session_no' => $sessionNo,
            'start_at' => $start,
            'end_at' => $start->addHour(),
            'status' => 'completed',
        ]);
        $activity->scoutLevels()->sync([$scoutLevel->id]);

        $attendanceSession = AttendanceSession::query()->create([
            'activity_id' => $activity->id,
            'created_by' => $this->user->id,
            'name' => 'Absensi',
            'participant_scope' => 'all',
            'open_at' => $start,
            'close_at' => $start->addHour(),
            'is_active' => true,
        ]);
        AttendanceSessionParticipant::query()->create([
            'attendance_session_id' => $attendanceSession->id,
            'student_id' => $student->id,
        ]);
        Attendance::query()->create([
            'attendance_session_id' => $attendanceSession->id,
            'activity_id' => $activity->id,
            'student_id' => $student->id,
            'status' => $attendanceStatus,
            'source' => 'manual',
        ]);

        $journal = Journal::query()->create([
            'activity_id' => $activity->id,
            'created_by' => $this->user->id,
            'material' => $material,
            'activity_description' => $material,
            'status' => 'published',
            'published_at' => $start,
        ]);

        if ($start->day === 4) {
            $path = 'lpj/session-'.$sessionNo.'.png';
            Storage::disk('public')->put($path, 'fake-image');
            JournalAttachment::query()->create([
                'journal_id' => $journal->id,
                'uploaded_by' => $this->user->id,
                'original_name' => 'dokumentasi-sesi-'.$sessionNo.'.png',
                'path' => $path,
                'mime_type' => 'image/png',
                'size_bytes' => 10,
            ]);
        }

        $activities[] = $activity;
    }

    $data = app(LpjReportService::class)->build(
        $this->academicYear->id,
        $this->semester->id,
        'monthly',
        9
    );
    $reportMonth = $data['reportMonths']->first();
    $sessionOne = $reportMonth['routineSessions']->firstWhere('number', 1);
    $sessionTwo = $reportMonth['routineSessions']->firstWhere('number', 2);
    $html = view('reports.pdf.lpj', $data)->render();

    expect($reportMonth['dateRows']->first()['sessions'])->toHaveCount(2)
        ->and($reportMonth['routineSessions'])->toHaveCount(2)
        ->and($sessionOne['dates'])->toHaveCount(2)
        ->and($sessionTwo['dates'])->toHaveCount(2)
        ->and($sessionOne['attendanceClasses'])->toHaveCount(1)
        ->and($sessionTwo['attendanceClasses'])->toHaveCount(1)
        ->and($sessionOne['attendanceClasses']->first()['classroom']->name)->toBe('III A')
        ->and($sessionTwo['attendanceClasses']->first()['classroom']->name)->toBe('V A')
        ->and($sessionOne['attendanceClasses']->first()['students']->first()['name'])->toBe('Peserta Siaga')
        ->and($sessionTwo['attendanceClasses']->first()['students']->first()['name'])->toBe('Peserta Penggalang')
        ->and($sessionOne['attendanceClasses']->first()['students']->first()['statuses']['2026-09-04'])->toBe('H')
        ->and($sessionOne['attendanceClasses']->first()['students']->first()['statuses']['2026-09-11'])->toBe('I')
        ->and($sessionTwo['attendanceClasses']->first()['students']->first()['statuses']['2026-09-04'])->toBe('A')
        ->and($sessionTwo['attendanceClasses']->first()['students']->first()['statuses']['2026-09-11'])->toBe('H')
        ->and($sessionOne['documentation'])->toHaveCount(1)
        ->and($sessionTwo['documentation'])->toHaveCount(1)
        ->and($html)->toContain(
            'SESI 1 - SIAGA LPJ',
            'SESI 2 - PENGGALANG LPJ',
            'KELAS III A',
            'KELAS V A',
            'dokumentasi-sesi-1.png',
            'dokumentasi-sesi-2.png'
        );
});

test('routine LPJ excludes special activities from the monthly report', function () {
    Activity::factory()->create([
        'school_id' => $this->school->id,
        'academic_year_id' => $this->academicYear->id,
        'semester_id' => $this->semester->id,
        'created_by' => $this->user->id,
        'title' => 'Perkemahan Khusus',
        'activity_type' => 'camp',
        'routine_session_no' => null,
        'start_at' => '2026-09-20 08:00:00',
        'end_at' => '2026-09-20 16:00:00',
        'status' => 'completed',
    ]);

    $data = app(LpjReportService::class)->build(
        $this->academicYear->id,
        $this->semester->id,
        'monthly',
        9
    );
    $html = view('reports.pdf.lpj', $data)->render();

    expect($data['activities'])->toHaveCount(0)
        ->and($html)->not->toContain('Perkemahan Khusus');
});
