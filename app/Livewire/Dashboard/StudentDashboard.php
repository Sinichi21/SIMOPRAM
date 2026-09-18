<?php

namespace App\Livewire\Dashboard;

use App\Models\Activity;
use App\Models\Student;
use App\Services\AnnouncementAudienceService;
use App\Services\StudentDocumentAccessService;
use App\Support\SchoolContext;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class StudentDashboard extends Component
{
    public function render(
        StudentDocumentAccessService $documentAccess
    ): View {
        $user = auth()->user();

        abort_unless(
            $user?->hasRole('student'),
            403
        );

        /*
        |--------------------------------------------------------------------------
        | Data siswa
        |--------------------------------------------------------------------------
        */

        $student = Student::query()
            ->where('user_id', $user->id)
            ->with([
                'school',
                'enrollments' => fn ($query) => $query
                    ->where('status', 'active')
                    ->with('classroom')
                    ->latest('id'),
            ])
            ->first();

        $documentCount =
            $documentAccess->countFor(
                $user
            );

        /*
        |--------------------------------------------------------------------------
        | Jika akun belum terhubung dengan data siswa
        |--------------------------------------------------------------------------
        */

        if (! $student) {
            return view(
                'livewire.dashboard.student-dashboard',
                [
                    'student' => null,
                    'enrollment' => null,
                    'attendance' => [
                        'participants' => 0,
                        'present' => 0,
                        'late' => 0,
                        'sick' => 0,
                        'excused' => 0,
                        'absent' => 0,
                        'percentage' => null,
                    ],
                    'documentCount' => 0,
                    'latestAnnouncements' => collect(),
                    'nextActivity' => null,
                    'recentAttendances' => collect(),
                ]
            );
        }

        $enrollment = $student
            ->enrollments
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Ringkasan Absensi
        |--------------------------------------------------------------------------
        |
        | Status mengikuti implementasi SIMPRAM:
        |
        | present  = hadir
        | late     = terlambat
        | sick     = sakit
        | excused  = izin
        | absent   = alpa
        |
        */

        $attendanceCounts = $student
            ->attendances()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $participants = $student
            ->attendanceParticipations()
            ->count();

        $present = (int) (
            $attendanceCounts['present'] ?? 0
        );

        $late = (int) (
            $attendanceCounts['late'] ?? 0
        );

        $sick = (int) (
            $attendanceCounts['sick'] ?? 0
        );

        $excused = (int) (
            $attendanceCounts['excused'] ?? 0
        );

        $absent = (int) (
            $attendanceCounts['absent'] ?? 0
        );

        $percentage = $participants > 0
            ? round(
                (($present + $late) / $participants) * 100,
                1
            )
            : null;

        /*
        |--------------------------------------------------------------------------
        | Kegiatan berikutnya
        |--------------------------------------------------------------------------
        */

        $schoolId = app(SchoolContext::class)->id()
            ?? $student->school_id;

        $nextActivity = Activity::query()
            ->where('school_id', $schoolId)
            ->where('status', 'published')
            ->where('start_at', '>=', now())
            ->orderBy('start_at')
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Pengumuman terbaru untuk siswa
        |--------------------------------------------------------------------------
        */

        $latestAnnouncements =
            app(
                AnnouncementAudienceService::class
            )
                ->publishedForUser(
                    $user
                )
                ->latest(
                    'published_at'
                )
                ->limit(5)
                ->get();

        /*
        |--------------------------------------------------------------------------
        | Riwayat absensi terbaru
        |--------------------------------------------------------------------------
        */

        $recentAttendances = $student
            ->attendances()
            ->latest('id')
            ->limit(5)
            ->get();

        return view(
            'livewire.dashboard.student-dashboard',
            [
                'student' => $student,
                'enrollment' => $enrollment,

                'attendance' => [
                    'participants' => $participants,
                    'present' => $present,
                    'late' => $late,
                    'sick' => $sick,
                    'excused' => $excused,
                    'absent' => $absent,
                    'percentage' => $percentage,
                ],

                'documentCount' => $documentCount,
                'latestAnnouncements' => $latestAnnouncements,

                'nextActivity' => $nextActivity,
                'recentAttendances' => $recentAttendances,
            ]
        );
    }
}
