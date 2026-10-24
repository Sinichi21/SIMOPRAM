<?php

namespace App\Livewire\Attendances;

use App\Models\AcademicYear;
use App\Models\Activity;
use App\Models\Semester;
use App\Services\ActivityAssignmentFilter;
use App\Support\SchoolContext;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filterAcademicYearId = '';

    public string $filterSemesterId = '';

    public string $filterStatus = '';

    public string $assignmentFilter = 'mine';

    public string $filterCoachId = '';

    public function mount(ActivityAssignmentFilter $assignmentFilter): void
    {
        $this->assignmentFilter = $assignmentFilter->defaultMode(auth()->user());
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterAcademicYearId(): void
    {
        $this->filterSemesterId = '';
        $this->resetPage();
    }

    public function updatedFilterSemesterId(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatedAssignmentFilter(): void
    {
        $this->filterCoachId = '';
        $this->resetPage();
    }

    public function updatedFilterCoachId(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $academicYears = AcademicYear::query()
            ->orderByDesc('start_date')
            ->get();

        $semesters = Semester::query()
            ->when(
                $this->filterAcademicYearId,
                fn ($query) => $query->where(
                    'academic_year_id',
                    (int) $this->filterAcademicYearId
                )
            )
            ->orderBy('semester_number')
            ->get();

        $schoolId = app(SchoolContext::class)->id();
        abort_unless($schoolId, 409, 'Pilih sekolah aktif terlebih dahulu.');
        $assignmentCoaches = app(ActivityAssignmentFilter::class)->coaches($schoolId);

        $activities = app(ActivityAssignmentFilter::class)->apply(
            Activity::query(),
            auth()->user(),
            $schoolId,
            $this->assignmentFilter,
            $this->filterCoachId !== '' ? (int) $this->filterCoachId : null,
        )
            ->with([
                'academicYear',
                'semester',
            ])
            ->when(
                $this->search,
                function ($query): void {
                    $search = '%'.trim($this->search).'%';

                    $query->where(function ($query) use ($search): void {
                        $query
                            ->where('title', 'like', $search)
                            ->orWhere('location', 'like', $search);
                    });
                }
            )
            ->when(
                $this->filterAcademicYearId,
                fn ($query) => $query->where(
                    'academic_year_id',
                    (int) $this->filterAcademicYearId
                )
            )
            ->when(
                $this->filterSemesterId,
                fn ($query) => $query->where(
                    'semester_id',
                    (int) $this->filterSemesterId
                )
            )
            ->when(
                $this->filterStatus,
                fn ($query) => $query->where(
                    'status',
                    $this->filterStatus
                )
            )
            ->orderByDesc('start_at')
            ->paginate(10);

        return view(
            'livewire.attendances.index',
            compact(
                'activities',
                'academicYears',
                'semesters', 'assignmentCoaches'
            )
        );
    }
}
