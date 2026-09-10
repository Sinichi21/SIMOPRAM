<?php

use App\Livewire\Reports\AttendanceDetail;
use App\Models\AcademicYear;
use App\Models\Activity;
use App\Models\ActivityAssessment;
use App\Models\AssessmentConfig;
use App\Models\AssessmentConfigItem;
use App\Models\AssessmentFactor;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\AttendanceSessionParticipant;
use App\Models\Classroom;
use App\Models\School;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Services\ActivityAssessmentService;
use App\Services\AssessmentService;
use App\Support\SchoolContext;
use Livewire\Livewire;

function detailAttendanceContext(): array
{
    $school = School::factory()->create();
    app(SchoolContext::class)->set($school);
    $year = AcademicYear::factory()->create(['school_id' => $school->id, 'start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'is_active' => true]);
    $first = Semester::query()->create(['academic_year_id' => $year->id, 'name' => 'Ganjil', 'semester_number' => 1, 'start_date' => '2026-07-01', 'end_date' => '2026-12-31', 'is_active' => true]);
    $second = Semester::query()->create(['academic_year_id' => $year->id, 'name' => 'Genap', 'semester_number' => 2, 'start_date' => '2027-01-01', 'end_date' => '2027-06-30', 'is_active' => false]);
    $classroom = Classroom::query()->create(['name' => 'Kelas III', 'grade' => 3, 'is_active' => true]);
    $student = Student::factory()->create(['school_id' => $school->id, 'status' => 'transferred']);
    StudentEnrollment::query()->create(['student_id' => $student->id, 'academic_year_id' => $year->id, 'classroom_id' => $classroom->id, 'status' => 'transferred']);

    return compact('school', 'year', 'first', 'second', 'classroom', 'student');
}

function detailAttendanceSession(array $data, string $date, string $type, ?string $status, bool $participant = true, bool $second = false): AttendanceSession
{
    $activity = Activity::factory()->create(['school_id' => $data['school']->id, 'academic_year_id' => $data['year']->id, 'semester_id' => $data[$second ? 'second' : 'first']->id, 'activity_type' => $type, 'start_at' => $date, 'end_at' => $date, 'status' => 'completed']);
    $session = AttendanceSession::query()->create(['activity_id' => $activity->id, 'name' => 'Sesi '.$type, 'open_at' => $date.' 08:00:00', 'close_at' => $date.' 10:00:00', 'is_active' => true, 'created_by' => auth()->id()]);
    if ($participant) {
        AttendanceSessionParticipant::query()->create(['attendance_session_id' => $session->id, 'student_id' => $data['student']->id]);
    }
    if ($status) {
        Attendance::query()->create(['attendance_session_id' => $session->id, 'activity_id' => $activity->id, 'student_id' => $data['student']->id, 'status' => $status, 'source' => 'manual']);
    }

    return $session;
}

beforeEach(function () {
    $this->actingAs(User::factory()->create(['system_role' => 'super_admin']));
});

test('detail attendance separates types and distinguishes missing records from nonparticipants across semesters', function () {
    $data = detailAttendanceContext();
    $present = detailAttendanceSession($data, '2026-08-07', 'regular', 'present');
    $missing = detailAttendanceSession($data, '2026-08-14', 'regular', null);
    $excluded = detailAttendanceSession($data, '2026-09-04', 'regular', null, false);
    detailAttendanceSession($data, '2026-09-05', 'camp', 'absent');
    detailAttendanceSession($data, '2027-01-08', 'regular', 'sick', true, true);
    Livewire::test(AttendanceDetail::class)->assertViewHas('sessionCount', 3)
        ->assertViewHas('rows', function ($rows) use ($present, $missing, $excluded) {
            $row = $rows->sole();

            return $row['cells'][$present->id] === 'H' && $row['cells'][$missing->id] === '?' && $row['cells'][$excluded->id] === '-'
                && $row['total']['participants'] === 2 && $row['total']['percentage'] === 50.0;
        })->set('period', 'year')->assertViewHas('sessionCount', 4)->assertViewHas('groups', fn ($groups) => $groups->count() === 2)
        ->set('activityType', 'special')->assertViewHas('sessionCount', 1)
        ->assertViewHas('rows', fn ($rows) => $rows->sole()['total']['absent'] === 1)
        ->set('activityType', 'competition')->assertViewHas('sessionCount', 0)
        ->set('activityType', 'regular')->set('period', 'range')->set('startDate', '2026-08-14')->set('endDate', '2026-08-14')
        ->assertViewHas('sessionCount', 1)->set('endDate', '2026-08-01')->assertViewHas('filterError', fn ($error) => $error !== null);
});

test('regular attendance score excludes special activity absence but activity factors remain explicit', function () {
    $data = detailAttendanceContext();
    detailAttendanceSession($data, '2026-08-07', 'regular', 'present');
    $camp = detailAttendanceSession($data, '2026-08-08', 'camp', 'absent');
    $config = AssessmentConfig::query()->create(['academic_year_id' => $data['year']->id, 'semester_id' => $data['first']->id, 'name' => 'Semester', 'is_active' => true]);
    expect(app(AssessmentService::class)->attendanceScore($config, $data['student']))->toBe(100.0);
    $factor = AssessmentFactor::factory()->create(['school_id' => $data['school']->id, 'source_type' => 'manual']);
    AssessmentConfigItem::query()->create(['assessment_config_id' => $config->id, 'assessment_factor_id' => $factor->id, 'weight' => 20, 'sort_order' => 1]);
    $assessment = ActivityAssessment::query()->create(['activity_id' => $camp->activity_id, 'assessment_factor_id' => $factor->id, 'title' => 'Nilai perkemahan', 'mode' => 'individual', 'status' => 'draft', 'is_special' => false]);
    expect(app(ActivityAssessmentService::class)->resolveAssessmentConfig($assessment)?->id)->toBe($config->id);
    $assessment->update(['is_special' => true]);
    expect(app(ActivityAssessmentService::class)->resolveAssessmentConfig($assessment))->toBeNull();
});

test('detail attendance protects tenant filters export and report permissions', function () {
    $data = detailAttendanceContext();
    detailAttendanceSession($data, '2026-08-07', 'regular', 'present');
    $this->withSession(['active_school_id' => $data['school']->id])->get(route('reports.attendance.detail'))->assertOk();
    $this->get(route('reports.attendance'))->assertOk()->assertSee('Rekap Detail per Pertemuan');
    Livewire::test(AttendanceDetail::class)->call('exportExcel')->assertFileDownloaded();
    $foreign = AcademicYear::factory()->create();
    Livewire::test(AttendanceDetail::class)->set('academicYearId', $foreign->id)->assertViewHas('sessionCount', 0)->assertViewHas('rows', fn ($rows) => $rows->isEmpty());
    $this->actingAs(User::factory()->create(['system_role' => 'student']));
    Livewire::test(AttendanceDetail::class)->assertForbidden();
});
