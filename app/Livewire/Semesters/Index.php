<?php

namespace App\Livewire\Semesters;

use App\Models\AcademicYear;
use App\Models\Semester;
use App\Support\SchoolContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public ?int $editingId = null;

    public ?int $academic_year_id = null;

    public ?int $semester_number = null;

    public string $start_date = '';

    public string $end_date = '';

    public bool $is_active = false;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    protected function rules(): array
    {
        $schoolId = app(SchoolContext::class)->id();

        return [
            'academic_year_id' => [
                'required',
                'integer',

                Rule::exists(
                    'academic_years',
                    'id'
                )->where(
                    fn ($query) =>
                        $query->where(
                            'school_id',
                            $schoolId
                        )
                ),
            ],

            'semester_number' => [
                'required',
                'integer',
                Rule::in([1, 2]),
            ],

            'start_date' => [
                'required',
                'date',
            ],

            'end_date' => [
                'required',
                'date',
                'after_or_equal:start_date',
            ],

            'is_active' => [
                'boolean',
            ],
        ];
    }

    public function save(): void
    {
        abort_unless(
            auth()->user()->can('semesters.manage'),
            403
        );

        $validated = $this->validate();

        $academicYear = AcademicYear::findOrFail(
            $validated['academic_year_id']
        );

        /*
        |--------------------------------------------------------------------------
        | Validasi periode semester
        |--------------------------------------------------------------------------
        */

        if (
            $validated['start_date'] <
            $academicYear->start_date->format('Y-m-d')
        ) {
            $this->addError(
                'start_date',
                'Tanggal mulai semester tidak boleh sebelum tahun ajaran.'
            );

            return;
        }

        if (
            $validated['end_date'] >
            $academicYear->end_date->format('Y-m-d')
        ) {
            $this->addError(
                'end_date',
                'Tanggal selesai semester tidak boleh melewati tahun ajaran.'
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Cegah semester ganda
        |--------------------------------------------------------------------------
        */

        $duplicate = Semester::query()
            ->where(
                'academic_year_id',
                $validated['academic_year_id']
            )
            ->where(
                'semester_number',
                $validated['semester_number']
            )
            ->when(
                $this->editingId,
                fn ($query) =>
                    $query->where(
                        'id',
                        '!=',
                        $this->editingId
                    )
            )
            ->exists();

        if ($duplicate) {
            $this->addError(
                'semester_number',
                'Semester tersebut sudah tersedia pada tahun ajaran ini.'
            );

            return;
        }

        $semesterName =
            (int) $validated['semester_number'] === 1
                ? 'Ganjil'
                : 'Genap';

        DB::transaction(
            function () use (
                $validated,
                $academicYear,
                $semesterName
            ) {
                /*
                |--------------------------------------------------------------------------
                | Jika semester dijadikan aktif
                |--------------------------------------------------------------------------
                */

                if ($validated['is_active']) {

                    Semester::query()
                        ->when(
                            $this->editingId,
                            fn ($query) =>
                                $query->where(
                                    'id',
                                    '!=',
                                    $this->editingId
                                )
                        )
                        ->update([
                            'is_active' => false,
                        ]);

                    AcademicYear::query()
                        ->where(
                            'id',
                            '!=',
                            $academicYear->id
                        )
                        ->update([
                            'is_active' => false,
                        ]);

                    $academicYear->update([
                        'is_active' => true,
                    ]);
                }

                $semester = $this->editingId
                    ? Semester::findOrFail(
                        $this->editingId
                    )
                    : new Semester();

                $semester->fill([
                    'academic_year_id' =>
                        $validated['academic_year_id'],

                    'name' =>
                        $semesterName,

                    'semester_number' =>
                        $validated['semester_number'],

                    'start_date' =>
                        $validated['start_date'],

                    'end_date' =>
                        $validated['end_date'],

                    'is_active' =>
                        $validated['is_active'],
                ]);

                $semester->save();
            }
        );

        session()->flash(
            'success',
            $this->editingId
                ? 'Semester berhasil diperbarui.'
                : 'Semester berhasil ditambahkan.'
        );

        $this->resetForm();
    }

    public function edit(int $id): void
    {
        abort_unless(
            auth()->user()->can('semesters.manage'),
            403
        );

        $semester = Semester::findOrFail($id);

        $this->editingId =
            $semester->id;

        $this->academic_year_id =
            $semester->academic_year_id;

        $this->semester_number =
            $semester->semester_number;

        $this->start_date =
            $semester
                ->start_date
                ->format('Y-m-d');

        $this->end_date =
            $semester
                ->end_date
                ->format('Y-m-d');

        $this->is_active =
            $semester->is_active;
    }

    public function delete(int $id): void
    {
        abort_unless(
            auth()->user()->can('semesters.manage'),
            403
        );

        $semester = Semester::findOrFail($id);

        if ($semester->is_active) {
            session()->flash(
                'error',
                'Semester aktif tidak dapat dihapus.'
            );

            return;
        }

        $semester->delete();

        session()->flash(
            'success',
            'Semester berhasil dihapus.'
        );
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingId',
            'academic_year_id',
            'semester_number',
            'start_date',
            'end_date',
            'is_active',
        ]);

        $this->resetValidation();
    }

    public function render()
    {
        $academicYears =
            AcademicYear::query()
                ->orderByDesc('start_date')
                ->get();

        $semesters =
            Semester::query()
                ->with('academicYear')
                ->when(
                    $this->search,
                    function ($query) {
                        $query->whereHas(
                            'academicYear',
                            fn ($query) =>
                                $query->where(
                                    'name',
                                    'like',
                                    '%' .
                                    $this->search .
                                    '%'
                                )
                        );
                    }
                )
                ->orderByDesc('start_date')
                ->paginate(10);

        return view(
            'livewire.semesters.index',
            compact(
                'academicYears',
                'semesters'
            )
        );
    }
}