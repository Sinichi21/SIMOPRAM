<?php

namespace App\Livewire\AcademicYears;

use App\Models\AcademicYear;
use App\Support\SchoolContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public ?int $editingId = null;

    public string $name = '';

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
            'name' => [
                'required',
                'string',
                'max:20',

                Rule::unique(
                    'academic_years',
                    'name'
                )
                    ->where(
                        fn ($query) => $query->where(
                            'school_id',
                            $schoolId
                        )
                    )
                    ->ignore($this->editingId),
            ],

            'start_date' => [
                'required',
                'date',
            ],

            'end_date' => [
                'required',
                'date',
                'after:start_date',
            ],

            'is_active' => [
                'boolean',
            ],
        ];
    }

    public function save(): void
    {
        abort_unless(
            auth()->user()->can(
                'academic_years.manage'
            ),
            403
        );

        $validated = $this->validate();

        DB::transaction(function () use ($validated) {

            if ($validated['is_active']) {
                AcademicYear::query()
                    ->when(
                        $this->editingId,
                        fn ($query) => $query->where(
                            'id',
                            '!=',
                            $this->editingId
                        )
                    )
                    ->update([
                        'is_active' => false,
                    ]);
            }

            if ($this->editingId) {
                $academicYear = AcademicYear::findOrFail(
                    $this->editingId
                );
            } else {
                $academicYear = new AcademicYear;
            }

            $academicYear->fill($validated);
            $academicYear->save();
        });

        session()->flash(
            'success',
            $this->editingId
                ? 'Tahun ajaran berhasil diperbarui.'
                : 'Tahun ajaran berhasil ditambahkan.'
        );

        $this->resetForm();
    }

    public function edit(int $id): void
    {
        abort_unless(
            auth()->user()->can(
                'academic_years.manage'
            ),
            403
        );

        $academicYear =
            AcademicYear::findOrFail($id);

        $this->editingId =
            $academicYear->id;

        $this->name =
            $academicYear->name;

        $this->start_date =
            $academicYear
                ->start_date
                ->format('Y-m-d');

        $this->end_date =
            $academicYear
                ->end_date
                ->format('Y-m-d');

        $this->is_active =
            $academicYear->is_active;
    }

    public function delete(int $id): void
    {
        abort_unless(
            auth()->user()->can(
                'academic_years.manage'
            ),
            403
        );

        $academicYear =
            AcademicYear::findOrFail($id);

        if (
            $academicYear->semesters()->exists() ||
            $academicYear->enrollments()->exists()
        ) {
            session()->flash(
                'error',
                'Tahun ajaran sudah digunakan dan tidak dapat dihapus.'
            );

            return;
        }

        $academicYear->delete();

        session()->flash(
            'success',
            'Tahun ajaran berhasil dihapus.'
        );

        $this->resetForm();
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingId',
            'name',
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
                ->when(
                    $this->search,
                    fn ($query) => $query->where(
                        'name',
                        'like',
                        '%'.$this->search.'%'
                    )
                )
                ->orderByDesc('start_date')
                ->paginate(10);

        return view(
            'livewire.academic-years.index',
            compact('academicYears')
        );
    }
}
