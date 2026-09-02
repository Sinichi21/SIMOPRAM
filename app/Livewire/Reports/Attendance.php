<?php

namespace App\Livewire\Reports;

use App\Models\AcademicYear;
use App\Models\Attendance as AttendanceModel;
use App\Models\AttendanceSession;
use App\Models\AttendanceSessionParticipant;
use App\Models\Classroom;
use App\Models\Semester;
use App\Models\Student;
use Livewire\Component;

class Attendance extends Component
{
    public ?int $academicYearId = null;

    public ?int $semesterId = null;

    public ?int $classroomId = null;

    public string $search = '';

    public function mount(): void
    {
        $year =
            AcademicYear::query()
                ->where(
                    'is_active',
                    true
                )
                ->first();

        $this->academicYearId =
            $year?->id;

        if ($this->academicYearId) {
            $semester =
                Semester::query()
                    ->where(
                        'academic_year_id',
                        $this->academicYearId
                    )
                    ->where(
                        'is_active',
                        true
                    )
                    ->first();

            $this->semesterId =
                $semester?->id;
        }
    }

    public function updatedAcademicYearId(): void
    {
        $this->semesterId = null;
        $this->classroomId = null;

        if (! $this->academicYearId) {
            return;
        }

        $semester =
            Semester::query()
                ->where(
                    'academic_year_id',
                    $this->academicYearId
                )
                ->where(
                    'is_active',
                    true
                )
                ->first();

        $this->semesterId =
            $semester?->id;
    }

    protected function getReportData(): array
    {
        if (! $this->academicYearId) {
            return [
                'students' => collect(),
                'rows' => collect(),
                'sessionCount' => 0,
            ];
        }

        $sessionIds =
            AttendanceSession::query()
                ->whereHas(
                    'activity',
                    function ($query): void {
                        $query->where(
                            'academic_year_id',
                            $this->academicYearId
                        );

                        if ($this->semesterId) {
                            $query->where(
                                'semester_id',
                                $this->semesterId
                            );
                        }
                    }
                )
                ->pluck('id');

        $students =
            Student::query()
                ->with([
                    'enrollments' => function ($query): void {
                        $query
                            ->where(
                                'academic_year_id',
                                $this->academicYearId
                            )
                            ->with(
                                'classroom'
                            );
                    },
                ])
                ->where(
                    'status',
                    'active'
                )
                ->whereHas(
                    'enrollments',
                    function ($query): void {
                        $query->where(
                            'academic_year_id',
                            $this->academicYearId
                        );

                        if ($this->classroomId) {
                            $query->where(
                                'classroom_id',
                                $this->classroomId
                            );
                        }
                    }
                )
                ->when(
                    trim($this->search) !== '',
                    function ($query): void {
                        $search =
                            '%'.
                            trim($this->search).
                            '%';

                        $query->where(
                            function ($query) use (
                                $search
                            ): void {
                                $query
                                    ->where(
                                        'name',
                                        'like',
                                        $search
                                    )
                                    ->orWhere(
                                        'nis',
                                        'like',
                                        $search
                                    );
                            }
                        );
                    }
                )
                ->orderBy('name')
                ->get();

        $studentIds =
            $students->pluck('id');

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

        $rows =
            $students->map(
                function ($student) use (
                    $participantCounts,
                    $attendanceCounts
                ): array {

                    $statusRows =
                        $attendanceCounts
                            ->get(
                                $student->id,
                                collect()
                            );

                    $counts = [
                        'present' => 0,
                        'late' => 0,
                        'sick' => 0,
                        'excused' => 0,
                        'absent' => 0,
                    ];

                    foreach (
                        $statusRows as $statusRow
                    ) {
                        if (
                            array_key_exists(
                                $statusRow->status,
                                $counts
                            )
                        ) {
                            $counts[
                                $statusRow->status
                            ] =
                                (int)
                                $statusRow->total;
                        }
                    }

                    $participants =
                        (int) (
                            $participantCounts[
                                $student->id
                            ] ?? 0
                        );

                    $recorded =
                        array_sum(
                            $counts
                        );

                    $unrecorded =
                        max(
                            0,
                            $participants
                            - $recorded
                        );

                    $physicalPresence =
                        $counts['present']
                        +
                        $counts['late'];

                    $presencePercentage =
                        $participants > 0
                            ? round(
                                (
                                    $physicalPresence
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

                        'present' => $counts['present'],

                        'late' => $counts['late'],

                        'sick' => $counts['sick'],

                        'excused' => $counts['excused'],

                        'absent' => $counts['absent'],

                        'unrecorded' => $unrecorded,

                        'presence_percentage' => $presencePercentage,
                    ];
                }
            );

        return [
            'students' => $students,

            'rows' => $rows,

            'sessionCount' => $sessionIds->count(),
        ];
    }

    public function exportCsv()
    {
        abort_unless(
            auth()->user()->can(
                'reports.export'
            ),
            403
        );

        $data =
            $this->getReportData();

        $filename =
            'rekap-absensi-'.
            now()->format(
                'Y-m-d-His'
            ).
            '.csv';

        return response()->streamDownload(
            function () use ($data): void {
                $handle =
                    fopen(
                        'php://output',
                        'w'
                    );

                fwrite(
                    $handle,
                    "\xEF\xBB\xBF"
                );

                fputcsv(
                    $handle,
                    [
                        'NIS',
                        'Nama Siswa',
                        'Kelas',
                        'Pertemuan',
                        'Hadir',
                        'Terlambat',
                        'Sakit',
                        'Izin',
                        'Alpa',
                        'Belum Dicatat',
                        'Persentase Hadir',
                    ],
                    ';'
                );

                foreach (
                    $data['rows'] as $row
                ) {
                    $student =
                        $row['student'];

                    $enrollment =
                        $student
                            ->enrollments
                            ->first();

                    fputcsv(
                        $handle,
                        [
                            $student->nis,

                            $student->name,

                            $enrollment
                                ?->classroom
                                ?->name ?? '-',

                            $row[
                                'participants'
                            ],

                            $row[
                                'present'
                            ],

                            $row[
                                'late'
                            ],

                            $row[
                                'sick'
                            ],

                            $row[
                                'excused'
                            ],

                            $row[
                                'absent'
                            ],

                            $row[
                                'unrecorded'
                            ],

                            number_format(
                                $row[
                                    'presence_percentage'
                                ],
                                2,
                                '.',
                                ''
                            ).'%',
                        ],
                        ';'
                    );
                }

                fclose($handle);
            },
            $filename,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]
        );
    }

    public function render()
    {
        $academicYears =
            AcademicYear::query()
                ->orderByDesc(
                    'start_date'
                )
                ->get();

        $semesters =
            Semester::query()
                ->when(
                    $this->academicYearId,
                    fn ($query) => $query->where(
                        'academic_year_id',
                        $this->academicYearId
                    )
                )
                ->orderBy(
                    'semester_number'
                )
                ->get();

        $classrooms =
            Classroom::query()
                ->orderBy('name')
                ->get();

        $data =
            $this->getReportData();

        return view(
            'livewire.reports.attendance',
            array_merge(
                compact(
                    'academicYears',
                    'semesters',
                    'classrooms'
                ),
                $data
            )
        );
    }
}
