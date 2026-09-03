<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Attendance as AttendanceModel;
use App\Models\AttendanceSession;
use App\Models\AttendanceSessionParticipant;
use App\Models\Classroom;
use App\Models\SchoolDocumentSetting;
use App\Models\Semester;
use App\Models\Student;
use App\Services\GradeReportService;
use App\Services\ReportVerificationService;
use App\Support\SchoolContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ReportPdfController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | REKAP NILAI PDF
    |--------------------------------------------------------------------------
    */

    public function grades(
        Request $request,
        SchoolContext $schoolContext,
        GradeReportService $gradeReportService,
        ReportVerificationService $reportVerificationService
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

        /*
        |--------------------------------------------------------------------------
        | Validasi Query Parameter
        |--------------------------------------------------------------------------
        |
        | Controller mendukung dua bentuk nama parameter agar tetap kompatibel
        | dengan link laporan lama maupun link baru:
        |
        | academic_year_id / academic_year
        | semester_id      / semester
        | classroom_id     / classroom
        | closure_id       / closure
        |
        */

        $request->validate([
            'academic_year_id' => [
                'nullable',
                'integer',
            ],

            'academic_year' => [
                'nullable',
                'integer',
            ],

            'semester_id' => [
                'nullable',
                'integer',
            ],

            'semester' => [
                'nullable',
                'integer',
            ],

            'classroom_id' => [
                'nullable',
                'integer',
            ],

            'classroom' => [
                'nullable',
                'integer',
            ],

            'closure_id' => [
                'nullable',
                'integer',
            ],

            'closure' => [
                'nullable',
                'integer',
            ],
        ]);

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

        abort_unless(
            $semester,
            422,
            'Semester belum dipilih.'
        );

        $classroom =
            $this->resolveClassroom(
                $request
            );

        $closureId =
            $this->resolveNullableIntegerInput(
                $request,
                'closure_id',
                'closure'
            );

        /*
        |--------------------------------------------------------------------------
        | Sumber Data Rekap Nilai
        |--------------------------------------------------------------------------
        |
        | Semester terbuka:
        |   StudentScore + FinalGrade
        |
        | Semester terkunci / closure dipilih:
        |   SemesterGradeSnapshot
        |
        | Seluruh pemilihan sumber dilakukan oleh GradeReportService.
        |
        */

        $data =
            $gradeReportService
                ->getData(
                    academicYearId:
                        (int) $academicYear->id,

                    semesterId:
                        (int) $semester->id,

                    classroomId:
                        $classroom
                            ? (int) $classroom->id
                            : null,

                    search:
                        '',

                    closureId:
                        $closureId
                );

        /*
        |--------------------------------------------------------------------------
        | Proteksi Export
        |--------------------------------------------------------------------------
        |
        | Data live hanya dapat diekspor ketika sudah sinkron.
        | Snapshot resmi dapat diekspor tanpa pemeriksaan stale.
        |
        */

        $gradeReportService
            ->assertExportable(
                $data
            );

        /*
        |--------------------------------------------------------------------------
        | Dokumen Sekolah
        |--------------------------------------------------------------------------
        */

        $documentSetting =
            SchoolDocumentSetting::query()
                ->with(
                    'responsibleCoach'
                )
                ->first();

        /*
        |--------------------------------------------------------------------------
        | Metadata Laporan
        |--------------------------------------------------------------------------
        */

        $reportSource =
            $data[
                'reportSource'
            ]
            ?? 'live';

        $selectedClosure =
            $data[
                'selectedClosure'
            ]
            ?? null;

        /*
        |--------------------------------------------------------------------------
        | QR Verifikasi Dokumen
        |--------------------------------------------------------------------------
        |
        | Hanya snapshot resmi yang memperoleh kode verifikasi publik.
        | Setiap download PDF menghasilkan satu kode dokumen yang berbeda.
        |
        */

        $verification =
            null;

        $verificationUrl =
            null;

        $verificationQrDataUri =
            null;

        if (
            $reportSource
                === 'snapshot'
            &&
            $selectedClosure
        ) {
            $verification =
                $reportVerificationService
                    ->issue(
                        closure:
                            $selectedClosure,

                        documentType:
                            'grades',

                        issuedBy:
                            $request
                                ->user()
                                ?->id
                    );

            $verificationUrl =
                $reportVerificationService
                    ->publicUrl(
                        $verification
                    );

            $verificationQrDataUri =
                $reportVerificationService
                    ->qrDataUri(
                        $verification
                    );
        }

        $pdfData =
            array_merge(
                $data,
                [
                    'school' =>
                        $school,

                    'academicYear' =>
                        $academicYear,

                    'semester' =>
                        $semester,

                    'classroom' =>
                        $classroom,

                    'documentSetting' =>
                        $documentSetting,

                    'reportGeneratedAt' =>
                        $verification
                            ?->issued_at
                        ?? now(),

                    'reportSourceLabel' =>
                        $reportSource
                            === 'snapshot'
                                ? 'Snapshot Resmi'
                                : 'Data Berjalan',

                    'verification' =>
                        $verification,

                    'verificationUrl' =>
                        $verificationUrl,

                    'verificationQrDataUri' =>
                        $verificationQrDataUri,
                ]
            );

        /*
        |--------------------------------------------------------------------------
        | Generate PDF
        |--------------------------------------------------------------------------
        |
        | Tetap menggunakan regular controller response agar binary PDF tidak
        | dikembalikan melalui Livewire.
        |
        */

        $pdf =
            Pdf::loadView(
                'reports.pdf.grades',
                $pdfData
            )
                ->setPaper(
                    'a4',
                    'landscape'
                );

        /*
        |--------------------------------------------------------------------------
        | Nama File
        |--------------------------------------------------------------------------
        */

        $versionSuffix =
            $reportSource
                === 'snapshot'
            &&
            $selectedClosure
                ? '-v'
                    . $selectedClosure->version
                : '';

        $filenameTimestamp =
            $verification
                ?->issued_at
                ?->format(
                    'Ymd-His'
                )
            ?? now()->format(
                'Ymd-His'
            );

        $filename =
            'rekap-nilai-'
            . Str::slug(
                $school->name
            )
            . $versionSuffix
            . '-'
            . $filenameTimestamp
            . '.pdf';

        /*
        |--------------------------------------------------------------------------
        | Snapshot Resmi: Simpan Binary Pertama
        |--------------------------------------------------------------------------
        |
        | Binary yang dikirim pada download pertama adalah binary yang sama
        | dengan binary yang disimpan ke private storage. Download ulang tidak
        | menjalankan DomPDF lagi.
        |--------------------------------------------------------------------------
        */

        if ($verification) {
            try {
                $binary =
                    $pdf->output();

                $reportVerificationService
                    ->archivePdf(
                        verification:
                            $verification,

                        binary:
                            $binary,

                        filename:
                            $filename
                    );
            } catch (
                Throwable $exception
            ) {
                $reportVerificationService
                    ->discardFailedIssue(
                        $verification
                    );

                throw $exception;
            }

            return response(
                $binary,
                200,
                [
                    'Content-Type' =>
                        'application/pdf',

                    'Content-Disposition' =>
                        'attachment; filename="'
                        . addslashes(
                            $filename
                        )
                        . '"',

                    'Content-Length' =>
                        (string) strlen(
                            $binary
                        ),

                    'X-Content-Type-Options' =>
                        'nosniff',

                    'Cache-Control' =>
                        'private, no-store, max-age=0',
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Data Berjalan
        |--------------------------------------------------------------------------
        */

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
        $academicYearId =
            $this->resolveNullableIntegerInput(
                $request,
                'academic_year_id',
                'academic_year'
            );

        if ($academicYearId) {
            return AcademicYear::query()
                ->findOrFail(
                    $academicYearId
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
        $semesterId =
            $this->resolveNullableIntegerInput(
                $request,
                'semester_id',
                'semester'
            );

        if ($semesterId) {
            return Semester::query()
                ->where(
                    'academic_year_id',
                    $academicYear->id
                )
                ->findOrFail(
                    $semesterId
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
        $classroomId =
            $this->resolveNullableIntegerInput(
                $request,
                'classroom_id',
                'classroom'
            );

        if (! $classroomId) {
            return null;
        }

        return Classroom::query()
            ->findOrFail(
                $classroomId
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Resolve Integer dari Dua Nama Parameter
    |--------------------------------------------------------------------------
    |
    | Digunakan untuk menjaga kompatibilitas parameter query lama dan baru.
    |
    */

    protected function resolveNullableIntegerInput(
        Request $request,
        string $primaryKey,
        string $fallbackKey
    ): ?int {
        if (
            $request->filled(
                $primaryKey
            )
        ) {
            return (int) $request->input(
                $primaryKey
            );
        }

        if (
            $request->filled(
                $fallbackKey
            )
        ) {
            return (int) $request->input(
                $fallbackKey
            );
        }

        return null;
    }
}
