<?php

namespace App\Livewire\Students;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Student;
use App\Services\StudentProgressionService;
use App\Support\SchoolContext;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class Progression extends Component
{
    public ?int $sourceYearId = null;

    public ?int $sourceClassId = null;

    public ?int $targetYearId = null;

    public ?int $targetClassId = null;

    public string $action = 'promote';

    public array $studentIds = [];

    public function updatedSourceYearId(): void
    {
        $this->studentIds = [];
    }

    public function updatedSourceClassId(): void
    {
        $this->studentIds = [];
    }

    protected function candidates(): Builder
    {
        abort_unless(auth()->user()?->can('students.update'), 403);
        $schoolId = app(SchoolContext::class)->id();
        abort_unless($schoolId, 409);

        return Student::query()->where('school_id', $schoolId)->where('status', 'active')
            ->whereHas('enrollments', fn ($query) => $query->where('academic_year_id', $this->sourceYearId)
                ->where('classroom_id', $this->sourceClassId)->where('status', 'active'));
    }

    public function selectAll(): void
    {
        $this->studentIds = $this->candidates()->orderBy('name')->limit(500)->pluck('id')->all();
    }

    public function process(StudentProgressionService $service): void
    {
        $this->validate([
            'sourceYearId' => ['required', 'integer'], 'sourceClassId' => ['required', 'integer'],
            'studentIds' => ['required', 'array', 'min:1', 'max:500'], 'studentIds.*' => ['integer'],
            'action' => ['required', 'in:promote,repeat,graduate'],
            'targetYearId' => ['nullable', 'required_unless:action,graduate', 'integer'],
            'targetClassId' => ['nullable', 'required_unless:action,graduate', 'integer'],
        ]);
        $count = $service->process($this->sourceYearId, $this->sourceClassId, $this->studentIds, $this->action, $this->targetYearId, $this->targetClassId);
        $this->studentIds = [];
        session()->flash('progression', $count.' siswa berhasil diproses. Biodata dan riwayat tahun sebelumnya tetap tersimpan.');
        $this->dispatch('students-progressed');
    }

    public function render(): View
    {
        $students = $this->candidates()->orderBy('name')->limit(500)->get(['id', 'name', 'nis']);

        return view('livewire.students.progression', [
            'students' => $students,
            'years' => AcademicYear::query()->orderByDesc('start_date')->get(),
            'classrooms' => Classroom::query()->where('is_active', true)->orderBy('grade')->orderBy('name')->get(),
        ]);
    }
}
