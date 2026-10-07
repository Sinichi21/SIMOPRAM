<?php

use App\Livewire\Reports\Lpj;
use App\Models\AcademicYear;
use App\Models\Activity;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\AttendanceSessionParticipant;
use App\Models\Classroom;
use App\Models\Coach;
use App\Models\Journal;
use App\Models\JournalAttachment;
use App\Models\School;
use App\Models\SchoolDocumentSetting;
use App\Models\ScoutLevel;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Services\LpjReportService;
use App\Services\ReportVerificationService;
use App\Support\SchoolContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
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

test('LPJ page renders its Livewire configuration and academic years', function () {
    $this->actingAs($this->user)
        ->withSession(['active_school_id' => $this->school->id])
        ->get(route('reports.lpj'))
        ->assertSeeLivewire(Lpj::class)
        ->assertSee('2026/2027');
});

test('LPJ uses the latest phone of the selected responsible coach on another export', function (string $mode) {
    $coach = Coach::query()->create([
        'school_id' => $this->school->id, 'name' => 'Pembina Terpilih', 'is_active' => true,
        'user_id' => $mode === 'user' ? $this->user->id : null,
    ]);
    SchoolDocumentSetting::query()->create([
        'school_id' => $this->school->id,
        'responsible_coach_id' => $mode === 'legacy' ? $coach->id : null,
        'responsible_signatory_user_id' => $mode === 'user' ? $this->user->id : null,
        'manual_signatories' => $mode === 'coach' ? ['responsible' => ['coach_id' => $coach->id]] : null,
    ]);
    $service = app(LpjReportService::class);
    $first = $service->build($this->academicYear->id, $this->semester->id, 'monthly', 9);
    expect($first['responsiblePhone'])->toBeNull();
    expect(view('reports.pdf.lpj', $first)->render())->toContain('<td>Nomor HP</td><td>: -</td>');

    $coach->update(['phone' => '081234567890']);
    $second = $service->build($this->academicYear->id, $this->semester->id, 'monthly', 9);

    expect($second['responsiblePhone'])->toBe('081234567890');
    expect(view('reports.pdf.lpj', $second)->render())->toContain('<td>Nomor HP</td><td>: 081234567890</td>');
})->with(['coach', 'user', 'legacy']);

test('LPJ does not borrow a legacy coach phone for a manually named signatory', function () {
    $coach = Coach::query()->create([
        'school_id' => $this->school->id, 'name' => 'Pembina Lama', 'is_active' => true, 'phone' => '081234567890',
    ]);
    SchoolDocumentSetting::query()->create([
        'school_id' => $this->school->id, 'responsible_coach_id' => $coach->id,
        'manual_signatories' => ['responsible' => ['name' => 'Penanggung Jawab Baru', 'position' => 'Pembina']],
    ]);

    $data = app(LpjReportService::class)->build($this->academicYear->id, $this->semester->id, 'monthly', 9);

    expect($data['responsiblePhone'])->toBeNull();
    expect(view('reports.pdf.lpj', $data)->render())
        ->toContain('Penanggung Jawab Baru', '<td>Nomor HP</td><td>: -</td>');
});

test('LPJ prints the document verification QR and code for each period type', function (string $periodType) {
    $data = app(LpjReportService::class)->build($this->academicYear->id, $this->semester->id, $periodType, 9);
    $service = app(ReportVerificationService::class);
    $verification = $service->issueReportDocument($this->school->id, 'lpj', str_repeat('a', 64));
    $data['verification'] = $verification;
    $data['verificationQrDataUri'] = $service->qrDataUri($verification);

    $html = view('reports.pdf.lpj', $data)->render();

    expect($html)->toContain('Verifikasi Dokumen SIMPRAM', $verification->code, $data['verificationQrDataUri']);
    expect(strpos($html, 'class="simpram-verification-footer"'))
        ->toBeLessThan(strpos($html, '<section'));
    expect(Pdf::loadHTML($html)->setPaper('a4')->output())->toStartWith('%PDF-');
})->with(['monthly', 'semester']);

test('LPJ attendance toggles independently update the download options', function () {
    Livewire::actingAs($this->user)
        ->test(Lpj::class)
        ->set('academicYearId', $this->academicYear->id)
        ->set('semesterId', $this->semester->id)
        ->set('month', 9)
        ->call('checkReport')
        ->assertSee('manual_student_attendance=0', false)
        ->assertSee('manual_coach_attendance=0', false)
        ->set('manualStudentAttendance', true)
        ->call('checkReport')
        ->assertSee('manual_student_attendance=1', false)
        ->assertSee('manual_coach_attendance=0', false)
        ->set('manualStudentAttendance', false)
        ->set('manualCoachAttendance', true)
        ->call('checkReport')
        ->assertSee('manual_student_attendance=0', false)
        ->assertSee('manual_coach_attendance=1', false);
});

