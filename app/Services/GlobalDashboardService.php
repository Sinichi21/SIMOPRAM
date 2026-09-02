<?php

namespace App\Services;

use App\Models\School;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GlobalDashboardService
{
    public function data(): array
    {
        /*
        |--------------------------------------------------------------------------
        | SEKOLAH
        |--------------------------------------------------------------------------
        */

        $totalSchools =
            DB::table('schools')
                ->whereNull(
                    'deleted_at'
                )
                ->count();

        $activeSchools =
            DB::table('schools')
                ->whereNull(
                    'deleted_at'
                )
                ->where(
                    'is_active',
                    true
                )
                ->count();

        /*
        |--------------------------------------------------------------------------
        | USER
        |--------------------------------------------------------------------------
        */

        $totalUsers =
            DB::table('users')
                ->where(
                    'is_active',
                    true
                )
                ->count();

        /*
        |--------------------------------------------------------------------------
        | SISWA
        |--------------------------------------------------------------------------
        */

        $totalStudents =
            DB::table('students')
                ->whereNull(
                    'deleted_at'
                )
                ->where(
                    'status',
                    'active'
                )
                ->count();

        /*
        |--------------------------------------------------------------------------
        | PEMBINA
        |--------------------------------------------------------------------------
        */

        $totalCoaches =
            DB::table('coaches')
                ->whereNull(
                    'deleted_at'
                )
                ->where(
                    'is_active',
                    true
                )
                ->count();

        /*
        |--------------------------------------------------------------------------
        | REGU / BARUNG
        |--------------------------------------------------------------------------
        */

        $totalScoutUnits =
            DB::table(
                'scout_units'
            )->count();

        /*
        |--------------------------------------------------------------------------
        | KEGIATAN
        |--------------------------------------------------------------------------
        */

        $totalActivities =
            DB::table('activities')
                ->whereNull(
                    'deleted_at'
                )
                ->count();

        $activitiesThisMonth =
            DB::table('activities')
                ->whereNull(
                    'deleted_at'
                )
                ->whereBetween(
                    'start_at',
                    [
                        now()
                            ->startOfMonth(),

                        now()
                            ->endOfMonth(),
                    ]
                )
                ->count();

        /*
        |--------------------------------------------------------------------------
        | ABSENSI
        |--------------------------------------------------------------------------
        */

        $participantSlots =
            DB::table(
                'attendance_session_participants'
            )->count();

        $physicalPresence =
            DB::table(
                'attendances'
            )
                ->whereIn(
                    'status',
                    [
                        'present',
                        'late',
                    ]
                )
                ->count();

        $globalPresencePercentage =
            $participantSlots > 0
                ? round(
                    (
                        $physicalPresence
                        /
                        $participantSlots
                    )
                    * 100,
                    2
                )
                : 0;

        /*
        |--------------------------------------------------------------------------
        | JURNAL
        |--------------------------------------------------------------------------
        */

        $totalJournals =
            DB::table('journals')
                ->whereNull(
                    'deleted_at'
                )
                ->count();

        $publishedJournals =
            DB::table('journals')
                ->whereNull(
                    'deleted_at'
                )
                ->where(
                    'status',
                    'published'
                )
                ->count();

        /*
        |--------------------------------------------------------------------------
        | PENILAIAN
        |--------------------------------------------------------------------------
        */

        $gradedStudents =
            DB::table(
                'final_grades'
            )
                ->distinct()
                ->count(
                    'student_id'
                );

        $averageFinalGrade =
            (float) (
                DB::table(
                    'final_grades'
                )
                    ->avg(
                        'final_score'
                    )
                ?? 0
            );

        /*
        |--------------------------------------------------------------------------
        | PENGUMUMAN
        |--------------------------------------------------------------------------
        */

        $publishedAnnouncements =
            DB::table(
                'announcements'
            )
                ->whereNull(
                    'deleted_at'
                )
                ->where(
                    'status',
                    'published'
                )
                ->count();

        /*
        |--------------------------------------------------------------------------
        | TELEGRAM
        |--------------------------------------------------------------------------
        */

        $telegramLinked =
            DB::table(
                'user_notification_channels'
            )
                ->where(
                    'channel',
                    'telegram'
                )
                ->where(
                    'is_verified',
                    true
                )
                ->where(
                    'is_active',
                    true
                )
                ->count();

        $telegramSent =
            DB::table(
                'notification_logs'
            )
                ->where(
                    'channel',
                    'telegram'
                )
                ->where(
                    'status',
                    'sent'
                )
                ->count();

        $telegramFailed =
            DB::table(
                'notification_logs'
            )
                ->where(
                    'channel',
                    'telegram'
                )
                ->where(
                    'status',
                    'failed'
                )
                ->count();

        /*
        |--------------------------------------------------------------------------
        | MONITORING PER SEKOLAH
        |--------------------------------------------------------------------------
        */

        $schoolMonitoring =
            $this->schoolMonitoring();

        /*
        |--------------------------------------------------------------------------
        | AKTIVITAS 6 BULAN
        |--------------------------------------------------------------------------
        */

        $activityByMonth =
            $this->activityByMonth();

        /*
        |--------------------------------------------------------------------------
        | AGENDA MENDATANG
        |--------------------------------------------------------------------------
        */

        $upcomingActivities =
            DB::table(
                'activities'
            )
                ->join(
                    'schools',
                    'schools.id',
                    '=',
                    'activities.school_id'
                )
                ->whereNull(
                    'activities.deleted_at'
                )
                ->whereNull(
                    'schools.deleted_at'
                )
                ->where(
                    'schools.is_active',
                    true
                )
                ->where(
                    'activities.start_at',
                    '>=',
                    now()
                )
                ->where(
                    'activities.status',
                    '!=',
                    'cancelled'
                )
                ->orderBy(
                    'activities.start_at'
                )
                ->limit(8)
                ->get([
                    'activities.id',
                    'activities.title',
                    'activities.start_at',
                    'activities.location',
                    'schools.name as school_name',
                ]);

        /*
        |--------------------------------------------------------------------------
        | PENGUMUMAN TERBARU
        |--------------------------------------------------------------------------
        */

        $latestAnnouncements =
            DB::table(
                'announcements'
            )
                ->join(
                    'schools',
                    'schools.id',
                    '=',
                    'announcements.school_id'
                )
                ->whereNull(
                    'announcements.deleted_at'
                )
                ->whereNull(
                    'schools.deleted_at'
                )
                ->where(
                    'schools.is_active',
                    true
                )
                ->where(
                    'announcements.status',
                    'published'
                )
                ->whereNotNull(
                    'announcements.published_at'
                )
                ->where(
                    'announcements.published_at',
                    '<=',
                    now()
                )
                ->where(
                    function ($query): void {
                        $query
                            ->whereNull(
                                'announcements.expires_at'
                            )
                            ->orWhere(
                                'announcements.expires_at',
                                '>',
                                now()
                            );
                    }
                )
                ->orderByDesc(
                    'announcements.published_at'
                )
                ->limit(8)
                ->get([
                    'announcements.id',
                    'announcements.title',
                    'announcements.body',
                    'announcements.published_at',
                    'schools.name as school_name',
                ]);

        return [
            'totalSchools' => $totalSchools,

            'activeSchools' => $activeSchools,

            'totalUsers' => $totalUsers,

            'totalStudents' => $totalStudents,

            'totalCoaches' => $totalCoaches,

            'totalScoutUnits' => $totalScoutUnits,

            'totalActivities' => $totalActivities,

            'activitiesThisMonth' => $activitiesThisMonth,

            'participantSlots' => $participantSlots,

            'globalPresencePercentage' => $globalPresencePercentage,

            'totalJournals' => $totalJournals,

            'publishedJournals' => $publishedJournals,

            'gradedStudents' => $gradedStudents,

            'averageFinalGrade' => round(
                $averageFinalGrade,
                2
            ),

            'publishedAnnouncements' => $publishedAnnouncements,

            'telegramLinked' => $telegramLinked,

            'telegramSent' => $telegramSent,

            'telegramFailed' => $telegramFailed,

            'schoolMonitoring' => $schoolMonitoring,

            'activityByMonth' => $activityByMonth,

            'upcomingActivities' => $upcomingActivities,

            'latestAnnouncements' => $latestAnnouncements,
        ];
    }

    protected function schoolMonitoring(): Collection
    {
        $schools =
            School::query()
                ->where(
                    'is_active',
                    true
                )
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'npsn',
                    'level',
                    'city',
                ]);

        $students =
            DB::table('students')
                ->whereNull(
                    'deleted_at'
                )
                ->where(
                    'status',
                    'active'
                )
                ->selectRaw(
                    'school_id, COUNT(*) as total'
                )
                ->groupBy(
                    'school_id'
                )
                ->pluck(
                    'total',
                    'school_id'
                );

        $coaches =
            DB::table('coaches')
                ->whereNull(
                    'deleted_at'
                )
                ->where(
                    'is_active',
                    true
                )
                ->selectRaw(
                    'school_id, COUNT(*) as total'
                )
                ->groupBy(
                    'school_id'
                )
                ->pluck(
                    'total',
                    'school_id'
                );

        $activities =
            DB::table('activities')
                ->whereNull(
                    'deleted_at'
                )
                ->selectRaw(
                    'school_id, COUNT(*) as total'
                )
                ->groupBy(
                    'school_id'
                )
                ->pluck(
                    'total',
                    'school_id'
                );

        $journals =
            DB::table('journals')
                ->whereNull(
                    'deleted_at'
                )
                ->selectRaw(
                    'school_id, COUNT(*) as total'
                )
                ->groupBy(
                    'school_id'
                )
                ->pluck(
                    'total',
                    'school_id'
                );

        $participantCounts =
            DB::table(
                'attendance_session_participants'
            )
                ->selectRaw(
                    'school_id, COUNT(*) as total'
                )
                ->groupBy(
                    'school_id'
                )
                ->pluck(
                    'total',
                    'school_id'
                );

        $physicalPresenceCounts =
            DB::table(
                'attendances'
            )
                ->whereIn(
                    'status',
                    [
                        'present',
                        'late',
                    ]
                )
                ->selectRaw(
                    'school_id, COUNT(*) as total'
                )
                ->groupBy(
                    'school_id'
                )
                ->pluck(
                    'total',
                    'school_id'
                );

        $gradeAverages =
            DB::table(
                'final_grades'
            )
                ->selectRaw(
                    'school_id, AVG(final_score) as average'
                )
                ->groupBy(
                    'school_id'
                )
                ->pluck(
                    'average',
                    'school_id'
                );

        return $schools->map(
            function ($school) use (
                $students,
                $coaches,
                $activities,
                $journals,
                $participantCounts,
                $physicalPresenceCounts,
                $gradeAverages
            ): array {

                $participants =
                    (int) (
                        $participantCounts[
                            $school->id
                        ] ?? 0
                    );

                $physicalPresence =
                    (int) (
                        $physicalPresenceCounts[
                            $school->id
                        ] ?? 0
                    );

                $presence =
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
                    'school' => $school,

                    'students' => (int) (
                        $students[
                            $school->id
                        ] ?? 0
                    ),

                    'coaches' => (int) (
                        $coaches[
                            $school->id
                        ] ?? 0
                    ),

                    'activities' => (int) (
                        $activities[
                            $school->id
                        ] ?? 0
                    ),

                    'journals' => (int) (
                        $journals[
                            $school->id
                        ] ?? 0
                    ),

                    'presence' => $presence,

                    'average_grade' => round(
                        (float) (
                            $gradeAverages[
                                $school->id
                            ] ?? 0
                        ),
                        2
                    ),
                ];
            }
        );
    }

    protected function activityByMonth(): Collection
    {
        $start =
            now()
                ->startOfMonth()
                ->subMonths(5);

        $activities =
            DB::table('activities')
                ->whereNull(
                    'deleted_at'
                )
                ->where(
                    'start_at',
                    '>=',
                    $start
                )
                ->get([
                    'start_at',
                ]);

        return collect(
            range(5, 0)
        )
            ->map(
                function (
                    int $monthsAgo
                ) use (
                    $activities
                ): array {

                    $month =
                        now()
                            ->startOfMonth()
                            ->subMonths(
                                $monthsAgo
                            );

                    $total =
                        $activities
                            ->filter(
                                function (
                                    $activity
                                ) use (
                                    $month
                                ): bool {

                                    return Carbon::parse(
                                        $activity
                                            ->start_at
                                    )
                                        ->format(
                                            'Y-m'
                                        )
                                    ===
                                    $month
                                        ->format(
                                            'Y-m'
                                        );
                                }
                            )
                            ->count();

                    return [
                        'label' => $month
                            ->translatedFormat(
                                'M Y'
                            ),

                        'total' => $total,
                    ];
                }
            );
    }
}
