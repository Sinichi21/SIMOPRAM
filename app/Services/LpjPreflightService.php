<?php

namespace App\Services;

use App\Models\Activity;
use Illuminate\Support\Collection;

class LpjPreflightService
{
    /**
     * @param array<string, mixed> $report
     * @return array<int, string>
     */
    public function warnings(array $report): array
    {
        $activities = $report['activities']->where('status', '!=', 'cancelled');
        $warnings = [];
        $rostersByDay = [];

        foreach ($activities as $activity) {
            /** @var Activity $activity */
            $date = $activity->start_at->format('d/m/Y');
            $dayKey = $activity->start_at->format('Y-m-d');

            if ($activity->attendanceSessions->isEmpty()) {
                $warnings[] = "{$date} - {$activity->title}: belum ada sesi absensi.";
                continue;
            }

            foreach ($activity->attendanceSessions as $session) {
                $participantIds = $session->participants->pluck('student_id')->map(fn ($id): int => (int) $id)->all();
                $attendanceIds = $session->attendances->pluck('student_id')->map(fn ($id): int => (int) $id)->all();

                if ($session->attendances->isEmpty()) {
                    $warnings[] = "{$date} - {$activity->title}: sesi absensi #{$session->id} belum memiliki catatan kehadiran.";
                }

                if ($participantIds !== []) {
                    $outsideIds = array_diff($attendanceIds, $participantIds);
                    if ($outsideIds !== []) {
                        $warnings[] = "{$date} - {$activity->title}: ada absensi siswa di luar daftar peserta sesi #{$session->id}.";
                    }
                }

                $roster = array_unique(array_merge($participantIds, $attendanceIds));
                foreach ($roster as $studentId) {
                    $rostersByDay[$dayKey][(int) $studentId][] = [
                        'activityId' => $activity->id,
                        'start' => $activity->start_at,
                        'end' => $activity->end_at,
                        'title' => $activity->title,
                    ];
                }
            }
        }

        foreach ($rostersByDay as $day => $students) {
            foreach ($students as $studentId => $slots) {
                $count = count($slots);
                for ($i = 0; $i < $count; $i++) {
                    for ($j = $i + 1; $j < $count; $j++) {
                        $a = $slots[$i];
                        $b = $slots[$j];
                        if ($a['activityId'] === $b['activityId']) {
                            continue;
                        }

                        if ($a['start']->lt($b['end']) && $b['start']->lt($a['end'])) {
                            $warnings[] = "{$day}: siswa ID {$studentId} tercatat pada kegiatan yang waktunya bertabrakan ({$a['title']} / {$b['title']}).";
                        }
                    }
                }
            }
        }

        return array_values(array_unique($warnings));
    }
}