test('LPJ download rejects invalid attendance modes', function () {
    $this->actingAs($this->user)
        ->withSession(['active_school_id' => $this->school->id])
        ->getJson(route('reports.lpj.pdf', [
            'academic_year_id' => $this->academicYear->id,
            'semester_id' => $this->semester->id,
            'period_type' => 'monthly',
            'month' => 9,
            'manual_student_attendance' => 'invalid',
            'manual_coach_attendance' => 'invalid',
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['manual_student_attendance', 'manual_coach_attendance']);
});

test('LPJ manual attendance leaves cells blank and preserves holidays independently', function (bool $manualStudents, bool $manualCoaches) {
    $data = app(LpjReportService::class)->build($this->academicYear->id, $this->semester->id, 'monthly', 9);
    $dates = collect([CarbonImmutable::parse('2026-09-04'), CarbonImmutable::parse('2026-09-11'), CarbonImmutable::parse('2026-09-18')]);
    $data['manualStudentAttendance'] = $manualStudents;
    $data['manualCoachAttendance'] = $manualCoaches;
    $data['reportMonths'] = collect([[
        'label' => 'September 2026', 'start' => $dates->first(), 'end' => $dates->last(),
        'dates' => $dates, 'dateRows' => collect(), 'documentation' => collect(),
        'coachRows' => collect([['coach' => (object) ['name' => 'Pembina Contoh'], 'statuses' => ['2026-09-04' => 'H', '2026-09-11' => 'LIBUR']]]),
        'routineSessions' => collect([[
            'label' => 'Sesi 1', 'startTime' => null, 'endTime' => null, 'dates' => $dates,
            'dateMeta' => ['2026-09-11' => ['isHoliday' => true, 'holidayLabel' => 'LIBUR SEKOLAH']],
            'documentation' => collect(),
            'attendanceClasses' => collect([[
                'classroom' => (object) ['name' => 'V A'],
                'students' => collect(range(1, 60))->map(fn (int $number): array => [
                    'name' => 'Siswa Contoh '.$number,
                    'className' => 'V A',
                    'statuses' => ['2026-09-04' => 'I', '2026-09-18' => 'H'],
                ]),
            ]]),
        ]]),
    ]]);

    $html = view('reports.pdf.lpj', $data)->render();

    expect($html)->toContain('LIBUR SEKOLAH', '>LIBUR</td>')
        ->toContain('<td class="status date-column">'.($manualStudents ? '' : 'I').'</td>')
        ->toContain('<td class="status">'.($manualCoaches ? '' : 'H').'</td>');

    $document = new DOMDocument;
    @$document->loadHTML($html);
    $xpath = new DOMXPath($document);
    $studentRows = $xpath->query('//table[contains(@class, "attendance-table")]/tbody/tr[td[contains(text(), "Siswa Contoh")]]');

    expect($studentRows)->toHaveCount(60);

    foreach ($studentRows as $row) {
        $cells = $xpath->query('./td', $row);
        expect($cells)->toHaveCount(6);
        expect(trim($cells->item(4)->textContent))->toBe('LIBUR SEKOLAH');
        expect($cells->item(4)->hasAttribute('rowspan'))->toBeFalse();
        expect(trim($cells->item(5)->textContent))->toBe($manualStudents ? '' : 'H');
    }

    $pdf = Pdf::loadHTML($html)->setPaper('a4');
    expect($pdf->output())->toStartWith('%PDF-');
    expect($pdf->getDomPDF()->getCanvas()->get_page_count())->toBeGreaterThan(3);
})->with([[false, false], [true, false], [false, true], [true, true]]);

test('monthly LPJ only contains activities from the selected month', function () {
    foreach ([['Latihan Agustus', '2026-08-10'], ['Latihan September', '2026-09-10']] as [$title, $date]) {
        Activity::factory()->create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->academicYear->id, 'semester_id' => $this->semester->id,
            'created_by' => $this->user->id, 'title' => $title, 'start_at' => $date.' 08:00:00', 'status' => 'completed',
        ]);
    }

    $data = app(LpjReportService::class)->build($this->academicYear->id, $this->semester->id, 'monthly', 8);
    $html = view('reports.pdf.lpj', $data)->render();

    $activityDataPosition = strpos($html, 'DATA KEGIATAN EKSTRA / PENGEMBANGAN DIRI');
    $monthlyReportPosition = strpos($html, 'LAPORAN PELAKSANAAN KEGIATAN EKSTRA / PENGEMBANGAN DIRI');

    expect($data['activities'])->toHaveCount(1)
        ->and($html)->toContain('Latihan Agustus')
        ->not->toContain('Latihan September')
        ->not->toContain('LEMBAR PENGESAHAN')
        ->and($activityDataPosition)->not->toBeFalse()
        ->and($monthlyReportPosition)->not->toBeFalse()
        ->and($activityDataPosition)->toBeLessThan($monthlyReportPosition)
        ->and($html)->toContain('class="signature-block"');
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
        ->and($sessionOne['documentation']->first()['attachments']->first()->pdf_src)
        ->toContain('data:image/png;base64,')
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

test('legacy routine activities with the same session number are separated by time slot', function () {
    $siaga = ScoutLevel::query()->create([
        'code' => 'siaga-legacy-session-lpj',
        'name' => 'Siaga',
        'sort_order' => 1,
    ]);
    $penggalang = ScoutLevel::query()->create([
        'code' => 'penggalang-legacy-session-lpj',
        'name' => 'Penggalang',
        'sort_order' => 2,
    ]);

    foreach ([
        [$siaga, '2026-08-07 12:00:00', '2026-08-07 13:30:00', 'Materi Siaga'],
        [$penggalang, '2026-08-07 12:30:00', '2026-08-07 15:00:00', 'Materi Penggalang'],
        [$siaga, '2026-08-14 12:00:00', '2026-08-14 13:30:00', 'Siaga Minggu Kedua'],
        [$penggalang, '2026-08-14 12:30:00', '2026-08-14 15:00:00', 'Penggalang Minggu Kedua'],
    ] as [$scoutLevel, $startAt, $endAt, $title]) {
        $activity = Activity::factory()->create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'semester_id' => $this->semester->id,
            'created_by' => $this->user->id,
            'title' => $title,
            'activity_type' => 'regular',
            'routine_session_no' => 1,
            'start_at' => $startAt,
            'end_at' => $endAt,
            'status' => 'completed',
        ]);

        $activity->scoutLevels()->sync([$scoutLevel->id]);
    }

    $data = app(LpjReportService::class)->build(
        $this->academicYear->id,
        $this->semester->id,
        'monthly',
        8
    );
    $reportMonth = $data['reportMonths']->first();
    $html = view('reports.pdf.lpj', $data)->render();

    expect($reportMonth['routineSessions'])->toHaveCount(2)
        ->and($reportMonth['dateRows']->first()['sessions'])->toHaveCount(2)
        ->and($reportMonth['routineSessions']->first()['startTime'])->toBe('12:00')
        ->and($reportMonth['routineSessions']->first()['endTime'])->toBe('13:30')
        ->and($reportMonth['routineSessions']->get(1)['startTime'])->toBe('12:30')
        ->and($reportMonth['routineSessions']->get(1)['endTime'])->toBe('15:00')
        ->and($html)->toContain(
            'SESI 1 - SIAGA',
            'SESI 2 - PENGGALANG',
            '12:00',
            '13:30',
            '12:30',
            '15:00'
        );
});

