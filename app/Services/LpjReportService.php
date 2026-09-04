<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Activity;
use App\Models\SchoolDocumentSetting;
use App\Models\ScoutGroup;
use App\Models\Semester;
use App\Support\SchoolContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class LpjReportService
{
    /** @return array<string, mixed> */
    public function build(int $academicYearId, int $semesterId, string $periodType, ?int $month = null): array
    {
        $school = app(SchoolContext::class)->school();
        abort_unless($school, 409, 'Pilih sekolah aktif terlebih dahulu.');

        $academicYear = AcademicYear::query()->findOrFail($academicYearId);
        $semester = Semester::query()->where('academic_year_id', $academicYear->id)->findOrFail($semesterId);
        [$periodStart, $periodEnd] = $this->resolvePeriod($semester, $periodType, $month);

        $activities = Activity::query()
            ->where('academic_year_id', $academicYear->id)
            ->where('semester_id', $semester->id)
            ->whereIn('status', ['published', 'completed'])
            ->whereBetween('start_at', [$periodStart, $periodEnd])
            ->with([
                'coaches:id,name',
                'scoutLevels:id,name',
                'journal.attachments',
                'attendanceSessions' => fn ($query) => $query->active()->withCount([
                    'participants',
                    'attendances as present_count' => fn ($query) => $query->whereIn('status', ['present', 'late']),
                    'attendances as sick_count' => fn ($query) => $query->where('status', 'sick'),
                    'attendances as excused_count' => fn ($query) => $query->where('status', 'excused'),
                    'attendances as absent_count' => fn ($query) => $query->where('status', 'absent'),
                ]),
            ])
            ->orderBy('start_at')
            ->get();

        $this->setAttachmentPaths($activities);

        return [
            'school' => $school,
            'academicYear' => $academicYear,
            'semester' => $semester,
            'documentSetting' => SchoolDocumentSetting::query()->with('responsibleCoach')->first(),
            'scoutGroup' => ScoutGroup::query()->where('is_active', true)->first(),
            'periodType' => $periodType,
            'month' => $month,
            'periodStart' => $periodStart,
            'periodEnd' => $periodEnd,
            'activities' => $activities,
            'activitiesByMonth' => $activities->groupBy(fn (Activity $activity): string => $activity->start_at->format('Y-m')),
            'attendance' => [
                'sessions' => $activities->sum(fn (Activity $activity): int => $activity->attendanceSessions->count()),
                'participants' => $activities->sum(fn (Activity $activity): int => $activity->attendanceSessions->sum('participants_count')),
                'present' => $activities->sum(fn (Activity $activity): int => $activity->attendanceSessions->sum('present_count')),
                'sick' => $activities->sum(fn (Activity $activity): int => $activity->attendanceSessions->sum('sick_count')),
                'excused' => $activities->sum(fn (Activity $activity): int => $activity->attendanceSessions->sum('excused_count')),
                'absent' => $activities->sum(fn (Activity $activity): int => $activity->attendanceSessions->sum('absent_count')),
            ],
        ];
    }

    /** @return array{CarbonImmutable, CarbonImmutable} */
    private function resolvePeriod(Semester $semester, string $periodType, ?int $month): array
    {
        abort_unless(in_array($periodType, ['monthly', 'semester'], true), 422, 'Jenis periode LPJ tidak valid.');

        $semesterStart = CarbonImmutable::parse($semester->start_date)->startOfDay();
        $semesterEnd = CarbonImmutable::parse($semester->end_date)->endOfDay();

        if ($periodType === 'semester') {
            return [$semesterStart, $semesterEnd];
        }

        abort_unless($month !== null, 422, 'Bulan wajib dipilih untuk LPJ bulanan.');
        $year = $month >= (int) $semesterStart->format('n') ? (int) $semesterStart->format('Y') : (int) $semesterEnd->format('Y');
        $monthStart = CarbonImmutable::create($year, $month, 1)->startOfMonth();
        $monthEnd = $monthStart->endOfMonth();
        abort_unless($monthStart->lte($semesterEnd) && $monthEnd->gte($semesterStart), 422, 'Bulan tidak termasuk dalam semester yang dipilih.');

        return [$monthStart->max($semesterStart), $monthEnd->min($semesterEnd)];
    }

    /** @param Collection<int, Activity> $activities */
    private function setAttachmentPaths(Collection $activities): void
    {
        $activities->each(function (Activity $activity): void {
            $activity->journal?->attachments->each(function ($attachment): void {
                $attachment->setAttribute('pdf_path', str_starts_with((string) $attachment->mime_type, 'image/') && Storage::disk('public')->exists($attachment->path)
                    ? Storage::disk('public')->path($attachment->path)
                    : null);
            });
        });
    }
}
