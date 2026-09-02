<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Activity;
use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\AttendanceSessionParticipant;
use App\Models\Coach;
use App\Models\FinalGrade;
use App\Models\Journal;
use App\Models\ScoutUnit;
use App\Models\Semester;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class GudepDashboardService
{
    public function data(): array
    {
        $academicYear =
            AcademicYear::query()
                ->where('is_active', true)
                ->first();

        $semester =
            $academicYear
                ? Semester::query()
                    ->where(
                        'academic_year_id',
                        $academicYear->id
                    )
                    ->where(
                        'is_active',
                        true
                    )
                    ->first()
                : null;

        $activityQuery =
            Activity::query();

        if ($academicYear) {
            $activityQuery->where(
                'academic_year_id',
                $academicYear->id
            );
        }

        if ($semester) {
            $activityQuery->where(
                'semester_id',
                $semester->id
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Statistik utama
        |--------------------------------------------------------------------------
        */

        $studentCount =
            Student::query()
                ->where(
                    'status',
                    'active'
                )
                ->count();

        $coachCount =
            Coach::query()
                ->where(
                    'is_active',
                    true
                )
                ->count();

        $scoutUnitCount =
            ScoutUnit::query()
                ->when(
                    $academicYear,
                    fn ($query) => $query->where(
                        'academic_year_id',
                        $academicYear->id
                    )
                )
                ->count();

        $activityCount =
            (clone $activityQuery)
                ->count();

        /*
        |--------------------------------------------------------------------------
        | Attendance
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
                        if ($academicYear) {
                            $query->where(
                                'academic_year_id',
                                $academicYear->id
                            );
                        }

                        if ($semester) {
                            $query->where(
                                'semester_id',
                                $semester->id
                            );
                        }
                    }
                )
                ->pluck('id');

        $participantSlots =
            AttendanceSessionParticipant::query()
                ->whereIn(
                    'attendance_session_id',
                    $sessionIds
                )
                ->count();

        $attendanceCounts =
            Attendance::query()
                ->whereIn(
                    'attendance_session_id',
                    $sessionIds
                )
                ->selectRaw(
                    'status, COUNT(*) as total'
                )
                ->groupBy('status')
                ->pluck(
                    'total',
                    'status'
                );

        $present =
            (int) (
                $attendanceCounts[
                    'present'
                ] ?? 0
            );

        $late =
            (int) (
                $attendanceCounts[
                    'late'
                ] ?? 0
            );

        $sick =
            (int) (
                $attendanceCounts[
                    'sick'
                ] ?? 0
            );

        $excused =
            (int) (
                $attendanceCounts[
                    'excused'
                ] ?? 0
            );

        $absent =
            (int) (
                $attendanceCounts[
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
                $participantSlots
                - $recorded
            );

        $presencePercentage =
            $participantSlots > 0
                ? round(
                    (
                        ($present + $late)
                        /
                        $participantSlots
                    )
                    * 100,
                    2
                )
                : 0;

        /*
        |--------------------------------------------------------------------------
        | Jurnal
        |--------------------------------------------------------------------------
        */

        $journalQuery =
            Journal::query()
                ->whereHas(
                    'activity',
                    function ($query) use (
                        $academicYear,
                        $semester
                    ): void {
                        if ($academicYear) {
                            $query->where(
                                'academic_year_id',
                                $academicYear->id
                            );
                        }

                        if ($semester) {
                            $query->where(
                                'semester_id',
                                $semester->id
                            );
                        }
                    }
                );

        $journalCount =
            (clone $journalQuery)
                ->count();

        $publishedJournalCount =
            (clone $journalQuery)
                ->where(
                    'status',
                    'published'
                )
                ->count();

        /*
        |--------------------------------------------------------------------------
        | Nilai
        |--------------------------------------------------------------------------
        */

        $finalGradeQuery =
            FinalGrade::query();

        if ($academicYear) {
            $finalGradeQuery
                ->whereHas(
                    'config',
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
                );
        }

        $gradedStudentCount =
            (clone $finalGradeQuery)
                ->count();

        $averageFinalGrade =
            (float) (
                (clone $finalGradeQuery)
                    ->avg('final_score')
                ?? 0
            );

        /*
        |--------------------------------------------------------------------------
        | Agenda mendatang
        |--------------------------------------------------------------------------
        */

        $upcomingActivities =
            Activity::query()
                ->with('coaches')
                ->where(
                    'start_at',
                    '>=',
                    now()
                )
                ->where(
                    'status',
                    '!=',
                    'cancelled'
                )
                ->orderBy('start_at')
                ->limit(5)
                ->get();

        /*
        |--------------------------------------------------------------------------
        | Pengumuman terbaru
        |--------------------------------------------------------------------------
        */

        $latestAnnouncements =
            Announcement::query()
                ->where(
                    'status',
                    'published'
                )
                ->whereNotNull(
                    'published_at'
                )
                ->where(
                    'published_at',
                    '<=',
                    now()
                )
                ->where(
                    function ($query): void {
                        $query
                            ->whereNull(
                                'expires_at'
                            )
                            ->orWhere(
                                'expires_at',
                                '>',
                                now()
                            );
                    }
                )
                ->when(
                    ! auth()->user()->can(
                        'announcements.view'
                    ),
                    function ($query): void {
                        $query->whereHas(
                            'notificationLogs',
                            fn ($logQuery) => $logQuery
                                ->where(
                                    'user_id',
                                    auth()->id()
                                )
                                ->where(
                                    'channel',
                                    'web'
                                )
                                ->where(
                                    'status',
                                    'sent'
                                )
                        );
                    }
                )
                ->latest(
                    'published_at'
                )
                ->limit(5)
                ->get();

        /*
        |--------------------------------------------------------------------------
        | Aktivitas 6 bulan
        |--------------------------------------------------------------------------
        */

        $activityByMonth =
            $this->activityByMonth();

        return [
            'academicYear' => $academicYear,

            'semester' => $semester,

            'studentCount' => $studentCount,

            'coachCount' => $coachCount,

            'scoutUnitCount' => $scoutUnitCount,

            'activityCount' => $activityCount,

            'participantSlots' => $participantSlots,

            'present' => $present,

            'late' => $late,

            'sick' => $sick,

            'excused' => $excused,

            'absent' => $absent,

            'unrecorded' => $unrecorded,

            'presencePercentage' => $presencePercentage,

            'journalCount' => $journalCount,

            'publishedJournalCount' => $publishedJournalCount,

            'gradedStudentCount' => $gradedStudentCount,

            'averageFinalGrade' => round(
                $averageFinalGrade,
                2
            ),

            'upcomingActivities' => $upcomingActivities,

            'latestAnnouncements' => $latestAnnouncements,

            'activityByMonth' => $activityByMonth,
        ];
    }

    protected function activityByMonth(): Collection
    {
        $start =
            now()
                ->startOfMonth()
                ->subMonths(5);

        $activities =
            Activity::query()
                ->where(
                    'start_at',
                    '>=',
                    $start
                )
                ->get([
                    'id',
                    'start_at',
                ]);

        return collect(
            range(5, 0)
        )
            ->map(
                function (int $monthsAgo) use (
                    $activities
                ): array {
                    $month =
                        now()
                            ->startOfMonth()
                            ->subMonths(
                                $monthsAgo
                            );

                    $count =
                        $activities
                            ->filter(
                                fn ($activity) => $activity
                                    ->start_at
                                    ->format('Y-m')
                                    ===
                                    $month
                                        ->format('Y-m')
                            )
                            ->count();

                    return [
                        'key' => $month
                            ->format(
                                'Y-m'
                            ),

                        'label' => $month
                            ->translatedFormat(
                                'M Y'
                            ),

                        'total' => $count,
                    ];
                }
            )
            ->push(
                $this->currentMonthActivity(
                    $activities
                )
            )
            ->values();
    }

    protected function currentMonthActivity(
        Collection $activities
    ): array {
        $month =
            Carbon::now()
                ->startOfMonth();

        return [
            'key' => $month->format(
                'Y-m'
            ),

            'label' => $month
                ->translatedFormat(
                    'M Y'
                ),

            'total' => $activities
                ->filter(
                    fn ($activity) => $activity
                        ->start_at
                        ->format('Y-m')
                        ===
                        $month
                            ->format('Y-m')
                )
                ->count(),
        ];
    }
}
