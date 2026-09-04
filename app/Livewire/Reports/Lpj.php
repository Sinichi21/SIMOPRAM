<?php

namespace App\Livewire\Reports;

use App\Models\AcademicYear;
use App\Models\Semester;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Lpj extends Component
{
    public ?int $academicYearId = null;

    public ?int $semesterId = null;

    public string $periodType = 'monthly';

    public ?int $month = null;

    public function mount(): void
    {
        $year = AcademicYear::query()->where('is_active', true)->first();
        $this->academicYearId = $year?->id;
        $this->semesterId = $year?->semesters()->where('is_active', true)->value('id');
        $this->setDefaultMonth();
    }

    public function updatedAcademicYearId(): void
    {
        $this->semesterId = Semester::query()->where('academic_year_id', $this->academicYearId)->where('is_active', true)->value('id');
        $this->setDefaultMonth();
    }

    public function updatedSemesterId(): void
    {
        $this->setDefaultMonth();
    }

    public function render(): View
    {
        return view('livewire.reports.lpj', [
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(),
            'semesters' => Semester::query()->when($this->academicYearId, fn ($query) => $query->where('academic_year_id', $this->academicYearId))->orderBy('semester_number')->get(),
            'months' => $this->availableMonths(),
        ]);
    }

    /** @return array<int, string> */
    private function availableMonths(): array
    {
        $semester = $this->semesterId ? Semester::query()->find($this->semesterId) : null;

        if (! $semester) {
            return [];
        }

        $cursor = CarbonImmutable::parse($semester->start_date)->startOfMonth();
        $end = CarbonImmutable::parse($semester->end_date)->startOfMonth();
        $months = [];

        while ($cursor->lte($end)) {
            $months[(int) $cursor->format('n')] = $cursor->translatedFormat('F Y');
            $cursor = $cursor->addMonth();
        }

        return $months;
    }

    private function setDefaultMonth(): void
    {
        $months = $this->availableMonths();
        $currentMonth = (int) now()->format('n');
        $this->month = array_key_exists($currentMonth, $months) ? $currentMonth : array_key_first($months);
    }
}
