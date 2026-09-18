<?php

namespace App\Livewire\Dashboard;

use App\Models\AcademicYear;
use App\Models\Activity;
use App\Models\ActivityAssessment;
use App\Models\AttendanceSession;
use App\Models\Semester;
use App\Models\Student;
use App\Support\SchoolContext;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CoachDashboard extends Component
{
    public function render(): View
    {
        $user = auth()->user();

        abort_unless(
            $user?->hasRole('coach'),
            403
        );

        $schoolId = app(SchoolContext::class)->id();

        /*
        |--------------------------------------------------------------------------
        | Dashboard tetap aman apabila SchoolContext belum tersedia
        |--------------------------------------------------------------------------
        */

        if (! $schoolId) {
            return view(
                'livewire.dashboard.coach-dashboard',
                $this->emptyData()
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Tahun ajaran & semester aktif
        |--------------------------------------------------------------------------
        */

        $academicYear = AcademicYear::query()
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->first();

        $semester = Semester::query()
            ->when(
                $academicYear,
                fn ($query) => $query->where(
                    'academic_year_id',
                    $academicYear->id
                )
            )
            ->where('is_active', true)
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Query kegiatan semester berjalan
        |--------------------------------------------------------------------------
        */

        $activities = Activity::query()
            ->where('school_id', $schoolId)
            ->when(
                $academicYear,
                fn ($query) => $query->where(
                    'academic_year_id',
                    $academicYear->id
                )
            )
            ->when(
                $semester,
                fn ($query) => $query->where(
                    'semester_id',
                    $semester->id
                )
            );

        /*
        |--------------------------------------------------------------------------
        | Semua kegiatan sekolah aktif
        |--------------------------------------------------------------------------
        */

        $schoolActivities = Activity::query()
            ->where(
                'school_id',
                $schoolId
            )
            ->when(
                $academicYear,
                fn ($query) => $query->where(
                    'academic_year_id',
                    $academicYear->id
                )
            )
            ->when(
                $semester,
                fn ($query) => $query->where(
                    'semester_id',
                    $semester->id
                )
            );

        /*
        |--------------------------------------------------------------------------
        | Kegiatan yang ditugaskan kepada pembina login
        |--------------------------------------------------------------------------
        */

        $myActivities =
            (clone $schoolActivities)
                ->whereHas(
                    'coaches',
                    fn ($query) => $query->where(
                        'coaches.id',
                        $coach->id
                    )
                );

        /*
        |--------------------------------------------------------------------------
        | Statistik
        |--------------------------------------------------------------------------
        */

        $activeStudents = Student::query()
            ->where('school_id', $schoolId)
            ->where('status', 'active')
            ->count();

        $upcomingActivities = (clone $activities)
            ->where('status', 'published')
            ->where('start_at', '>=', now())
            ->count();

        $todayActivities =
            (clone $schoolActivities)
                ->whereNotIn(
                    'status',
                    ['cancelled']
                )
                ->whereDate(
                    'start_at',
                    today()
                )
                ->count();

        $nextActivities =
            (clone $myActivities)
                ->with([
                    'scoutLevels',
                    'coaches',
                ])
                ->where(
                    'status',
                    'published'
                )
                ->where(
                    'start_at',
                    '>=',
                    now()
                )
                ->orderBy(
                    'start_at'
                )
                ->limit(5)
                ->get();

        /*
        |--------------------------------------------------------------------------
        | Sesi absensi aktif
        |--------------------------------------------------------------------------
        */

        $openAttendanceSessions = AttendanceSession::query()
            ->whereHas(
                'activity',
                fn ($query) => $query->where(
                    'school_id',
                    $schoolId
                )
            )
            ->where('is_active', true)
            ->where(
                'open_at',
                '<=',
                now()
            )
            ->where(
                'close_at',
                '>=',
                now()
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Jurnal belum lengkap
        |--------------------------------------------------------------------------
        |
        | Kegiatan selesai tetapi:
        | - belum punya jurnal
        | - atau jurnal belum published.
        |--------------------------------------------------------------------------
        */

        $pendingJournals = (clone $activities)
            ->where(
                'status',
                'completed'
            )
            ->where(
                function ($query): void {
                    $query
                        ->whereDoesntHave(
                            'journal'
                        )
                        ->orWhereHas(
                            'journal',
                            fn ($query) => $query->where(
                                'status',
                                '!=',
                                'published'
                            )
                        );
                }
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Penilaian kegiatan belum selesai
        |--------------------------------------------------------------------------
        */

        $pendingAssessments =
            ActivityAssessment::query()
                ->where(
                    'school_id',
                    $schoolId
                )
                ->where(
                    'status',
                    'draft'
                )
                ->count();

        /*
        |--------------------------------------------------------------------------
        | Agenda berikutnya
        |--------------------------------------------------------------------------
        */

        $nextActivities = (clone $activities)
            ->with([
                'scoutLevels',
                'coaches',
            ])
            ->where(
                'status',
                'published'
            )
            ->where(
                'start_at',
                '>=',
                now()
            )
            ->orderBy(
                'start_at'
            )
            ->limit(5)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Kegiatan selesai yang jurnalnya perlu diperhatikan
        |--------------------------------------------------------------------------
        */

        $activitiesNeedingJournal =
            (clone $activities)
                ->with('journal')
                ->where(
                    'status',
                    'completed'
                )
                ->where(
                    function ($query): void {
                        $query
                            ->whereDoesntHave(
                                'journal'
                            )
                            ->orWhereHas(
                                'journal',
                                fn ($query) => $query->where(
                                    'status',
                                    '!=',
                                    'published'
                                )
                            );
                    }
                )
                ->orderByDesc(
                    'start_at'
                )
                ->limit(5)
                ->get();

        return view(
            'livewire.dashboard.coach-dashboard',
            [
                'hasSchool' => true,

                'academicYear' => $academicYear,

                'semester' => $semester,

                'statistics' => [
                    'students' => $activeStudents,

                    'upcoming' => $upcomingActivities,

                    'today' => $todayActivities,

                    'attendance' => $openAttendanceSessions,

                    'journals' => $pendingJournals,

                    'assessments' => $pendingAssessments,
                ],

                'nextActivities' => $nextActivities,

                'activitiesNeedingJournal' => $activitiesNeedingJournal,
            ]
        );
    }

    private function emptyData(): array
    {
        return [
            'hasSchool' => false,

            'academicYear' => null,

            'semester' => null,

            'statistics' => [
                'students' => 0,
                'upcoming' => 0,
                'today' => 0,
                'attendance' => 0,
                'journals' => 0,
                'assessments' => 0,
            ],

            'nextActivities' => collect(),

            'activitiesNeedingJournal' => collect(),
        ];
    }
}
