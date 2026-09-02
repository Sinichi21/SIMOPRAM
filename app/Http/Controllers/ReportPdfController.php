<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\AssessmentConfig;
use App\Models\Attendance as AttendanceModel;
use App\Models\AttendanceSession;
use App\Models\AttendanceSessionParticipant;
use App\Models\Classroom;
use App\Models\FinalGrade;
use App\Models\SchoolDocumentSetting;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentScore;
use App\Support\SchoolContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use App\Services\AssessmentService;

class ReportPdfController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | REKAP NILAI PDF
    |--------------------------------------------------------------------------
    */

    public function grades(
        Request $request,
        SchoolContext $schoolContext
    ): Response {
        abort_unless(
            $request->user()?->can('reports.export'),
            403
        );

        abort_unless(
            $schoolContext->hasSchool(),
            409,
            'Pilih sekolah aktif terlebih dahulu.'
        );

        $school =
            $schoolContext->school();

        /*
        |--------------------------------------------------------------------------
        | Filter
        |--------------------------------------------------------------------------
        */

        $academicYear =
            $this->resolveAcademicYear(
                $request
            );

        abort_unless(
            $academicYear,
            422,
            'Tahun ajaran belum dipilih.'
        );

        $semester =
            $this->resolveSemester(
                $request,
                $academicYear
            );

        $classroom =
            $this->resolveClassroom(
                $request
            );

        /*
        |--------------------------------------------------------------------------
        | Konfigurasi Penilaian
        |--------------------------------------------------------------------------
        */

        $selectedConfig =
            AssessmentConfig::query()
                ->with([
                    'items' => function ($query): void {
                        $query->orderBy(
                            'sort_order'
                        );
                    },

                    'items.factor',
                ])
                ->where(
                    'academic_year_id',
                    $academicYear->id
                )
                ->when(
                    $semester,
                    fn ($query) => $query->where(
                        'semester_id',
                        $semester->id
                    ),
                    fn ($query) => $query->whereNull(
                        'semester_id'
                    )
                )
                ->where(
                    'is_active',
                    true
                )
                ->first();

        abort_unless(
            $selectedConfig,
            422,
            'Belum ada konfigurasi penilaian aktif untuk periode yang dipilih.'
        );

        $assessmentService =
            app(
                AssessmentService::class
            );


        $attendanceSync =
            $assessmentService
                ->attendanceSyncStatus(
                    $selectedConfig
                );


        $finalSync =
            $assessmentService
                ->finalGradeSyncStatus(
                    $selectedConfig
                );


        abort_if(
            $attendanceSync['is_stale']
            ||
            $finalSync['is_stale'],
            409,
            'Nilai belum sinkron. Hitung ulang nilai sebelum mencetak laporan.'
        );

        /*
        |--------------------------------------------------------------------------
        | Siswa
        |--------------------------------------------------------------------------
        */

        $students =
            Student::query()
                ->where(
                    'status',
                    'active'
                )
                ->whereHas(
                    'enrollments',
                    function ($query) use (
                        $academicYear,
                        $classroom
                    ): void {
                        $query->where(
                            'academic_year_id',
                            $academicYear->id
                        );

                        if ($classroom) {
                            $query->where(
                                'classroom_id',
                                $classroom->id
                            );
                        }
                    }
                )
                ->with([
                    'enrollments' => function ($query) use (
                        $academicYear
                    ): void {
                        $query
                            ->where(
                                'academic_year_id',
                                $academicYear->id
                            )
                            ->with(
                                'classroom'
                            );
                    },
                ])
                ->orderBy(
                    'name'
                )
                ->get();

        $studentIds =
            $students->pluck(
                'id'
            );

        /*
        |--------------------------------------------------------------------------
        | Nilai Faktor
        |--------------------------------------------------------------------------
        */

        $scores =
            StudentScore::query()
                ->where(
                    'assessment_config_id',
                    $selectedConfig->id
                )
                ->whereIn(
                    'student_id',
                    $studentIds
                )
                ->get()
                ->groupBy(
                    'student_id'
                );

        /*
        |--------------------------------------------------------------------------
        | Nilai Akhir
        |--------------------------------------------------------------------------
        */

        $finalGrades =
            FinalGrade::query()
                ->where(
                    'assessment_config_id',
                    $selectedConfig->id
                )
                ->whereIn(
                    'student_id',
                    $studentIds
                )
                ->get()
                ->keyBy(
                    'student_id'
                );

        /*
        |--------------------------------------------------------------------------
        | Generate PDF
        |--------------------------------------------------------------------------
        */

        $documentSetting =
            SchoolDocumentSetting::query()
                ->with(
                    'responsibleCoach'
                )
                ->first();

        $pdf =
            Pdf::loadView(
                'reports.pdf.grades',
                [
                    'school' => $school,

                    'academicYear' => $academicYear,

                    'semester' => $semester,

                    'classroom' => $classroom,

                    'selectedConfig' => $selectedConfig,

                    'students' => $students,

                    'scores' => $scores,

                    'finalGrades' => $finalGrades,

                    'documentSetting' => $documentSetting,
                ]
            )
                ->setPaper(
                    'a4',
                    'landscape'
                );

        $filename =
            'rekap-nilai-'
            .Str::slug(
                $school->name
            )
            .'-'
            .now()->format(
                'Ymd-His'
            )
            .'.pdf';

        return $pdf->download(
            $filename
        );
    }

    /*
    |--------------------------------------------------------------------------
    | REKAP ABSENSI PDF
    |--------------------------------------------------------------------------
    */

    public function attendance(
        Request $request,
        SchoolContext $schoolContext
    ): Response {
        abort_unless(
            $request->user()?->can(
                'reports.export'
            ),
            403
        );

        abort_unless(
            $schoolContext->hasSchool(),
            409,
            'Pilih sekolah aktif terlebih dahulu.'
        );

        $school =
            $schoolContext->school();

        /*
        |--------------------------------------------------------------------------
        | Filter
        |--------------------------------------------------------------------------
        */

        $academicYear =
            $this->resolveAcademicYear(
                $request
            );

        abort_unless(
            $academicYear,
            422,
            'Tahun ajaran belum dipilih.'
        );

        $semester =
            $this->resolveSemester(
                $request,
                $academicYear
            );

        $classroom =
            $this->resolveClassroom(
                $request
            );

        /*
        |--------------------------------------------------------------------------
        | Session Absensi
        |--------------------------------------------------------------------------
        */

        $sessionIds =
            AttendanceSession::query()
                ->whereHas(
                    'activity',
                    function ($query) use (
                        $academicYear,
                        $semester
                    ): void {
                        $query->where(
                            'academic_year_id',
                            $academicYear->id
                        );

                        if ($semester) {
                            $query->where(
                                'semester_id',
                                $semester->id
                            );
                        }
                    }
                )
                ->pluck(
                    'id'
                );

        /*
        |--------------------------------------------------------------------------
        | Siswa
        |--------------------------------------------------------------------------
        */

        $students =
            Student::query()
                ->where(
                    'status',
                    'active'
                )
                ->whereHas(
                    'enrollments',
                    function ($query) use (
                        $academicYear,
                        $classroom
                    ): void {
                        $query->where(
                            'academic_year_id',
                            $academicYear->id
                        );

                        if ($classroom) {
                            $query->where(
                                'classroom_id',
                                $classroom->id
                            );
                        }
                    }
                )
                ->with([
                    'enrollments' => function ($query) use (
                        $academicYear
                    ): void {
                        $query
                            ->where(
                                'academic_year_id',
                                $academicYear->id
                            )
                            ->with(
                                'classroom'
                            );
                    },
                ])
                ->orderBy(
                    'name'
                )
                ->get();

        $studentIds =
            $students->pluck(
                'id'
            );

        /*
        |--------------------------------------------------------------------------
        | Jumlah sesi yang diikuti masing-masing siswa
        |--------------------------------------------------------------------------
        */

        $participantCounts =
            AttendanceSessionParticipant::query()
                ->whereIn(
                    'attendance_session_id',
                    $sessionIds
                )
                ->whereIn(
                    'student_id',
                    $studentIds
                )
                ->selectRaw(
                    'student_id, COUNT(*) as total'
                )
                ->groupBy(
                    'student_id'
                )
                ->pluck(
                    'total',
                    'student_id'
                );

        /*
        |--------------------------------------------------------------------------
        | Status Absensi
        |--------------------------------------------------------------------------
        */

        $attendanceCounts =
            AttendanceModel::query()
                ->whereIn(
                    'attendance_session_id',
                    $sessionIds
                )
                ->whereIn(
                    'student_id',
                    $studentIds
                )
                ->selectRaw(
                    'student_id, status, COUNT(*) as total'
                )
                ->groupBy(
                    'student_id',
                    'status'
                )
                ->get()
                ->groupBy(
                    'student_id'
                );

        /*
        |--------------------------------------------------------------------------
        | Susun Row Laporan
        |--------------------------------------------------------------------------
        */

        $rows =
            $students
                ->map(
                    function (
                        Student $student
                    ) use (
                        $participantCounts,
                        $attendanceCounts
                    ): array {

                        $participants =
                            (int) (
                                $participantCounts[
                                    $student->id
                                ] ?? 0
                            );

                        $counts =
                            $attendanceCounts
                                ->get(
                                    $student->id,
                                    collect()
                                )
                                ->pluck(
                                    'total',
                                    'status'
                                );

                        $present =
                            (int) (
                                $counts[
                                    'present'
                                ] ?? 0
                            );

                        $late =
                            (int) (
                                $counts[
                                    'late'
                                ] ?? 0
                            );

                        $sick =
                            (int) (
                                $counts[
                                    'sick'
                                ] ?? 0
                            );

                        $excused =
                            (int) (
                                $counts[
                                    'excused'
                                ] ?? 0
                            );

                        $absent =
                            (int) (
                                $counts[
                                    'absent'
                                ] ?? 0
                            );

                        $recorded =
                            $present
                            + $late
                            + $sick
                            + $excused
                            + $absent;

                        $unrecorded =
                            max(
                                0,
                                $participants
                                - $recorded
                            );

                        $presencePercentage =
                            $participants > 0
                                ? round(
                                    (
                                        (
                                            $present
                                            + $late
                                        )
                                        /
                                        $participants
                                    )
                                    * 100,
                                    2
                                )
                                : 0;

                        return [
                            'student' => $student,

                            'participants' => $participants,

                            'present' => $present,

                            'late' => $late,

                            'sick' => $sick,

                            'excused' => $excused,

                            'absent' => $absent,

                            'unrecorded' => $unrecorded,

                            'presence_percentage' => $presencePercentage,
                        ];
                    }
                );

        /*
        |--------------------------------------------------------------------------
        | Generate PDF
        |--------------------------------------------------------------------------
        */

        $documentSetting =
            SchoolDocumentSetting::query()
                ->with(
                    'responsibleCoach'
                )
                ->first();

        $pdf =
            Pdf::loadView(
                'reports.pdf.attendance',
                [
                    'school' => $school,

                    'academicYear' => $academicYear,

                    'semester' => $semester,

                    'classroom' => $classroom,

                    'rows' => $rows,

                    'sessionCount' => $sessionIds->count(),

                    'documentSetting' => $documentSetting,
                ]
            )
                ->setPaper(
                    'a4',
                    'landscape'
                );

        $filename =
            'rekap-absensi-'
            .Str::slug(
                $school->name
            )
            .'-'
            .now()->format(
                'Ymd-His'
            )
            .'.pdf';

        return $pdf->download(
            $filename
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Resolve Tahun Ajaran
    |--------------------------------------------------------------------------
    */

    protected function resolveAcademicYear(
        Request $request
    ): ?AcademicYear {
        if (
            $request->filled(
                'academic_year_id'
            )
        ) {
            return AcademicYear::query()
                ->findOrFail(
                    (int) $request->input(
                        'academic_year_id'
                    )
                );
        }

        return AcademicYear::query()
            ->where(
                'is_active',
                true
            )
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Resolve Semester
    |--------------------------------------------------------------------------
    */

    protected function resolveSemester(
        Request $request,
        AcademicYear $academicYear
    ): ?Semester {
        if (
            $request->filled(
                'semester_id'
            )
        ) {
            return Semester::query()
                ->where(
                    'academic_year_id',
                    $academicYear->id
                )
                ->findOrFail(
                    (int) $request->input(
                        'semester_id'
                    )
                );
        }

        return Semester::query()
            ->where(
                'academic_year_id',
                $academicYear->id
            )
            ->where(
                'is_active',
                true
            )
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Resolve Kelas
    |--------------------------------------------------------------------------
    */

    protected function resolveClassroom(
        Request $request
    ): ?Classroom {
        if (
            ! $request->filled(
                'classroom_id'
            )
        ) {
            return null;
        }

        return Classroom::query()
            ->findOrFail(
                (int) $request->input(
                    'classroom_id'
                )
            );
    }
}