test('student attendance renders the class document heading only once for a long class', function () {
    $classroom = Classroom::query()->create([
        'name' => 'V A',
        'grade' => 5,
        'is_active' => true,
    ]);

    $activity = Activity::factory()->create([
        'school_id' => $this->school->id,
        'academic_year_id' => $this->academicYear->id,
        'semester_id' => $this->semester->id,
        'created_by' => $this->user->id,
        'title' => 'Latihan Rutin',
        'activity_type' => 'regular',
        'routine_session_no' => 1,
        'start_at' => '2026-09-04 12:00:00',
        'end_at' => '2026-09-04 13:30:00',
        'status' => 'completed',
    ]);

    $attendanceSession = AttendanceSession::query()->create([
        'activity_id' => $activity->id,
        'created_by' => $this->user->id,
        'name' => 'Absensi',
        'participant_scope' => 'all',
        'open_at' => '2026-09-04 12:00:00',
        'close_at' => '2026-09-04 13:30:00',
        'is_active' => true,
    ]);

    Student::factory()
        ->count(25)
        ->create(['school_id' => $this->school->id])
        ->each(function (Student $student) use ($classroom, $attendanceSession): void {
            StudentEnrollment::query()->create([
                'student_id' => $student->id,
                'academic_year_id' => $this->academicYear->id,
                'classroom_id' => $classroom->id,
                'status' => 'active',
            ]);

            AttendanceSessionParticipant::query()->create([
                'attendance_session_id' => $attendanceSession->id,
                'student_id' => $student->id,
            ]);
        });

    $data = app(LpjReportService::class)->build(
        $this->academicYear->id,
        $this->semester->id,
        'monthly',
        9
    );
    $html = view('reports.pdf.lpj', $data)->render();

    expect(substr_count($html, 'KELAS V A - SEPTEMBER 2026'))->toBe(1)
        ->and(substr_count($html, 'DAFTAR HADIR PESERTA/SISWA EKSTRA / PENGEMBANGAN DIRI'))->toBe(1);
});


