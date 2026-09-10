<?php

namespace App\Livewire\Reports;

use App\Exports\ReportViewExport;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\AttendanceSessionParticipant;
use App\Models\Classroom;
use App\Models\Semester;
use App\Models\Student;
use App\Services\AttendanceWeightService;
use App\Support\SchoolContext;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AttendanceDetail extends Component
{
    use WithPagination;

    public ?int $academicYearId = null;

    public ?int $semesterId = null;

    public ?int $classroomId = null;

    public string $period = 'semester';

    public string $activityType = 'regular';

    public string $startDate = '';

    public string $endDate = '';

    public string $search = '';

    public const TYPES = ['regular' => 'Latihan rutin', 'special' => 'Semua kegiatan khusus', 'training' => 'Pelatihan', 'ceremony' => 'Upacara', 'camp' => 'Perkemahan', 'competition' => 'Lomba', 'service' => 'Bakti sosial', 'other' => 'Lainnya'];

    public const STATUSES = ['present' => 'H', 'late' => 'T', 'sick' => 'S', 'excused' => 'I', 'absent' => 'A', 'unrecorded' => '?'];

    public function mount(): void
    {
        $this->authorizeReport();
        $this->academicYearId = AcademicYear::query()->where('is_active', true)->value('id');
        $this->updatedAcademicYearId();
    }

    protected function authorizeReport(): void
    {
        abort_unless(auth()->user()?->can('reports.attendance.view'), 403);
        abort_unless(app(SchoolContext::class)->id(), 409);
    }

    public function updatedAcademicYearId(): void
    {
        $year = AcademicYear::query()->find($this->academicYearId);
        $this->semesterId = $year ? Semester::query()->where('academic_year_id', $year->id)->where('is_active', true)->value('id') : null;
        $this->classroomId = null;
        $this->startDate = $year?->start_date?->toDateString() ?? '';
        $this->endDate = $year?->end_date?->toDateString() ?? '';
        $this->resetPage();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['academicYearId', 'semesterId', 'classroomId', 'period', 'activityType', 'startDate', 'endDate', 'search'], true)) {
            $this->resetPage();
        }
    }

    protected function reportData(bool $export = false): array
    {
        $this->authorizeReport();
        $empty = ['groups' => collect(), 'rows' => collect(), 'students' => null, 'sessionCount' => 0, 'filterError' => null];
        $year = AcademicYear::query()->find($this->academicYearId);
        if (! $year || ! in_array($this->period, ['semester', 'year', 'range'], true) || ! array_key_exists($this->activityType, self::TYPES)) {
            return [...$empty, 'filterError' => 'Pilih tahun ajaran dan periode yang valid.'];
        }
        $semester = $this->period === 'semester' ? Semester::query()->where('academic_year_id', $year->id)->find($this->semesterId) : null;
        if ($this->period === 'semester' && ! $semester) {
            return [...$empty, 'filterError' => 'Pilih semester untuk menampilkan rekap.'];
        }
        if ($this->classroomId && ! Classroom::query()->whereKey($this->classroomId)->exists()) {
            return [...$empty, 'filterError' => 'Kelas tidak tersedia di sekolah aktif.'];
        }
        $start = $semester?->start_date?->toDateString() ?? $year->start_date->toDateString();
        $end = $semester?->end_date?->toDateString() ?? $year->end_date->toDateString();
        if ($this->period === 'range') {
            $validator = Validator::make(['start' => $this->startDate, 'end' => $this->endDate], [
                'start' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.$start, 'before_or_equal:'.$end],
                'end' => ['required', 'date_format:Y-m-d', 'after_or_equal:start', 'before_or_equal:'.$end],
            ]);
            if ($validator->fails()) {
                return [...$empty, 'filterError' => 'Pilih rentang tanggal berurutan di dalam tahun ajaran yang dipilih.'];
            }
            $start = $this->startDate;
            $end = $this->endDate;
        }
        $sessions = AttendanceSession::query()->active()
            ->whereBetween('open_at', [$start.' 00:00:00', $end.' 23:59:59'])
            ->whereHas('activity', function ($query) use ($year, $semester): void {
                $query->where('academic_year_id', $year->id);
                if ($semester) {
                    $query->where('semester_id', $semester->id);
                }
                $query->where('activity_type', $this->activityType === 'special' ? '!=' : '=', $this->activityType === 'special' ? 'regular' : $this->activityType);
            })->with('activity.semester')->orderBy('open_at')->orderBy('id')->get();
        $studentQuery = Student::query()->with(['enrollments' => fn ($query) => $query->where('academic_year_id', $year->id)->with('classroom')])
            ->whereHas('enrollments', function ($query) use ($year): void {
                $query->where('academic_year_id', $year->id);
                if ($this->classroomId) {
                    $query->where('classroom_id', $this->classroomId);
                }
            })->when(trim($this->search) !== '', fn ($query) => $query->where(fn ($query) => $query->where('name', 'like', '%'.trim($this->search).'%')->orWhere('nis', 'like', '%'.trim($this->search).'%')))->orderBy('name')->orderBy('id');
        $students = $export ? $studentQuery->get() : $studentQuery->paginate(50);
        $participants = AttendanceSessionParticipant::query()->whereIn('attendance_session_id', $sessions->modelKeys())
            ->whereIn('student_id', $students->pluck('id'))->get()->groupBy('student_id');
        $attendances = Attendance::query()->whereIn('attendance_session_id', $sessions->modelKeys())
            ->whereIn('student_id', $students->pluck('id'))->get()->groupBy('student_id');
        $groups = $sessions->groupBy(fn ($session) => $session->activity->semester_id ?? 0)->map(fn ($items) => [
            'name' => $items->first()->activity->semester?->name ?? 'Tanpa semester',
            'sessions' => $items,
            'months' => $items->groupBy(fn ($session) => $session->open_at->translatedFormat('F Y')),
        ]);
        $weights = app(AttendanceWeightService::class)->factors();
        $rows = collect($export ? $students->all() : $students->items())->map(function ($student) use ($groups, $participants, $attendances, $weights): array {
            $participating = $participants->get($student->id, collect())->keyBy('attendance_session_id');
            $recorded = $attendances->get($student->id, collect())->keyBy('attendance_session_id');
            $cells = [];
            $summaries = [];
            $total = array_fill_keys(array_keys(self::STATUSES), 0);
            foreach ($groups as $key => $group) {
                $counts = array_fill_keys(array_keys(self::STATUSES), 0);
                foreach ($group['sessions'] as $session) {
                    $status = $participating->has($session->id) ? ($recorded->get($session->id)?->status ?? 'unrecorded') : null;
                    $cells[$session->id] = $status === null ? '-' : (self::STATUSES[$status] ?? '?');
                    if ($status !== null) {
                        $counts[array_key_exists($status, $counts) ? $status : 'unrecorded']++;
                    }
                }
                $summaries[$key] = $this->summarize($counts, $weights);
                foreach ($counts as $status => $count) {
                    $total[$status] += $count;
                }
            }

            return ['student' => $student, 'cells' => $cells, 'summaries' => $summaries, 'total' => $this->summarize($total, $weights)];
        });

        return compact('groups', 'rows', 'students') + ['sessionCount' => $sessions->count(), 'filterError' => null,
            'periodLabel' => $year->name.' / '.$start.' - '.$end,
            'schoolName' => app(SchoolContext::class)->school()?->name,
        ];
    }

    protected function summarize(array $counts, array $weights): array
    {
        $participants = array_sum($counts);
        $points = 0;
        foreach ($counts as $status => $count) {
            $points += $count * ($weights[$status] ?? 0);
        }

        return $counts + ['participants' => $participants, 'percentage' => $participants ? round(($counts['present'] + $counts['late']) / $participants * 100, 2) : null,
            'weighted' => $participants ? round($points / $participants * 100, 2) : null];
    }

    public function exportExcel(): BinaryFileResponse
    {
        $this->authorizeReport();
        abort_unless(auth()->user()?->can('reports.export'), 403);
        $data = $this->reportData(true);
        abort_if($data['filterError'], 422, $data['filterError'] ?? 'Filter tidak valid.');

        return Excel::download(new ReportViewExport('exports.reports.attendance-detail', $data + ['activityLabel' => self::TYPES[$this->activityType]], 'Absensi Detail'), 'rekap-absensi-detail-'.now()->format('Ymd-His').'.xlsx');
    }

    public function render(): View
    {
        return view('livewire.reports.attendance-detail', $this->reportData() + [
            'years' => AcademicYear::query()->orderByDesc('start_date')->get(),
            'semesters' => Semester::query()->where('academic_year_id', $this->academicYearId)->orderBy('semester_number')->get(),
            'classrooms' => Classroom::query()->orderBy('grade')->orderBy('name')->get(),
            'types' => self::TYPES,
        ]);
    }
}
