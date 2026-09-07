<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Activity;
use App\Models\Coach;
use App\Models\SchoolDocumentSetting;
use App\Models\ScoutGroup;
use App\Models\Semester;
use App\Models\StudentEnrollment;
use App\Support\SchoolContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class LpjReportService
{
    /** @return array<string, mixed> */
    public function build(
        int $academicYearId,
        int $semesterId,
        string $periodType,
        ?int $month = null
    ): array {
        $school = app(SchoolContext::class)->school();
        abort_unless($school, 409, 'Pilih sekolah aktif terlebih dahulu.');

        $academicYear = AcademicYear::query()->findOrFail($academicYearId);
        $semester = Semester::query()
            ->where('academic_year_id', $academicYear->id)
            ->findOrFail($semesterId);

        [$periodStart, $periodEnd] = $this->resolvePeriod(
            $semester,
            $periodType,
            $month
        );

        $documentSetting = SchoolDocumentSetting::query()
            ->with('responsibleCoach')
            ->first();

        $scoutGroup = ScoutGroup::query()
            ->where('is_active', true)
            ->first();

        $activities = Activity::query()
            ->where('academic_year_id', $academicYear->id)
            ->where('semester_id', $semester->id)
            ->where('activity_type', 'regular')
            ->whereIn('status', [
                'published',
                'completed',
                'cancelled',
            ])
            ->whereBetween('start_at', [$periodStart, $periodEnd])
            ->with([
                'coaches:id,name,nip,phone',
                'scoutLevels:id,name',
                'journal.attachments',
                'attendanceSessions' => fn ($query) => $query
                    ->active()
                    ->with([
                        'participants:id,attendance_session_id,student_id',
                        'attendances:id,attendance_session_id,activity_id,student_id,status',
                    ]),
            ])
            ->orderBy('start_at')
            ->get();

        $this->setAttachmentPaths($activities);

        $reportMonths = $this->buildReportMonths(
            $academicYear,
            $periodStart,
            $periodEnd,
            $activities,
            $documentSetting
        );

        $activeActivities = $activities->where('status', '!=', 'cancelled');

        return [
            'school' => $school,
            'academicYear' => $academicYear,
            'semester' => $semester,
            'documentSetting' => $documentSetting,
            'scoutGroup' => $scoutGroup,
            'periodType' => $periodType,
            'month' => $month,
            'periodStart' => $periodStart,
            'periodEnd' => $periodEnd,
            'activities' => $activities,
            'reportMonths' => $reportMonths,
            'attendance' => $this->attendanceSummary($activeActivities),
            'schoolLogoPath' => $this->localImagePath($school->logo),
            'scoutGroupLogoPath' => $this->localImagePath($scoutGroup?->logo),
            'coverBorderPath' => public_path('images/reports/pramuka-cover-border.png'),
            'signingDate' => $periodEnd,
            'schedule' => $this->scheduleMetadata(
                $documentSetting,
                $activities
            ),
        ];
    }

    /** @return array{CarbonImmutable, CarbonImmutable} */
    private function resolvePeriod(
        Semester $semester,
        string $periodType,
        ?int $month
    ): array {
        abort_unless(
            in_array($periodType, ['monthly', 'semester'], true),
            422,
            'Jenis periode LPJ tidak valid.'
        );

        $semesterStart = CarbonImmutable::parse($semester->start_date)
            ->startOfDay();
        $semesterEnd = CarbonImmutable::parse($semester->end_date)
            ->endOfDay();

        if ($periodType === 'semester') {
            return [$semesterStart, $semesterEnd];
        }

        abort_unless(
            $month !== null,
            422,
            'Bulan wajib dipilih untuk LPJ bulanan.'
        );

        $year = $month >= (int) $semesterStart->format('n')
            ? (int) $semesterStart->format('Y')
            : (int) $semesterEnd->format('Y');

        $monthStart = CarbonImmutable::create($year, $month, 1)
            ->startOfMonth();
        $monthEnd = $monthStart->endOfMonth();

        abort_unless(
            $monthStart->lte($semesterEnd)
                && $monthEnd->gte($semesterStart),
            422,
            'Bulan tidak termasuk dalam semester yang dipilih.'
        );

        return [
            $monthStart->max($semesterStart),
            $monthEnd->min($semesterEnd),
        ];
    }

    /**
     * @param  Collection<int, Activity>  $activities
     * @return Collection<int, array<string, mixed>>
     */
    private function buildReportMonths(
        AcademicYear $academicYear,
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd,
        Collection $activities,
        ?SchoolDocumentSetting $documentSetting
    ): Collection {
        $months = collect();
        $cursor = $periodStart->startOfMonth();

        while ($cursor->lte($periodEnd)) {
            $monthStart = $cursor->max($periodStart);
            $monthEnd = $cursor->endOfMonth()->min($periodEnd);
            $monthActivities = $activities
                ->filter(fn (Activity $activity): bool => $activity->start_at->betweenIncluded($monthStart, $monthEnd))
                ->values();

            $dates = $this->reportDates(
                $monthStart,
                $monthEnd,
                $monthActivities,
                $documentSetting?->extracurricular_weekday
            );

            $dateRows = $this->activityRows($dates, $monthActivities);
            $routineSessions = $this->routineSessionRows(
                $academicYear,
                $monthStart,
                $monthEnd,
                $monthActivities,
                $documentSetting?->extracurricular_weekday
            );

            $months->push([
                'key' => $cursor->format('Y-m'),
                'label' => $cursor->translatedFormat('F Y'),
                'start' => $monthStart,
                'end' => $monthEnd,
                'activities' => $monthActivities,
                'dates' => $dates,
                'dateRows' => $dateRows,
                'routineSessions' => $routineSessions,
                'coachRows' => $this->coachRows(
                    $dates,
                    $monthActivities,
                    $documentSetting?->responsibleCoach
                ),
            ]);

            $cursor = $cursor->addMonth();
        }

        return $months;
    }

    /**
     * @param  Collection<int, Activity>  $activities
     * @return Collection<int, CarbonImmutable>
     */
    private function reportDates(
        CarbonImmutable $monthStart,
        CarbonImmutable $monthEnd,
        Collection $activities,
        ?int $weekday
    ): Collection {
        $dates = collect();

        if ($weekday && $weekday >= 1 && $weekday <= 7) {
            $cursor = $monthStart->startOfDay();

            while ($cursor->lte($monthEnd)) {
                if ($cursor->isoWeekday() === $weekday) {
                    $dates->push($cursor);
                }

                $cursor = $cursor->addDay();
            }
        }

        $activities->each(function (Activity $activity) use ($dates): void {
            $date = CarbonImmutable::parse($activity->start_at)->startOfDay();

            if (! $dates->contains(
                fn (CarbonImmutable $existing): bool => $existing->isSameDay($date)
            )) {
                $dates->push($date);
            }
        });

        return $dates
            ->sortBy(fn (CarbonImmutable $date): string => $date->format('Y-m-d'))
            ->values();
    }

    /**
     * @param  Collection<int, CarbonImmutable>  $dates
     * @param  Collection<int, Activity>  $activities
     * @return Collection<int, array<string, mixed>>
     */
    private function activityRows(
        Collection $dates,
        Collection $activities
    ): Collection {
        return $dates->map(function (CarbonImmutable $date) use ($activities): array {
            $dayActivities = $activities
                ->filter(fn (Activity $activity): bool => $activity->start_at->isSameDay($date))
                ->sortBy('start_at')
                ->values();

            $sessionRows = $dayActivities
                ->groupBy(fn (Activity $activity): int => (int) ($activity->routine_session_no ?: 1))
                ->sortKeys()
                ->map(function (Collection $sessionActivities, int|string $sessionNo): array {
                    $active = $sessionActivities
                        ->where('status', '!=', 'cancelled')
                        ->sortBy('start_at')
                        ->values();
                    $cancelled = $sessionActivities
                        ->where('status', 'cancelled')
                        ->values();
                    $isCancelled = $active->isEmpty();
                    $referenceActivity = $active->first() ?: $sessionActivities->first();

                    $materials = $active
                        ->flatMap(fn (Activity $activity): Collection => $this->activityMaterials($activity))
                        ->prepend('Doa')
                        ->filter()
                        ->unique()
                        ->values();

                    $holidayLabel = null;

                    if ($isCancelled) {
                        $reason = $cancelled
                            ->map(function (Activity $activity): string {
                                $description = trim((string) $activity->description);

                                return $description !== ''
                                    ? $description
                                    : $activity->title;
                            })
                            ->filter()
                            ->implode(' / ');

                        $holidayLabel = $reason !== ''
                            ? 'LIBUR - '.$reason
                            : 'LIBUR / TIDAK ADA KEGIATAN';
                    }

                    return [
                        'number' => (int) $sessionNo,
                        'label' => $this->routineSessionLabel((int) $sessionNo, $sessionActivities),
                        'startTime' => $referenceActivity?->start_at?->format('H:i'),
                        'endTime' => $referenceActivity?->end_at?->format('H:i'),
                        'activities' => $sessionActivities,
                        'isCancelled' => $isCancelled,
                        'holidayLabel' => $holidayLabel,
                        'materials' => $materials,
                    ];
                })
                ->values();

            $isHoliday = $sessionRows->isEmpty()
                || $sessionRows->every(fn (array $session): bool => $session['isCancelled']);

            $holidayLabel = null;

            if ($isHoliday) {
                $holidayLabel = $sessionRows
                    ->pluck('holidayLabel')
                    ->filter()
                    ->unique()
                    ->implode(' / ');

                if ($holidayLabel === '') {
                    $holidayLabel = 'LIBUR / TIDAK ADA KEGIATAN';
                }
            }

            return [
                'date' => $date,
                'activities' => $dayActivities,
                'sessions' => $sessionRows,
                'isHoliday' => $isHoliday,
                'holidayLabel' => $holidayLabel,
            ];
        });
    }

    /**
     * @param  Collection<int, Activity>  $activities
     * @return Collection<int, array<string, mixed>>
     */
    private function routineSessionRows(
        AcademicYear $academicYear,
        CarbonImmutable $monthStart,
        CarbonImmutable $monthEnd,
        Collection $activities,
        ?int $weekday
    ): Collection {
        return $activities
            ->groupBy(fn (Activity $activity): int => (int) ($activity->routine_session_no ?: 1))
            ->sortKeys()
            ->map(function (Collection $sessionActivities, int|string $sessionNo) use (
                $academicYear,
                $monthStart,
                $monthEnd,
                $weekday
            ): array {
                $sessionActivities = $sessionActivities
                    ->sortBy('start_at')
                    ->values();
                $dates = $this->reportDates(
                    $monthStart,
                    $monthEnd,
                    $sessionActivities,
                    $weekday
                );
                $dateRows = $this->activityRows($dates, $sessionActivities);
                $attendance = $this->attendanceMatrix(
                    $academicYear,
                    $dates,
                    $sessionActivities,
                    $dateRows
                );
                $referenceActivity = $sessionActivities
                    ->firstWhere('status', '!=', 'cancelled')
                    ?: $sessionActivities->first();

                return [
                    'number' => (int) $sessionNo,
                    'label' => $this->routineSessionLabel((int) $sessionNo, $sessionActivities),
                    'startTime' => $referenceActivity?->start_at?->format('H:i'),
                    'endTime' => $referenceActivity?->end_at?->format('H:i'),
                    'activities' => $sessionActivities,
                    'dates' => $dates,
                    'dateMeta' => $attendance['dateMeta'],
                    'attendanceClasses' => $attendance['classes'],
                    'documentation' => $this->documentationRows($sessionActivities),
                ];
            })
            ->values();
    }

    /**
     * @param  Collection<int, Activity>  $activities
     */
    private function routineSessionLabel(int $sessionNo, Collection $activities): string
    {
        $scoutLevels = $activities
            ->flatMap(fn (Activity $activity) => $activity->scoutLevels->pluck('name'))
            ->filter()
            ->unique()
            ->values();

        $label = 'Sesi '.$sessionNo;

        if ($scoutLevels->isNotEmpty()) {
            $label .= ' - '.$scoutLevels->implode(' / ');
        }

        return $label;
    }

    /** @return Collection<int, string> */
    private function activityMaterials(Activity $activity): Collection
    {
        $material = trim((string) ($activity->journal?->material ?? ''));

        if ($material !== '') {
            return collect(preg_split('/\R+/', $material) ?: [])
                ->map(fn (string $line): string => trim($line))
                ->filter()
                ->values();
        }

        $description = trim((string) $activity->description);

        return collect([
            $description !== '' ? $description : $activity->title,
        ])->filter();
    }

    /**
     * @param  Collection<int, CarbonImmutable>  $dates
     * @param  Collection<int, Activity>  $activities
     * @param  Collection<int, array<string, mixed>>  $dateRows
     * @return array{classes: Collection<int, array<string, mixed>>, dateMeta: Collection<string, array<string, mixed>>}
     */
    private function attendanceMatrix(
        AcademicYear $academicYear,
        Collection $dates,
        Collection $activities,
        Collection $dateRows
    ): array {
        $sessions = $activities
            ->where('status', '!=', 'cancelled')
            ->flatMap(fn (Activity $activity) => $activity->attendanceSessions)
            ->values();

        $participantIds = $sessions
            ->flatMap(fn ($session) => $session->participants->pluck('student_id'))
            ->filter()
            ->unique()
            ->values();

        $classroomScopeIds = $sessions
            ->where('participant_scope', 'classroom')
            ->pluck('participant_scope_id')
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        $hasAllScope = $sessions->contains(
            fn ($session): bool => $session->participant_scope === 'all'
        );

        $enrollmentQuery = StudentEnrollment::query()
            ->with(['student:id,name,status', 'classroom:id,name,grade'])
            ->where('academic_year_id', $academicYear->id)
            ->where('status', 'active');

        if ($participantIds->isNotEmpty()) {
            $enrollmentQuery->whereIn('student_id', $participantIds);
        } elseif ($hasAllScope) {
            // Fallback untuk data lama yang belum mempunyai snapshot peserta.
        } elseif ($classroomScopeIds->isNotEmpty()) {
            $enrollmentQuery->whereIn('classroom_id', $classroomScopeIds);
        } else {
            $enrollmentQuery->whereIn('student_id', $participantIds);
        }

        $enrollments = $enrollmentQuery
            ->get()
            ->filter(fn (StudentEnrollment $enrollment): bool => (bool) $enrollment->student && (bool) $enrollment->classroom);

        $attendanceByDateStudent = [];

        foreach ($sessions as $session) {
            $activity = $activities->firstWhere('id', $session->activity_id);

            if (! $activity) {
                continue;
            }

            $dateKey = $activity->start_at->format('Y-m-d');

            foreach ($session->attendances as $attendance) {
                $current = $attendanceByDateStudent[$dateKey][$attendance->student_id] ?? null;
                $attendanceByDateStudent[$dateKey][$attendance->student_id] = $this->preferAttendanceStatus(
                    $current,
                    $attendance->status
                );
            }
        }

        $dateMeta = $dateRows->mapWithKeys(function (array $row): array {
            $dateKey = $row['date']->format('Y-m-d');

            return [
                $dateKey => [
                    'date' => $row['date'],
                    'isHoliday' => $row['isHoliday'],
                    'holidayLabel' => $row['holidayLabel'],
                ],
            ];
        });

        $classes = $enrollments
            ->groupBy('classroom_id')
            ->map(function (Collection $classEnrollments) use ($dates, $attendanceByDateStudent): array {
                $classroom = $classEnrollments->first()->classroom;
                $students = $classEnrollments
                    ->sortBy(fn (StudentEnrollment $enrollment): string => mb_strtolower($enrollment->student->name))
                    ->values()
                    ->map(function (StudentEnrollment $enrollment) use ($dates, $attendanceByDateStudent): array {
                        $statuses = [];

                        foreach ($dates as $date) {
                            $dateKey = $date->format('Y-m-d');
                            $status = $attendanceByDateStudent[$dateKey][$enrollment->student_id] ?? null;
                            $statuses[$dateKey] = $this->attendanceCode($status);
                        }

                        return [
                            'studentId' => $enrollment->student_id,
                            'name' => $enrollment->student->name,
                            'className' => $enrollment->classroom->name,
                            'statuses' => $statuses,
                        ];
                    });

                return [
                    'classroom' => $classroom,
                    'students' => $students,
                ];
            })
            ->sortBy(function (array $class): string {
                $grade = str_pad((string) ($class['classroom']->grade ?? 99), 2, '0', STR_PAD_LEFT);

                return $grade.'-'.$class['classroom']->name;
            })
            ->values();

        return [
            'classes' => $classes,
            'dateMeta' => $dateMeta,
        ];
    }

    private function preferAttendanceStatus(?string $current, string $candidate): string
    {
        $priority = [
            'present' => 5,
            'late' => 4,
            'sick' => 3,
            'excused' => 2,
            'absent' => 1,
        ];

        if ($current === null) {
            return $candidate;
        }

        return ($priority[$candidate] ?? 0) > ($priority[$current] ?? 0)
            ? $candidate
            : $current;
    }

    private function attendanceCode(?string $status): string
    {
        return match ($status) {
            'present', 'late' => 'H',
            'sick' => 'S',
            'excused' => 'I',
            'absent' => 'A',
            default => '-',
        };
    }

    /**
     * @param  Collection<int, CarbonImmutable>  $dates
     * @param  Collection<int, Activity>  $activities
     * @return Collection<int, array<string, mixed>>
     */
    private function coachRows(
        Collection $dates,
        Collection $activities,
        ?Coach $responsibleCoach
    ): Collection {
        $coaches = $activities
            ->flatMap(fn (Activity $activity) => $activity->coaches)
            ->when($responsibleCoach, fn (Collection $collection) => $collection->push($responsibleCoach))
            ->unique('id')
            ->sortBy('name')
            ->values();

        return $coaches->map(function (Coach $coach) use ($dates, $activities): array {
            $statuses = [];

            foreach ($dates as $date) {
                $dateKey = $date->format('Y-m-d');
                $dayActivities = $activities
                    ->filter(fn (Activity $activity): bool => $activity->start_at->isSameDay($date));

                $hasActiveActivity = $dayActivities->contains(
                    fn (Activity $activity): bool => $activity->status !== 'cancelled'
                );

                if (! $hasActiveActivity) {
                    $statuses[$dateKey] = 'LIBUR';

                    continue;
                }

                $assigned = $dayActivities
                    ->where('status', '!=', 'cancelled')
                    ->contains(function (Activity $activity) use ($coach): bool {
                        return $activity->coaches->contains('id', $coach->id);
                    });

                $statuses[$dateKey] = $assigned ? 'H' : '-';
            }

            return [
                'coach' => $coach,
                'statuses' => $statuses,
            ];
        });
    }

    /**
     * @param  Collection<int, Activity>  $activities
     * @return Collection<int, array<string, mixed>>
     */
    private function documentationRows(Collection $activities): Collection
    {
        return $activities
            ->where('status', '!=', 'cancelled')
            ->map(function (Activity $activity): array {
                $attachments = $activity->journal?->attachments
                    ?->filter(fn ($attachment): bool => (bool) $attachment->pdf_path)
                    ->values() ?? collect();

                return [
                    'activity' => $activity,
                    'attachments' => $attachments,
                ];
            })
            ->filter(fn (array $row): bool => $row['attachments']->isNotEmpty())
            ->values();
    }

    /** @param Collection<int, Activity> $activities */
    private function attendanceSummary(Collection $activities): array
    {
        $sessions = $activities
            ->flatMap(fn (Activity $activity) => $activity->attendanceSessions)
            ->values();

        $attendances = $sessions
            ->flatMap(fn ($session) => $session->attendances)
            ->values();

        return [
            'sessions' => $sessions->count(),
            'participants' => $sessions->sum(fn ($session): int => $session->participants->count()),
            'present' => $attendances->whereIn('status', ['present', 'late'])->count(),
            'sick' => $attendances->where('status', 'sick')->count(),
            'excused' => $attendances->where('status', 'excused')->count(),
            'absent' => $attendances->where('status', 'absent')->count(),
        ];
    }

    /**
     * @param  Collection<int, Activity>  $activities
     * @return array<string, mixed>
     */
    private function scheduleMetadata(
        ?SchoolDocumentSetting $setting,
        Collection $activities
    ): array {
        $firstActivity = $activities->firstWhere('status', '!=', 'cancelled');
        $weekday = $setting?->extracurricular_weekday;

        if (! $weekday && $firstActivity) {
            $weekday = $firstActivity->start_at->isoWeekday();
        }

        return [
            'weekday' => $weekday,
            'dayName' => $weekday
                ? $this->weekdayName($weekday)
                : '-',
            'startTime' => $this->displayTime(
                $setting?->extracurricular_start_time
                    ?: $firstActivity?->start_at?->format('H:i')
            ),
            'endTime' => $this->displayTime(
                $setting?->extracurricular_end_time
                    ?: $firstActivity?->end_at?->format('H:i')
            ),
            'location' => $setting?->extracurricular_location
                ?: $firstActivity?->location
                ?: '-',
        ];
    }

    private function weekdayName(int $weekday): string
    {
        return [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ][$weekday] ?? '-';
    }

    private function displayTime(mixed $value): ?string
    {
        if (! $value) {
            return null;
        }

        return substr((string) $value, 0, 5);
    }

    /** @param Collection<int, Activity> $activities */
    private function setAttachmentPaths(Collection $activities): void
    {
        $activities->each(function (Activity $activity): void {
            $activity->journal?->attachments->each(function ($attachment): void {
                $attachment->setAttribute(
                    'pdf_path',
                    str_starts_with((string) $attachment->mime_type, 'image/')
                        ? $this->localImagePath($attachment->path)
                        : null
                );
            });
        });
    }

    private function localImagePath(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $normalized = ltrim($path, '/');

        if (str_starts_with($normalized, 'storage/')) {
            $normalized = substr($normalized, strlen('storage/'));
        }

        if (Storage::disk('public')->exists($normalized)) {
            return Storage::disk('public')->path($normalized);
        }

        $publicPath = public_path(ltrim($path, '/'));

        return is_file($publicPath) ? $publicPath : null;
    }
}
