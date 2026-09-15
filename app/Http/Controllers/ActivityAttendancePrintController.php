<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\AttendanceSession;
use App\Services\GlobalActivityAttendance;
use App\Support\SchoolContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class ActivityAttendancePrintController extends Controller
{
    public function global(Request $request, int $activityId, GlobalActivityAttendance $service): Response
    {
        $activity = $service->activity($activityId);
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:150'], 'role' => ['nullable', 'in:student,coach'],
            'attendance' => ['nullable', Rule::in([...array_keys(GlobalActivityAttendance::STATUSES), 'unmarked'])], 'blank' => ['sometimes', 'boolean']]);
        $members = $service->participants($activity, $filters['search'] ?? '', $filters['role'] ?? '', $filters['attendance'] ?? '')->limit(1001)->get();
        abort_if($members->count() > 1000, 422, 'Maksimal 1.000 peserta per cetakan. Persempit pencarian atau filter.');
        $rows = $members->map(fn ($member): array => ['name' => $member->name, 'identifier' => $member->identifier,
            'group' => $member->entry->name, 'school' => $member->school_name,
            'role' => $member->role === 'coach' ? 'Pembina' : ($member->is_reserve ? 'Cadangan' : 'Peserta'),
            'status' => GlobalActivityAttendance::STATUSES[$member->attendance_status] ?? 'Belum dicatat', 'time' => $member->checked_in_at?->format('d-m-Y H:i')]);

        return $this->document($activity, $rows, $request->boolean('blank', true), 'Absensi kegiatan');
    }

    public function school(Request $request, int $activityId, int $sessionId): Response
    {
        abort_unless($schoolId = app(SchoolContext::class)->id(), 409);
        abort_unless($request->user()?->can('attendance_sessions.view'), 403);
        $request->validate(['blank' => ['sometimes', 'boolean']]);
        $activity = Activity::where('school_id', $schoolId)->findOrFail($activityId);
        $session = AttendanceSession::where('school_id', $schoolId)->where('activity_id', $activity->id)->findOrFail($sessionId);
        $members = $session->participants()->where('school_id', $schoolId)
            ->whereHas('student', fn ($query) => $query->where('school_id', $schoolId))
            ->with('student')->limit(1001)->get();
        abort_if($members->count() > 1000, 422, 'Maksimal 1.000 peserta per cetakan. Buat sesi dengan cakupan peserta lebih kecil.');
        $attendances = $session->attendances()->where('school_id', $schoolId)->get()->keyBy('student_id');
        $rows = $members->sortBy('student.name')->map(function ($member) use ($activity, $attendances): array {
            $attendance = $attendances->get($member->student_id);

            return ['name' => $member->student->name, 'identifier' => $member->student->nis, 'group' => 'Siswa',
                'school' => $activity->school->name, 'role' => 'Peserta',
                'status' => [...GlobalActivityAttendance::STATUSES, 'late' => 'Terlambat'][$attendance?->status] ?? 'Belum dicatat',
                'time' => $attendance?->checked_in_at?->format('d-m-Y H:i')];
        })->values();

        return $this->document($activity, $rows, $request->boolean('blank', true), $session->name);
    }

    private function document(Activity $activity, Collection $rows, bool $blank, string $sessionName): Response
    {
        return Pdf::loadView('reports.pdf.activity-attendance-form', compact('activity', 'rows', 'blank', 'sessionName'))
            ->setPaper('a4', 'landscape')->setOption('isRemoteEnabled', false)
            ->download(($blank ? 'form' : 'rekap').'-absensi-'.$activity->id.'.pdf')->withHeaders(['Cache-Control' => 'private, no-store']);
    }
}