test('LPJ distinguishes unscheduled participants when a student changes routine sessions across dates', function () {
    $classroom = Classroom::query()->create(['name' => 'V A', 'grade' => 5, 'is_active' => true]);
    $student = Student::factory()->create(['school_id' => $this->school->id, 'name' => 'Peserta Pindah Sesi']);
    StudentEnrollment::query()->create([
        'student_id' => $student->id, 'academic_year_id' => $this->academicYear->id,
        'classroom_id' => $classroom->id, 'status' => 'active',
    ]);

    foreach ([
        [1, '2026-09-04 12:00:00', true],
        [1, '2026-09-11 12:00:00', false],
        [2, '2026-09-11 13:30:00', true],
    ] as [$sessionNo, $startAt, $participates]) {
        $start = CarbonImmutable::parse($startAt);
        $activity = Activity::factory()->create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->academicYear->id,
            'semester_id' => $this->semester->id,
            'created_by' => $this->user->id,
            'title' => 'Latihan Sesi '.$sessionNo,
            'activity_type' => 'regular',
            'routine_session_no' => $sessionNo,
            'start_at' => $start,
            'end_at' => $start->addMinutes(90),
            'status' => 'completed',
        ]);
        $session = AttendanceSession::query()->create([
            'activity_id' => $activity->id, 'created_by' => $this->user->id,
            'name' => 'Absensi', 'participant_scope' => 'all',
            'open_at' => $start, 'close_at' => $start->addMinutes(90), 'is_active' => true,
        ]);

        if ($participates) {
            AttendanceSessionParticipant::query()->create([
                'attendance_session_id' => $session->id, 'student_id' => $student->id,
            ]);
            Attendance::query()->create([
                'attendance_session_id' => $session->id, 'activity_id' => $activity->id,
                'student_id' => $student->id, 'status' => 'present', 'source' => 'manual',
            ]);
        } else {
            // A different student is rostered so this session has a known scope.
            $other = Student::factory()->create(['school_id' => $this->school->id]);
            AttendanceSessionParticipant::query()->create([
                'attendance_session_id' => $session->id, 'student_id' => $other->id,
            ]);
        }
    }

    $report = app(LpjReportService::class)->build($this->academicYear->id, $this->semester->id, 'monthly', 9);
    $sessions = $report['reportMonths']->first()['routineSessions'];
    $sessionOne = $sessions->firstWhere('number', 1);
    $sessionTwo = $sessions->firstWhere('number', 2);
    $studentOne = $sessionOne['attendanceClasses']->first()['students']->firstWhere('studentId', $student->id);
    $studentTwo = $sessionTwo['attendanceClasses']->first()['students']->firstWhere('studentId', $student->id);

    expect($studentOne['statuses']['2026-09-04'])->toBe('H')
        ->and($studentOne['statuses']['2026-09-11'])->toBe('—')
        ->and($studentTwo['statuses']['2026-09-11'])->toBe('H')
        ->and(Attendance::query()->where('student_id', $student->id)->count())->toBe(2);
});

test('LPJ preflight detects overlapping student schedules without changing attendance', function () {
    $student = Student::factory()->create(['school_id' => $this->school->id]);

    foreach (['12:00:00', '12:30:00'] as $time) {
        $start = CarbonImmutable::parse('2026-09-11 '.$time);
        $activity = Activity::factory()->create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->academicYear->id,
            'semester_id' => $this->semester->id, 'created_by' => $this->user->id,
            'activity_type' => 'regular', 'routine_session_no' => $time === '12:00:00' ? 1 : 2,
            'start_at' => $start, 'end_at' => $start->addMinutes(90), 'status' => 'completed',
        ]);
        $session = AttendanceSession::query()->create([
            'activity_id' => $activity->id, 'created_by' => $this->user->id,
            'name' => 'Absensi', 'participant_scope' => 'all',
            'open_at' => $start, 'close_at' => $start->addMinutes(90), 'is_active' => true,
        ]);
        AttendanceSessionParticipant::query()->create([
            'attendance_session_id' => $session->id, 'student_id' => $student->id,
        ]);
        Attendance::query()->create([
            'attendance_session_id' => $session->id, 'activity_id' => $activity->id,
            'student_id' => $student->id, 'status' => 'present', 'source' => 'manual',
        ]);
    }

    $report = app(LpjReportService::class)->build($this->academicYear->id, $this->semester->id, 'monthly', 9);
    $warnings = app(\App\Services\LpjPreflightService::class)->warnings($report);

    expect(implode(' ', $warnings))->toContain('bertabrakan')
        ->and(Attendance::query()->where('student_id', $student->id)->count())->toBe(2);
});
